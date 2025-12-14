<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Debt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service for caching debt collection queries.
 *
 * This service provides centralized caching for debt queries to avoid
 * redundant database calls across multiple Livewire components.
 */
class DebtCacheService
{
    public const CACHE_KEY_ALL = 'debts:all';

    public const CACHE_KEY_WITH_PAYMENTS = 'debts:all_with_payments';

    public const CACHE_TTL_MINUTES = 1440; // 24 hours

    /**
     * Get all debts with caching.
     *
     * @return Collection<int, Debt>
     */
    public function getAll(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_ALL,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => Debt::all()
        );
    }

    /**
     * Get all debts with payments relationship eager loaded.
     *
     * @return Collection<int, Debt>
     */
    public function getAllWithPayments(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_WITH_PAYMENTS,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => Debt::with('payments')->get()
        );
    }

    /**
     * Clear all debt-related caches.
     * Called when debts or payments are modified.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_ALL);
        Cache::forget(self::CACHE_KEY_WITH_PAYMENTS);

        // Also clear related calculation caches
        DebtCalculationService::clearAllCalculationCaches();
    }

    /**
     * Check if the cache has been populated.
     */
    public function hasCachedData(): bool
    {
        return Cache::has(self::CACHE_KEY_ALL) || Cache::has(self::CACHE_KEY_WITH_PAYMENTS);
    }
}
