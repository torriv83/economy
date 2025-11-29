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
            // Pre-warm cache by fetching data
            $ynabService->fetchBudgetSummary();
            $ynabService->fetchCategories();
            $ynabService->fetchDebtAccounts();

            // Update last sync timestamp
            $settingsService->setYnabLastSyncAt(new \DateTimeImmutable);

            Log::info('YNAB background sync completed successfully');
        } catch (\Exception $e) {
            Log::error('YNAB background sync failed: '.$e->getMessage());
        }
    }
}
