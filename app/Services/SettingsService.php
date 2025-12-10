<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const DEFAULT_KREDITTKORT_PERCENTAGE = 0.03;

    private const DEFAULT_KREDITTKORT_MINIMUM = 300;

    private const DEFAULT_FORBRUKSLAN_PAYOFF_MONTHS = 60;

    private const DEFAULT_HIGH_INTEREST_THRESHOLD = 15.0;

    private const DEFAULT_LOW_INTEREST_THRESHOLD = 5.0;

    private const DEFAULT_BUFFER_TARGET_MONTHS = 2;

    private const DEFAULT_MIN_INTEREST_SAVINGS = 1000.0;

    private const CACHE_KEY = 'app_settings';

    private const CACHE_TTL_HOURS = 1;

    /**
     * Check if YNAB integration is enabled.
     */
    public function isYnabEnabled(): bool
    {
        return $this->get('ynab.enabled', 'boolean') ?? false;
    }

    /**
     * Enable or disable YNAB integration.
     */
    public function setYnabEnabled(bool $enabled): void
    {
        $this->set('ynab.enabled', $enabled, 'boolean', 'ynab');
    }

    /**
     * Get the decrypted YNAB token.
     */
    public function getYnabToken(): ?string
    {
        return $this->get('ynab.token', 'encrypted');
    }

    /**
     * Set and encrypt the YNAB token.
     */
    public function setYnabToken(?string $token): void
    {
        if ($token === null) {
            Setting::where('key', 'ynab.token')->delete();
            $this->clearCacheForKey('ynab.token');

            return;
        }

        $this->set('ynab.token', $token, 'encrypted', 'ynab');
    }

    /**
     * Get the YNAB budget ID.
     */
    public function getYnabBudgetId(): ?string
    {
        return $this->get('ynab.budget_id', 'string');
    }

    /**
     * Set the YNAB budget ID.
     */
    public function setYnabBudgetId(?string $budgetId): void
    {
        if ($budgetId === null) {
            Setting::where('key', 'ynab.budget_id')->delete();
            $this->clearCacheForKey('ynab.budget_id');

            return;
        }

        $this->set('ynab.budget_id', $budgetId, 'string', 'ynab');
    }

    /**
     * Check if YNAB is fully configured (enabled, has token, has budget ID).
     */
    public function isYnabConfigured(): bool
    {
        return $this->isYnabEnabled()
            && $this->getYnabToken() !== null
            && $this->getYnabBudgetId() !== null;
    }

    /**
     * Clear all YNAB credentials.
     */
    public function clearYnabCredentials(): void
    {
        Setting::whereIn('key', ['ynab.token', 'ynab.budget_id'])->delete();
        $this->clearCacheForKey('ynab.token');
        $this->clearCacheForKey('ynab.budget_id');
    }

    /**
     * Check if YNAB background sync is enabled.
     */
    public function isYnabBackgroundSyncEnabled(): bool
    {
        return $this->get('ynab.background_sync_enabled', 'boolean') ?? false;
    }

    /**
     * Enable or disable YNAB background sync.
     */
    public function setYnabBackgroundSyncEnabled(bool $enabled): void
    {
        $this->set('ynab.background_sync_enabled', $enabled, 'boolean', 'ynab');
    }

    /**
     * Get the YNAB background sync interval in minutes.
     */
    public function getYnabBackgroundSyncInterval(): int
    {
        return $this->get('ynab.background_sync_interval', 'integer') ?? 30;
    }

    /**
     * Set the YNAB background sync interval in minutes.
     */
    public function setYnabBackgroundSyncInterval(int $minutes): void
    {
        $this->set('ynab.background_sync_interval', $minutes, 'integer', 'ynab');
    }

    /**
     * Get the timestamp of the last YNAB background sync.
     */
    public function getYnabLastSyncAt(): ?\DateTimeInterface
    {
        $timestamp = $this->get('ynab.last_sync_at', 'string');

        return $timestamp ? new \DateTimeImmutable($timestamp) : null;
    }

    /**
     * Set the timestamp of the last YNAB background sync.
     */
    public function setYnabLastSyncAt(\DateTimeInterface $datetime): void
    {
        $this->set('ynab.last_sync_at', $datetime->format('Y-m-d H:i:s'), 'string', 'ynab');
    }

    /**
     * Check if YNAB background sync is due based on the interval.
     */
    public function isYnabSyncDue(): bool
    {
        if (! $this->isYnabBackgroundSyncEnabled() || ! $this->isYnabConfigured()) {
            return false;
        }

        $lastSync = $this->getYnabLastSyncAt();

        if ($lastSync === null) {
            return true;
        }

        $intervalMinutes = $this->getYnabBackgroundSyncInterval();
        $nextSyncAt = (new \DateTimeImmutable)->setTimestamp($lastSync->getTimestamp() + ($intervalMinutes * 60));

        return new \DateTimeImmutable >= $nextSyncAt;
    }

    /**
     * Get the kredittkort percentage setting.
     */
    public function getKredittkortPercentage(): float
    {
        return $this->get('debt.kredittkort_percentage', 'float') ?? self::DEFAULT_KREDITTKORT_PERCENTAGE;
    }

    /**
     * Set the kredittkort percentage setting.
     */
    public function setKredittkortPercentage(float $percentage): void
    {
        $this->set('debt.kredittkort_percentage', $percentage, 'float', 'debt');
    }

    /**
     * Get the kredittkort minimum payment setting.
     */
    public function getKredittkortMinimum(): float
    {
        return $this->get('debt.kredittkort_minimum', 'float') ?? self::DEFAULT_KREDITTKORT_MINIMUM;
    }

    /**
     * Set the kredittkort minimum payment setting.
     */
    public function setKredittkortMinimum(float $minimum): void
    {
        $this->set('debt.kredittkort_minimum', $minimum, 'float', 'debt');
    }

    /**
     * Get the forbrukslån payoff months setting.
     */
    public function getForbrukslånPayoffMonths(): int
    {
        return $this->get('debt.forbrukslan_payoff_months', 'integer') ?? self::DEFAULT_FORBRUKSLAN_PAYOFF_MONTHS;
    }

    /**
     * Set the forbrukslån payoff months setting.
     */
    public function setForbrukslånPayoffMonths(int $months): void
    {
        $this->set('debt.forbrukslan_payoff_months', $months, 'integer', 'debt');
    }

    /**
     * Get the high interest threshold setting.
     */
    public function getHighInterestThreshold(): float
    {
        return $this->get('recommendations.high_interest_threshold', 'float') ?? self::DEFAULT_HIGH_INTEREST_THRESHOLD;
    }

    /**
     * Set the high interest threshold setting.
     */
    public function setHighInterestThreshold(float $threshold): void
    {
        $this->set('recommendations.high_interest_threshold', $threshold, 'float', 'recommendations');
    }

    /**
     * Get the low interest threshold setting.
     */
    public function getLowInterestThreshold(): float
    {
        return $this->get('recommendations.low_interest_threshold', 'float') ?? self::DEFAULT_LOW_INTEREST_THRESHOLD;
    }

    /**
     * Set the low interest threshold setting.
     */
    public function setLowInterestThreshold(float $threshold): void
    {
        $this->set('recommendations.low_interest_threshold', $threshold, 'float', 'recommendations');
    }

    /**
     * Get the buffer target months setting.
     */
    public function getBufferTargetMonths(): int
    {
        return $this->get('recommendations.buffer_target_months', 'integer') ?? self::DEFAULT_BUFFER_TARGET_MONTHS;
    }

    /**
     * Set the buffer target months setting.
     */
    public function setBufferTargetMonths(int $months): void
    {
        $this->set('recommendations.buffer_target_months', $months, 'integer', 'recommendations');
    }

    /**
     * Get the minimum interest savings threshold setting.
     */
    public function getMinInterestSavings(): float
    {
        return $this->get('recommendations.min_interest_savings', 'float') ?? self::DEFAULT_MIN_INTEREST_SAVINGS;
    }

    /**
     * Set the minimum interest savings threshold setting.
     */
    public function setMinInterestSavings(float $amount): void
    {
        $this->set('recommendations.min_interest_savings', $amount, 'float', 'recommendations');
    }

    /**
     * Reset all recommendation settings to their default values.
     */
    public function resetRecommendationSettingsToDefaults(): void
    {
        Setting::where('group', 'recommendations')->delete();
        $this->clearCache();
    }

    /**
     * Reset all debt settings to their default values.
     */
    public function resetDebtSettingsToDefaults(): void
    {
        Setting::where('group', 'debt')->delete();
        $this->clearCache();
    }

    /**
     * Get a setting value from the database with caching.
     */
    public function get(string $key, string $type = 'string'): mixed
    {
        $cacheKey = self::CACHE_KEY.'.'.$key;

        $value = Cache::remember(
            $cacheKey,
            now()->addHours(self::CACHE_TTL_HOURS),
            function () use ($key) {
                $setting = Setting::where('key', $key)->first();

                if ($setting === null) {
                    return null;
                }

                return $setting->typed_value;
            }
        );

        if ($value === null) {
            return null;
        }

        // Ensure proper type casting
        return match ($type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => (bool) $value,
            default => $value,
        };
    }

    /**
     * Set a setting value in the database and clear cache.
     */
    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        $setting = Setting::firstOrNew(['key' => $key]);
        $setting->type = $type;
        $setting->group = $group;
        $setting->typed_value = $value;
        $setting->save();

        $this->clearCacheForKey($key);
    }

    /**
     * Clear the cache for a specific setting key.
     */
    protected function clearCacheForKey(string $key): void
    {
        $cacheKey = self::CACHE_KEY.'.'.$key;
        Cache::forget($cacheKey);
    }

    /**
     * Clear all settings cache.
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
}
