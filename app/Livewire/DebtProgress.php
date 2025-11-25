<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCalculationService;
use Carbon\Carbon;
use Livewire\Component;

class DebtProgress extends Component
{
    protected DebtCalculationService $calculationService;

    public function boot(DebtCalculationService $calculationService): void
    {
        $this->calculationService = $calculationService;
    }

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

            $dataPoints[] = [
                'month' => $currentDate->locale(app()->getLocale())->translatedFormat('M Y'),
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
        return round(Payment::sum('interest_paid'), 2);
    }

    public function getAverageMonthlyPaymentProperty(): float
    {
        $payments = Payment::selectRaw('SUM(actual_amount) as monthly_total')
            ->groupBy('month_number')
            ->get();

        if ($payments->isEmpty()) {
            return 0;
        }

        $totalPaid = $payments->sum('monthly_total');

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
            2000,
            'avalanche'
        );

        return $schedule['months'];
    }

    public function getProjectedPayoffDateProperty(): string
    {
        $debts = Debt::with('payments')->get();

        if ($debts->isEmpty()) {
            return now()->locale('nb')->translatedFormat('F Y');
        }

        $schedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            2000,
            'avalanche'
        );

        return now()->parse($schedule['payoffDate'])->locale('nb')->translatedFormat('F Y');
    }

    public function getProjectedTotalInterestProperty(): float
    {
        $debts = Debt::with('payments')->get();

        if ($debts->isEmpty()) {
            return 0;
        }

        $schedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            2000,
            'avalanche'
        );

        return $schedule['totalInterest'];
    }

    public function render()
    {
        return view('livewire.debt-progress');
    }
}
