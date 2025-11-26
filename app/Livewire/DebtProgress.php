<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCalculationService;
use App\Services\PayoffSettingsService;
use Carbon\Carbon;
use Livewire\Component;

class DebtProgress extends Component
{
    protected DebtCalculationService $calculationService;

    protected PayoffSettingsService $settingsService;

    public function boot(DebtCalculationService $calculationService, PayoffSettingsService $settingsService): void
    {
        $this->calculationService = $calculationService;
        $this->settingsService = $settingsService;
    }

    /**
     * @return array{labels: array<string>, datasets: array<array<string, mixed>>}
     */
    public function getProgressDataProperty(): array
    {
        // Get all debts with their payments
        $debts = Debt::with('payments')->get();

        if ($debts->isEmpty()) {
            return ['labels' => [], 'datasets' => []];
        }

        // Get the earliest payment or debt creation date
        $earliestPayment = Payment::orderBy('payment_date')->first();
        $earliestDebt = Debt::orderBy('created_at')->first();

        if (! $earliestDebt) {
            return ['labels' => [], 'datasets' => []];
        }

        $startDate = $earliestPayment
            ? min($earliestPayment->payment_date, $earliestDebt->created_at)
            : $earliestDebt->created_at;

        // Color palette for individual debt lines
        $colors = [
            '#10B981', // green
            '#F59E0B', // amber
            '#EF4444', // red
            '#8B5CF6', // purple
            '#EC4899', // pink
            '#06B6D4', // cyan
            '#84CC16', // lime
        ];

        // Initialize data structures
        $labels = [];
        $totalData = [];
        $debtData = [];

        // Initialize debt data arrays
        foreach ($debts as $index => $debt) {
            $debtData[$debt->name] = [
                'label' => $debt->name,
                'data' => [],
                'borderColor' => $colors[$index % count($colors)],
            ];
        }

        // Generate monthly data points from start date to now
        $currentDate = Carbon::parse($startDate)->startOfMonth();
        $now = Carbon::now();

        while ($currentDate->lte($now)) {
            $clonedDate = clone $currentDate;
            $clonedDate->locale(app()->getLocale());
            $formattedMonth = $clonedDate->isoFormat('MMM YYYY');
            $labels[] = $formattedMonth;

            $totalBalance = 0;

            foreach ($debts as $debt) {
                // Get all payments up to this month
                $paidAmount = $debt->payments()
                    ->where('payment_date', '<=', $currentDate->endOfMonth())
                    ->sum('principal_paid');

                // Calculate remaining balance for this month
                $originalBalance = $debt->original_balance ?? $debt->balance;
                $remainingBalance = max(0, $originalBalance - $paidAmount);

                $debtData[$debt->name]['data'][] = round($remainingBalance, 2);
                $totalBalance += $remainingBalance;
            }

            $totalData[] = round($totalBalance, 2);
            $currentDate->addMonth();
        }

        // Build datasets array - total first, then individual debts
        $datasets = [
            [
                'label' => __('app.total_debt_balance'),
                'data' => $totalData,
                'borderColor' => '#3B82F6',
                'isTotal' => true,
            ],
        ];

        foreach ($debtData as $data) {
            $datasets[] = $data;
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
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
        $debts = Debt::all();
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
        $debts = Debt::with('payments')->get();

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
        $debts = Debt::with('payments')->get();

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
        $debts = Debt::with('payments')->get();

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
