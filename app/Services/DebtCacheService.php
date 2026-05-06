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

    public const CACHE_KEY_ACTIVE = 'debts:active';

    public const CACHE_KEY_ACTIVE_WITH_PAYMENTS = 'debts:active_with_payments';

    public const CACHE_KEY_ARCHIVED = 'debts:archived';

    public const CACHE_KEY_ARCHIVED_WITH_PAYMENTS = 'debts:archived_with_payments';

    public const CACHE_TTL_MINUTES = 1440; // 24 hours

    /**
     * Get all debts with caching (active + archived).
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
     * Get all debts with payments relationship eager loaded (active + archived).
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
     * Get only active (not paid off) debts.
     *
     * @return Collection<int, Debt>
     */
    public function getAllActive(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_ACTIVE,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => Debt::active()->get()
        );
    }

    /**
     * Get active debts with payments eager loaded.
     *
     * @return Collection<int, Debt>
     */
    public function getAllActiveWithPayments(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_ACTIVE_WITH_PAYMENTS,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => Debt::active()->with('payments')->get()
        );
    }

    /**
     * Get only archived (paid off) debts.
     *
     * @return Collection<int, Debt>
     */
    public function getAllArchived(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_ARCHIVED,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => Debt::archived()->get()
        );
    }

    /**
     * Get archived debts with payments eager loaded.
     *
     * @return Collection<int, Debt>
     */
    public function getAllArchivedWithPayments(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_ARCHIVED_WITH_PAYMENTS,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => Debt::archived()->with('payments')->get()
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
        Cache::forget(self::CACHE_KEY_ACTIVE);
        Cache::forget(self::CACHE_KEY_ACTIVE_WITH_PAYMENTS);
        Cache::forget(self::CACHE_KEY_ARCHIVED);
        Cache::forget(self::CACHE_KEY_ARCHIVED_WITH_PAYMENTS);

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
