<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Debt;
use App\Services\DebtCacheService;
use App\Services\ProgressCacheService;

class DebtObserver
{
    /**
     * Threshold under which a balance is treated as fully paid off.
     */
    private const PAID_OFF_THRESHOLD = 0.01;

    /**
     * Handle the Debt "saving" event.
     *
     * Automatically sets/clears paid_off_at based on the current balance,
     * so all paths that change balance (payments, reconciliations, YNAB sync,
     * manual updates) keep the archive flag consistent.
     */
    public function saving(Debt $debt): void
    {
        if ($debt->balance <= self::PAID_OFF_THRESHOLD) {
            if ($debt->paid_off_at === null) {
                $debt->paid_off_at = now();
            }
        } else {
            if ($debt->paid_off_at !== null) {
                $debt->paid_off_at = null;
            }
        }
    }

    /**
     * Handle the Debt "created" event.
     */
    public function created(Debt $debt): void
    {
        DebtCacheService::clearCache();
        ProgressCacheService::clearCache();
        \App\Services\DebtCalculationService::clearAllCalculationCaches();
    }

    /**
     * Handle the Debt "updated" event.
     */
    public function updated(Debt $debt): void
    {
        DebtCacheService::clearCache();
        ProgressCacheService::clearCache();
        \App\Services\DebtCalculationService::clearAllCalculationCaches();
    }

    /**
     * Handle the Debt "deleted" event.
     */
    public function deleted(Debt $debt): void
    {
        DebtCacheService::clearCache();
        ProgressCacheService::clearCache();
        \App\Services\DebtCalculationService::clearAllCalculationCaches();
    }

    /**
     * Handle the Debt "restored" event.
     */
    public function restored(Debt $debt): void
    {
        DebtCacheService::clearCache();
        ProgressCacheService::clearCache();
        \App\Services\DebtCalculationService::clearAllCalculationCaches();
    }

    /**
     * Handle the Debt "force deleted" event.
     */
    public function forceDeleted(Debt $debt): void
    {
        DebtCacheService::clearCache();
        ProgressCacheService::clearCache();
        \App\Services\DebtCalculationService::clearAllCalculationCaches();
    }
}
