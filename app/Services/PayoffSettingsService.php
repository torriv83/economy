<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PayoffSetting;
use Illuminate\Support\Facades\Cache;

class PayoffSettingsService
{
    private const CACHE_KEY = 'payoff_settings';

    private const CACHE_TTL_HOURS = 1;

    public function getSettings(): PayoffSetting
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => PayoffSetting::firstOrCreate([], [
                'extra_payment' => 2000.00,
                'strategy' => 'avalanche',
            ])
        );
    }

    public function getExtraPayment(): float
    {
        return $this->getSettings()->extra_payment;
    }

    public function getStrategy(): string
    {
        return $this->getSettings()->strategy;
    }

    public function setExtraPayment(float $amount): void
    {
        $settings = $this->getSettings();
        $settings->extra_payment = $amount;
        $settings->save();
        self::clearSettingsCache();
    }

    public function setStrategy(string $strategy): void
    {
        $settings = $this->getSettings();
        $settings->strategy = $strategy;
        $settings->save();
        self::clearSettingsCache();
    }

    public function saveSettings(float $extraPayment, string $strategy): void
    {
        $settings = $this->getSettings();
        $settings->extra_payment = $extraPayment;
        $settings->strategy = $strategy;
        $settings->save();
        self::clearSettingsCache();
    }

    /**
     * Clear the payoff settings cache.
     * Call this when settings are modified from outside this service.
     */
    public static function clearSettingsCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
