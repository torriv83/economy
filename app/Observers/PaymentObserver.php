<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Payment;
use App\Services\DebtCacheService;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        DebtCacheService::clearCache();
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        DebtCacheService::clearCache();
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        DebtCacheService::clearCache();
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        DebtCacheService::clearCache();
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        DebtCacheService::clearCache();
    }
}
