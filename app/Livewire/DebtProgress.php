<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCacheService;
use App\Services\DebtCalculationService;
use App\Services\PayoffSettingsService;
use App\Services\ProgressCacheService;
use App\Services\ProgressChartService;
use Carbon\Carbon;
use Livewire\Component;

class DebtProgress extends Component
{
    protected DebtCalculationService $calculationService;

    protected PayoffSettingsService $settingsService;

    protected DebtCacheService $debtCacheService;

    protected ProgressCacheService $progressCacheService;

    protected ProgressChartService $progressChartService;

    public function boot(
        DebtCalculationService $calculationService,
        PayoffSettingsService $settingsService,
        DebtCacheService $debtCacheService,
        ProgressCacheService $progressCacheService,
        ProgressChartService $progressChartService
    ): void {
        $this->calculationService = $calculationService;
        $this->settingsService = $settingsService;
        $this->debtCacheService = $debtCacheService;
        $this->progressCacheService = $progressCacheService;
        $this->progressChartService = $progressChartService;
    }

    /**
     * Generate a cache key for progress data based on debt and payment state.
     *
     * @deprecated Use ProgressCacheService::getProgressDataCacheKey() instead
     */
    public static function getProgressDataCacheKey(): string
    {
        return ProgressCacheService::getProgressDataCacheKey();
    }

    /**
     * Clear the progress data cache.
     *
     * @deprecated Use ProgressCacheService::clearCache() instead
     */
    public static function clearProgressDataCache(): void
    {
        ProgressCacheService::clearCache();
    }

    /**
     * @return array{labels: array<string>, datasets: array<array<string, mixed>>}
     */
    public function getProgressDataProperty(): array
    {
        // Check for empty data first (no caching needed)
        if (Debt::count() === 0) {
            return ['labels' => [], 'datasets' => []];
        }

        return $this->progressCacheService->remember(
            fn () => $this->progressChartService->calculateProgressData()
        );
    }

    public function getTotalPaidProperty(): float
    {
        // Sum of actual payments made (excluding reconciliation adjustments)
        $totalPaid = Payment::where('is_reconciliation_adjustment', false)
            ->sum('actual_amount');

        return round((float) $totalPaid, 2);
    }

    /**
     * Calculate the net change in total debt (positive = debt decreased, negative = debt increased)
     */
    public function getNetDebtChangeProperty(): float
    {
        $debts = $this->debtCacheService->getAll();
        $netChange = 0;

        foreach ($debts as $debt) {
            $originalBalance = $debt->original_balance ?? $debt->balance;
            $currentBalance = $debt->balance;
            $netChange += ($originalBalance - $currentBalance);
        }

        return round($netChange, 2);
    }

    public function getTotalInterestPaidProperty(): float
    {
        $interestPaid = Payment::sum('interest_paid');

        return round((float) $interestPaid, 2);
    }

    public function getAverageMonthlyPaymentProperty(): float
    {
        // Sum actual payments (excluding reconciliations) grouped by payment_month
        $payments = Payment::selectRaw('SUM(actual_amount) as monthly_total')
            ->where('is_reconciliation_adjustment', false)
            ->groupBy('payment_month')
            ->get();

        if ($payments->isEmpty()) {
            return 0;
        }

        $totalPaidValue = $payments->sum('monthly_total');
        $totalPaid = is_numeric($totalPaidValue) ? (float) $totalPaidValue : 0;

        return round($totalPaid / $payments->count(), 2);
    }

    /**
     * Calculate the average net flow per month (includes reconciliation adjustments)
     * This shows the actual net effect on debt - negative means debt increased on average
     */
    public function getAverageNetFlowProperty(): float
    {
        // Sum ALL payments (including reconciliations) grouped by payment_month
        // This gives the "net" view - actual payments minus credit card charges etc.
        $payments = Payment::selectRaw('SUM(actual_amount) as monthly_total')
            ->groupBy('payment_month')
            ->get();

        if ($payments->isEmpty()) {
            return 0;
        }

        $totalValue = $payments->sum('monthly_total');
        $total = is_numeric($totalValue) ? (float) $totalValue : 0;

        return round($total / $payments->count(), 2);
    }

    public function getMonthsToDebtFreeProperty(): int
    {
        $debts = $this->debtCacheService->getAllWithPayments();

        if ($debts->isEmpty()) {
            return 0;
        }

        $schedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $this->settingsService->getExtraPayment(),
            $this->settingsService->getStrategy()
        );

        $months = $schedule['months'] ?? 0;

        return is_numeric($months) ? (int) $months : 0;
    }

    public function getProjectedPayoffDateProperty(): string
    {
        $debts = $this->debtCacheService->getAllWithPayments();

        if ($debts->isEmpty()) {
            $carbon = now();
            $carbon->locale('nb');

            return $carbon->isoFormat('MMMM YYYY');
        }

        $schedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $this->settingsService->getExtraPayment(),
            $this->settingsService->getStrategy()
        );

        /** @var string $payoffDate */
        $payoffDate = $schedule['payoffDate'] ?? now()->toDateString();
        $carbon = Carbon::parse($payoffDate);
        $carbon->locale('nb');

        return $carbon->isoFormat('MMMM YYYY');
    }

    public function getProjectedTotalInterestProperty(): float
    {
        $debts = $this->debtCacheService->getAllWithPayments();

        if ($debts->isEmpty()) {
            return 0;
        }

        $schedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $this->settingsService->getExtraPayment(),
            $this->settingsService->getStrategy()
        );

        $totalInterest = $schedule['totalInterest'] ?? 0;

        return is_numeric($totalInterest) ? (float) $totalInterest : 0;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.debt-progress');
    }
}
