<?php

declare(strict_types=1);

namespace App\Services;

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

    private const CACHE_TTL_HOURS = 1;

    /**
     * Generate a cache key for progress data based on debt and payment state.
     *
     * The cache key is derived from the latest update timestamps of both
     * debts and payments, ensuring the cache is automatically invalidated
     * when data changes.
     */
    public function getCacheKey(): string
    {
        $paymentMaxUpdated = Payment::max('updated_at') ?? '';
        $debtMaxUpdated = Debt::max('updated_at') ?? '';

        return self::CACHE_KEY_PREFIX.':'.md5($paymentMaxUpdated.$debtMaxUpdated);
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
     * Clear the progress data cache.
     *
     * For Redis, clears all progress data cache keys by pattern.
     * For file/array cache, clears the current cache key.
     */
    public function clear(): void
    {
        $store = Cache::getStore();

        if ($store instanceof \Illuminate\Cache\RedisStore) {
            /** @var \Illuminate\Redis\Connections\Connection $redis */
            $redis = $store->getRedis();
            $prefix = config('cache.prefix', 'laravel').':';
            $keys = $redis->keys($prefix.self::CACHE_KEY_PREFIX.':*');
            foreach ($keys as $key) {
                $cacheKey = str_replace($prefix, '', $key);
                Cache::forget($cacheKey);
            }
        } else {
            // For file/array cache, just clear the current cache key
            Cache::forget($this->getCacheKey());
        }
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
