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
     * @return array<int, array<string, mixed>>
     */
    public function getProgressDataProperty(): array
    {
        // Get all debts with their payments
        $debts = Debt::with('payments')->get();

        if ($debts->isEmpty()) {
            return [];
        }

        // Get the earliest payment or debt creation date
        $earliestPayment = Payment::orderBy('payment_date')->first();
        $earliestDebt = Debt::orderBy('created_at')->first();

        if (! $earliestDebt) {
            return [];
        }

        $startDate = $earliestPayment
            ? min($earliestPayment->payment_date, $earliestDebt->created_at)
            : $earliestDebt->created_at;

        // Generate monthly data points from start date to now
        $dataPoints = [];
        $currentDate = Carbon::parse($startDate)->startOfMonth();
        $now = Carbon::now();

        while ($currentDate->lte($now)) {
            $monthKey = $currentDate->format('Y-m');
            $totalBalance = 0;

            foreach ($debts as $debt) {
                // Get all payments up to this month
                $paidAmount = $debt->payments()
                    ->where('payment_date', '<=', $currentDate->endOfMonth())
                    ->sum('principal_paid');

                // Calculate remaining balance for this month
                $originalBalance = $debt->original_balance ?? $debt->balance;
                $remainingBalance = max(0, $originalBalance - $paidAmount);

                $totalBalance += $remainingBalance;
            }

            $clonedDate = clone $currentDate;
            $clonedDate->locale(app()->getLocale());
            $formattedMonth = $clonedDate->isoFormat('MMM YYYY');

            $dataPoints[] = [
                'month' => $formattedMonth,
                'date' => $currentDate->format('Y-m-d'),
                'balance' => round($totalBalance, 2),
            ];

            $currentDate->addMonth();
        }

        return $dataPoints;
    }

    public function getTotalPaidProperty(): float
    {
        $debts = Debt::with('payments')->get();
        $totalPaid = 0;

        foreach ($debts as $debt) {
            $originalBalance = $debt->original_balance ?? $debt->balance;
            $currentBalance = $debt->balance;
            $totalPaid += ($originalBalance - $currentBalance);
        }

        return round($totalPaid, 2);
    }

    public function getTotalInterestPaidProperty(): float
    {
        $interestPaid = Payment::sum('interest_paid');

        return round((float) $interestPaid, 2);
    }

    public function getAverageMonthlyPaymentProperty(): float
    {
        $payments = Payment::selectRaw('SUM(actual_amount) as monthly_total')
            ->groupBy('month_number')
            ->get();

        if ($payments->isEmpty()) {
            return 0;
        }

        $totalPaidValue = $payments->sum('monthly_total');
        $totalPaid = is_numeric($totalPaidValue) ? (float) $totalPaidValue : 0;

        return round($totalPaid / $payments->count(), 2);
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
