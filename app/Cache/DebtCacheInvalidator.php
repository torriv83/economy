<?php

declare(strict_types=1);

namespace App\Cache;

use App\Services\DebtCacheService;

/**
 * Coordinates cache invalidation across all debt-related cache layers.
 *
 * Delegates to DebtCacheService::clearCache(), which already cascades
 * into DebtCalculationService and ProgressCacheService. The internal
 * re-entry guard prevents recursive flushes within a single call stack
 * (e.g., when a flush triggers an Eloquent event that would re-enter).
 */
final class DebtCacheInvalidator
{
    private bool $flushing = false;

    /**
     * Invalidate every debt-related cache. Safe to call repeatedly;
     * re-entrant calls within the same call stack are no-ops.
     */
    public function flush(): void
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
