<?php

namespace App\Services;

use App\Models\Debt;
use Illuminate\Support\Collection;

class DebtCalculationService
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

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

        if (abs($interestRate) < 0.01) {
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

    protected function getActualPaymentsByMonth(Collection $debts): array
    {
        $payments = [];

        foreach ($debts as $debt) {
            foreach ($debt->payments as $payment) {
                $monthNumber = $payment->month_number;
                $debtName = $debt->name;

                if (! isset($payments[$monthNumber])) {
                    $payments[$monthNumber] = [];
                }

                $payments[$monthNumber][$debtName] = $payment->actual_amount;
            }
        }

        return $payments;
    }

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

        // Get all actual payments organized by month and debt
        $actualPayments = $this->getActualPaymentsByMonth($debts);

        $orderedDebts = $strategy === 'snowball'
            ? $this->orderBySnowball($debts)
            : $this->orderByAvalanche($debts);

        $remainingDebts = $orderedDebts->map(function ($debt) {
            return [
                'id' => $debt->id,
                'name' => $debt->name,
                'balance' => $debt->original_balance ?? $debt->balance,
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

            if (count($remainingDebts) === 0) {
                break;
            }

            $monthlyPayments = [];
            $totalPaidThisMonth = 0;
            $priorityDebtName = $remainingDebts[0]['name'] ?? null;

            $hasActualPayments = isset($actualPayments[$month]);

            foreach ($remainingDebts as $index => $debt) {
                if ($debt['balance'] <= 0.01) {
                    continue;
                }

                $isPriority = $index === 0;
                $debtName = $debt['name'];

                $interest = $this->calculateMonthlyInterest($debt['balance'], $debt['interest_rate']);

                if ($hasActualPayments && isset($actualPayments[$month][$debtName])) {
                    $actualAmount = $actualPayments[$month][$debtName];
                    $totalPayment = $actualAmount;
                    $minimumPayment = $debt['minimum_payment'];
                    $extraForThisDebt = max(0, $actualAmount - $minimumPayment);
                } else {
                    $minimumPayment = $debt['minimum_payment'];
                    $extraForThisDebt = $isPriority ? $availableExtraPayment : 0;
                    $totalPayment = $minimumPayment + $extraForThisDebt;
                }

                $maxPayment = $debt['balance'] + $interest;
                $totalPayment = min($totalPayment, $maxPayment);

                if ($maxPayment - $totalPayment > 0 && $maxPayment - $totalPayment <= 1) {
                    $totalPayment = $maxPayment;
                }

                if ($totalPayment >= $maxPayment - 0.01) {
                    $newBalance = 0;
                } else {
                    $newBalance = $debt['balance'] + $interest - $totalPayment;
                }

                $totalInterest += $interest;
                $totalPaidThisMonth += $totalPayment;

                $monthlyPayments[] = [
                    'name' => $debt['name'],
                    'amount' => round($totalPayment, 2),
                    'minimum' => round($minimumPayment, 2),
                    'extra' => round($extraForThisDebt, 2),
                    'interest' => round($interest, 2),
                    'remaining' => round($newBalance, 2),
                    'isPriority' => $isPriority,
                ];

                $remainingDebts[$index]['balance'] = $newBalance;
            }

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

    /**
     * Calculate payoff time with true minimum payments only.
     * Each debt is paid independently with NO reallocation of freed-up payments.
     * This is different from snowball/avalanche which reallocate freed-up minimum payments.
     *
     * @param  Collection  $debts  Collection of Debt models
     * @return int Maximum months to pay off all debts (the longest-running debt)
     */
    public function calculateMinimumPaymentsOnly(Collection $debts): int
    {
        if ($debts->isEmpty()) {
            return 0;
        }

        $maxMonths = 0;

        foreach ($debts as $debt) {
            // Use original_balance for consistency with generatePaymentSchedule
            $balance = $debt->original_balance ?? $debt->balance;
            $interestRate = $debt->interest_rate;
            $minimumPayment = $debt->minimum_payment;

            // Skip if no minimum payment set or if balance is already zero
            if ($minimumPayment === null || $minimumPayment <= 0 || $balance <= 0.01) {
                continue;
            }

            // Calculate months for this specific debt independently
            $months = $this->calculatePayoffMonths($balance, $interestRate, $minimumPayment);

            // Track the longest payoff time
            if ($months > $maxMonths) {
                $maxMonths = $months;
            }
        }

        return $maxMonths;
    }

    /**
     * Calculate total interest paid with minimum payments only.
     * Each debt is paid independently with NO reallocation of freed-up payments.
     *
     * @param  Collection  $debts  Collection of Debt models
     * @return float Total interest paid across all debts
     */
    public function calculateMinimumPaymentsInterest(Collection $debts): float
    {
        if ($debts->isEmpty()) {
            return 0.0;
        }

        $totalInterest = 0.0;

        foreach ($debts as $debt) {
            // Use original_balance for consistency with generatePaymentSchedule
            $balance = $debt->original_balance ?? $debt->balance;
            $interestRate = $debt->interest_rate;
            $minimumPayment = $debt->minimum_payment;

            // Skip if no minimum payment set or if balance is already zero
            if ($minimumPayment === null || $minimumPayment <= 0 || $balance <= 0.01) {
                continue;
            }

            // Calculate months and interest for this specific debt independently
            $months = $this->calculatePayoffMonths($balance, $interestRate, $minimumPayment);
            $interest = $this->calculateTotalInterest($balance, $interestRate, $minimumPayment, $months);

            $totalInterest += $interest;
        }

        return $totalInterest;
    }
}
