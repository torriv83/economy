<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Cache\DebtCacheInvalidator;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Events\Dispatcher;

/**
 * Subscribes to Eloquent lifecycle events for Debt and Payment models and
 * schedules a cache flush. The invalidator defers the flush until the
 * surrounding transaction commits.
 */
final class InvalidateDebtCacheSubscriber
{
    public function __construct(
        private readonly DebtCacheInvalidator $invalidator,
    ) {}

    public function onChange(): void
    {
        $this->invalidator->flush();
    }

    /**
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'eloquent.saved: '.Debt::class => 'onChange',
            'eloquent.deleted: '.Debt::class => 'onChange',
            'eloquent.restored: '.Debt::class => 'onChange',
            'eloquent.forceDeleted: '.Debt::class => 'onChange',
            'eloquent.saved: '.Payment::class => 'onChange',
            'eloquent.deleted: '.Payment::class => 'onChange',
            'eloquent.restored: '.Payment::class => 'onChange',
            'eloquent.forceDeleted: '.Payment::class => 'onChange',
        ];
    }
}
