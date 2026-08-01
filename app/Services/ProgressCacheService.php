<?php

declare(strict_types=1);

namespace App\Services;

use App\Cache\DebtCacheVersion;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing progress data caching.
 *
 * This service handles all caching operations for debt progress data,
 * providing a centralized location for cache key generation and invalidation.
 */
class ProgressCacheService
{
    private const CACHE_KEY_PREFIX = 'progress_data';

    private const CACHE_TTL_HOURS = 24;

    /**
     * Get the environment-aware cache key prefix.
     * This ensures test and production caches never collide.
     */
    private static function getPrefix(): string
    {
        $env = app()->environment();

        return self::CACHE_KEY_PREFIX.'_'.$env;
    }

    /**
     * Generate a cache key for progress data based on debt and payment state.
     *
     * The cache key is derived from the latest update timestamps of both
     * debts and payments, ensuring the cache is automatically invalidated
     * when data changes, and from the shared debt cache version.
     */
    public function getCacheKey(): string
    {
        $paymentMaxUpdated = Payment::max('updated_at') ?? '';
        $debtMaxUpdated = Debt::max('updated_at') ?? '';

        return DebtCacheVersion::key(self::getPrefix().':'.md5($paymentMaxUpdated.$debtMaxUpdated));
    }

    /**
     * Get cached progress data or compute it using the provided callback.
     *
     * @param  callable  $callback  Function to compute data if not cached
     * @return array<string, mixed>
     */
    public function remember(callable $callback): array
    {
        $cacheKey = $this->getCacheKey();

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), $callback);
    }

    /**
     * Check if the progress data cache exists.
     */
    public function has(): bool
    {
        return Cache::has($this->getCacheKey());
    }

    /**
     * Clear the progress data cache by advancing the shared debt cache
     * version, which makes every previously cached key unreachable.
     */
    public function clear(): void
    {
        DebtCacheVersion::bump();
    }

    /**
     * Static helper for clearing cache (maintains backward compatibility).
     *
     * This method allows clearing the cache without needing to instantiate
     * the service, useful for model observers and other static contexts.
     */
    public static function clearCache(): void
    {
        app(self::class)->clear();
    }

    /**
     * Static helper for getting the cache key (maintains backward compatibility).
     *
     * This method allows retrieving the cache key without needing to instantiate
     * the service, useful for tests and other static contexts.
     */
    public static function getProgressDataCacheKey(): string
    {
        return app(self::class)->getCacheKey();
    }
}
