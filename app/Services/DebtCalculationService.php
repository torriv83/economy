<?php

namespace App\Services;

use Illuminate\Support\Collection;

class DebtCalculationService
{
    /**
     * Order debts by lowest balance first (Snowball method).
     * This method provides psychological wins by paying off smaller debts first.
     *
     * @param  Collection  $debts  Collection of Debt models
     * @return Collection Ordered collection with lowest balance first
     */
    public function orderBySnowball(Collection $debts): Collection
    {
        return $debts->sortBy('balance')->values();
    }

    /**
     * Order debts by highest interest rate first (Avalanche method).
     * This method minimizes total interest paid over time.
     *
     * @param  Collection  $debts  Collection of Debt models
     * @return Collection Ordered collection with highest interest rate first
     */
    public function orderByAvalanche(Collection $debts): Collection
    {
        return $debts->sortByDesc('interest_rate')->values();
    }

    /**
     * Calculate the monthly interest charge for a debt.
     *
     * @param  float  $balance  Current debt balance
     * @param  float  $annualRate  Annual interest rate as percentage (e.g., 15.5 for 15.5%)
     * @return float Monthly interest charge rounded to 2 decimals
     */
    public function calculateMonthlyInterest(float $balance, float $annualRate): float
    {
        return round($balance * ($annualRate / 100) / 12, 2);
    }

    /**
     * Calculate the number of months needed to pay off a debt.
     *
     * @param  float  $balance  Current debt balance
     * @param  float  $interestRate  Annual interest rate as percentage
     * @param  float  $monthlyPayment  Monthly payment amount
     * @return int Number of months to payoff (rounded up), or PHP_INT_MAX if never pays off
     */
    public function calculatePayoffMonths(float $balance, float $interestRate, float $monthlyPayment): int
    {
        $monthlyInterest = $this->calculateMonthlyInterest($balance, $interestRate);

        if ($monthlyPayment <= $monthlyInterest) {
            return PHP_INT_MAX;
        }

        if ($interestRate == 0) {
            return (int) ceil($balance / $monthlyPayment);
        }

        $monthlyRate = ($interestRate / 100) / 12;
        $months = -log(1 - ($balance * $monthlyRate) / $monthlyPayment) / log(1 + $monthlyRate);

        return (int) ceil($months);
    }

    /**
     * Calculate the total interest paid over the repayment period.
     *
     * @param  float  $balance  Original debt balance
     * @param  float  $interestRate  Annual interest rate as percentage
     * @param  float  $monthlyPayment  Monthly payment amount
     * @param  int  $months  Number of months to pay off
     * @return float Total interest paid rounded to 2 decimals
     */
    public function calculateTotalInterest(float $balance, float $interestRate, float $monthlyPayment, int $months): float
    {
        $totalPaid = $monthlyPayment * $months;
        $totalInterest = $totalPaid - $balance;

        return round(max(0, $totalInterest), 2);
    }

