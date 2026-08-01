<?php

declare(strict_types=1);

namespace App\Cache;

use App\Services\DebtCacheService;
use Illuminate\Support\Facades\DB;

/**
 * Coordinates cache invalidation across all debt-related cache layers.
 *
 * The flush is deferred until the surrounding database transaction commits.
 * Flushing mid-transaction would let a concurrent request re-populate the
 * cache from the pre-commit database state, leaving stale data behind for the
 * full cache lifetime. When no transaction is active, DB::afterCommit() runs
 * the callback immediately.
 */
final class DebtCacheInvalidator
{
    private bool $flushing = false;

    private bool $flushPending = false;

    /**
     * Schedule a flush of every debt-related cache. Safe to call repeatedly:
     * many model events inside one transaction result in a single flush.
     */
    public function flush(): void
    {
        if ($this->flushing || $this->flushPending) {
            return;
        }

        $this->flushPending = true;

        DB::afterCommit(function (): void {
            $this->flushPending = false;
            $this->performFlush();
        });

        // A rolled back transaction changed nothing, but the pending guard must
        // be released so later changes in the same request still invalidate.
        DB::afterRollBack(function (): void {
            $this->flushPending = false;
        });
    }

    /**
     * Perform the flush. The re-entry guard protects against a flush that
     * triggers an Eloquent event which would re-enter this method.
     */
    private function performFlush(): void
    {
        if ($this->flushing) {
            return;
        }

        $this->flushing = true;

        try {
            DebtCacheService::clearCache();
        } finally {
            $this->flushing = false;
        }
    }
}
