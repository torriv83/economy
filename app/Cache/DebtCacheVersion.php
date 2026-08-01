<?php

declare(strict_types=1);

namespace App\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Monotonic version counter embedded in every debt-related cache key.
 *
 * Invalidation is a single atomic increment: all previously cached entries
 * become unreachable at once. This replaces pattern-based key deletion, which
 * Laravel's cache stores do not support portably (and which silently matched
 * nothing on Redis because the store never writes the assumed key layout).
 */
final class DebtCacheVersion
{
    public const CACHE_KEY = 'debt_cache_version';

    /**
     * The current version, seeded on first use and stored without a TTL.
     */
    public static function current(): int
    {
        $version = Cache::get(self::CACHE_KEY);

        if ($version === null) {
            // Seeded from the wall clock rather than 1, so a version key that
            // was evicted or lost can never land back on a number that older
            // cached entries were written under.
            $version = now()->timestamp;
            Cache::forever(self::CACHE_KEY, $version);
        }

        return (int) $version;
    }

    /**
     * Invalidate every versioned cache entry by advancing the version.
     */
    public static function bump(): int
    {
        self::current();

        $version = Cache::increment(self::CACHE_KEY);

        return is_numeric($version) ? (int) $version : self::current();
    }

    /**
     * Prefix a cache key with the current version.
     */
    public static function key(string $key): string
    {
        return 'v'.self::current().':'.$key;
    }
}
