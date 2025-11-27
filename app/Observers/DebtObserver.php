<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Debt;
use App\Services\DebtCacheService;

class DebtObserver
{
    /**
     * Handle the Debt "created" event.
     */
    public function created(Debt $debt): void
    {
        DebtCacheService::clearCache();
    }

    /**
     * Handle the Debt "updated" event.
     */
    public function updated(Debt $debt): void
    {
        DebtCacheService::clearCache();
    }

    /**
     * Handle the Debt "deleted" event.
     */
    public function deleted(Debt $debt): void
    {
        DebtCacheService::clearCache();
    }

    /**
     * Handle the Debt "restored" event.
     */
    public function restored(Debt $debt): void
    {
        DebtCacheService::clearCache();
    }

    /**
     * Handle the Debt "force deleted" event.
     */
    public function forceDeleted(Debt $debt): void
    {
        DebtCacheService::clearCache();
    }
}
