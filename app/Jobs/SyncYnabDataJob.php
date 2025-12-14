<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\SettingsService;
use App\Services\YnabService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncYnabDataJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(SettingsService $settingsService): void
    {
        // Check if sync is due
        if (! $settingsService->isYnabSyncDue()) {
            return;
        }

        $token = $settingsService->getYnabToken();
        $budgetId = $settingsService->getYnabBudgetId();

        if ($token === null || $budgetId === null) {
            return;
        }

        $ynabService = new YnabService($token, $budgetId);

        try {
            // Check if YNAB data has actually changed using delta sync
            if ($ynabService->hasDataChanged()) {
                // Clear old cache since data has changed
                $ynabService->clearCache();

                // Pre-warm cache with fresh data
                $ynabService->fetchBudgetSummary();
                $ynabService->fetchCategories();
                $ynabService->fetchDebtAccounts();

                Log::info('YNAB background sync: data updated');
            } else {
                Log::info('YNAB background sync: no changes detected');
            }

            // Update last sync timestamp regardless
            $settingsService->setYnabLastSyncAt(new \DateTimeImmutable);
        } catch (\Exception $e) {
            Log::error('YNAB background sync failed: '.$e->getMessage());
        }
    }
}