    /**
     * Generate a complete month-by-month payment schedule with snowball effect.
     *
     * @param  Collection  $debts  Collection of Debt models
     * @param  float  $extraPayment  Extra monthly payment beyond minimums
     * @param  string  $strategy  Payment strategy: 'avalanche' or 'snowball'
     * @return array Complete payment schedule with monthly details
     */
    public function generatePaymentSchedule(Collection $debts, float $extraPayment, string $strategy = 'avalanche'): array
    {
        if ($debts->isEmpty()) {
            return [
                'months' => 0,
                'totalInterest' => 0.00,
                'payoffDate' => now()->format('Y-m-d'),
                'schedule' => [],
            ];
        }

        $orderedDebts = $strategy === 'snowball'
            ? $this->orderBySnowball($debts)
            : $this->orderByAvalanche($debts);

        $remainingDebts = $orderedDebts->map(function ($debt) {
            return [
                'name' => $debt->name,
                'balance' => $debt->balance,
                'interest_rate' => $debt->interest_rate,
                'minimum_payment' => $debt->minimum_payment ?? $this->calculateMonthlyInterest($debt->balance, $debt->interest_rate),
            ];
        })->toArray();

        $schedule = [];
        $month = 0;
        $totalInterest = 0;
        $originalTotal = array_sum(array_column($remainingDebts, 'balance'));
        $availableExtraPayment = $extraPayment;
        $currentDate = now();

        while (count($remainingDebts) > 0 && $month < 600) {
            $month++;
            $monthDate = now()->addMonths($month - 1);

            $monthlyPayments = [];
            $totalPaidThisMonth = 0;
            $priorityDebtName = $remainingDebts[0]['name'] ?? null;

            foreach ($remainingDebts as $index => $debt) {
                $isPriority = $index === 0;
                $minimumPayment = $debt['minimum_payment'];
                $extraForThisDebt = $isPriority ? $availableExtraPayment : 0;
                $totalPayment = $minimumPayment + $extraForThisDebt;

                $totalPayment = min($totalPayment, $debt['balance']);

                $interest = $this->calculateMonthlyInterest($debt['balance'], $debt['interest_rate']);
                $newBalance = $debt['balance'] + $interest - $totalPayment;

                $totalInterest += $interest;
                $totalPaidThisMonth += $totalPayment;

                $monthlyPayments[] = [
                    'name' => $debt['name'],
                    'amount' => round($totalPayment, 2),
                    'minimum' => round($minimumPayment, 2),
                    'extra' => round($extraForThisDebt, 2),
                    'interest' => round($interest, 2),
                    'remaining' => round(max(0, $newBalance), 2),
                    'isPriority' => $isPriority,
                ];

                $remainingDebts[$index]['balance'] = max(0, $newBalance);
            }

            $paidOffDebts = [];
            foreach ($remainingDebts as $index => $debt) {
                if ($debt['balance'] <= 0.01) {
                    $paidOffDebts[] = $index;
                    $availableExtraPayment += $debt['minimum_payment'];
                }
            }

            foreach (array_reverse($paidOffDebts) as $index) {
                unset($remainingDebts[$index]);
            }

            $remainingDebts = array_values($remainingDebts);

            $totalRemaining = array_sum(array_column($remainingDebts, 'balance'));
            $progress = $originalTotal > 0 ? round((($originalTotal - $totalRemaining) / $originalTotal) * 100, 1) : 100;

            $schedule[] = [
                'month' => $month,
                'monthName' => $monthDate->locale('nb')->translatedFormat('F Y'),
                'date' => $monthDate->format('Y-m-d'),
                'priorityDebt' => $priorityDebtName,
                'payments' => $monthlyPayments,
                'totalPaid' => round($totalPaidThisMonth, 2),
                'progress' => $progress,
            ];
        }

        $payoffDate = now()->addMonths($month)->format('Y-m-d');

        return [
            'months' => $month,
            'totalInterest' => round($totalInterest, 2),
            'payoffDate' => $payoffDate,
            'schedule' => $schedule,
        ];
    }

    /**
     * Compare snowball and avalanche strategies.
     *
     * @param  Collection  $debts  Collection of Debt models
     * @param  float  $extraPayment  Extra monthly payment beyond minimums
     * @return array Comparison of both strategies with savings
     */
    public function compareStrategies(Collection $debts, float $extraPayment): array
    {
        $snowballSchedule = $this->generatePaymentSchedule($debts, $extraPayment, 'snowball');
        $avalancheSchedule = $this->generatePaymentSchedule($debts, $extraPayment, 'avalanche');

        $snowballOrder = $this->orderBySnowball($debts)->pluck('name')->toArray();
        $avalancheOrder = $this->orderByAvalanche($debts)->pluck('name')->toArray();

        $savings = $snowballSchedule['totalInterest'] - $avalancheSchedule['totalInterest'];

        return [
            'snowball' => [
                'months' => $snowballSchedule['months'],
                'totalInterest' => $snowballSchedule['totalInterest'],
                'order' => $snowballOrder,
            ],
            'avalanche' => [
                'months' => $avalancheSchedule['months'],
                'totalInterest' => $avalancheSchedule['totalInterest'],
                'order' => $avalancheOrder,
                'savings' => round($savings, 2),
            ],
        ];
    }
}
