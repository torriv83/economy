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
     * Order debts by custom priority order set by the user.
     * This method allows users to choose their own repayment priority.
     *
     * @param  Collection  $debts  Collection of Debt models
     * @return Collection Ordered collection by custom_priority_order (ascending)
     */
    public function orderByCustom(Collection $debts): Collection
    {
        return $debts->sortBy('custom_priority_order')->values();
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
        $cumulativePrincipal = [];

        foreach ($debts as $debt) {
            $debtName = $debt->name;
            $cumulativePrincipal[$debtName] = 0;

            // Sort payments by month_number to ensure correct cumulative calculation
            // Exclude reconciliation adjustments (month_number = NULL) as they don't represent monthly payments
            $sortedPayments = $debt->payments
                ->filter(fn ($payment) => $payment->month_number !== null)
                ->sortBy('month_number');

            foreach ($sortedPayments as $payment) {
                $monthNumber = $payment->month_number;
                $principalPaid = $payment->principal_paid ?? $payment->actual_amount;

                // Track cumulative principal paid up to and including this month
                $cumulativePrincipal[$debtName] += $principalPaid;

                if (! isset($payments[$monthNumber])) {
                    $payments[$monthNumber] = [];
                }

                $payments[$monthNumber][$debtName] = [
                    'actual_amount' => $payment->actual_amount,
                    'principal_paid' => $principalPaid,
                    'cumulative_principal_paid' => $cumulativePrincipal[$debtName],
                    'is_reconciliation' => $payment->is_reconciliation_adjustment ?? false,
                    'notes' => $payment->notes,
                ];
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

        $orderedDebts = match ($strategy) {
            'snowball' => $this->orderBySnowball($debts),
            'custom' => $this->orderByCustom($debts),
            default => $this->orderByAvalanche($debts),
        };

        // Get all actual payments organized by month and debt
        $actualPayments = $this->getActualPaymentsByMonth($debts);

        $remainingDebts = $orderedDebts->map(function ($debt) use ($actualPayments) {
            // If there are actual payments for this debt, start from original_balance to replay them
            // Otherwise, start from current balance for accurate projections
            $hasActualPaymentsForDebt = collect($actualPayments)->contains(function ($monthPayments) use ($debt) {
                return isset($monthPayments[$debt->name]);
            });

            $startingBalance = $hasActualPaymentsForDebt
                ? ($debt->original_balance ?? $debt->balance)
                : $debt->balance;

            return [
                'id' => $debt->id,
                'name' => $debt->name,
                'balance' => $startingBalance,
                'original_balance' => $debt->original_balance ?? $debt->balance,
                'interest_rate' => $debt->interest_rate,
                'minimum_payment' => $debt->minimum_payment ?? $this->calculateMonthlyInterest($debt->balance, $debt->interest_rate),
                'due_day' => $debt->due_day ?? 1,
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
            // Use the due_day from the priority debt (first in list), defaulting to 1
            $dueDay = $remainingDebts[0]['due_day'] ?? 1;
            $monthDate = now()->addMonths($month - 1)->day(min($dueDay, now()->addMonths($month - 1)->daysInMonth));

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

                $isReconciliation = false;
                $paymentNotes = null;

                if ($hasActualPayments && isset($actualPayments[$month][$debtName])) {
                    $paymentData = $actualPayments[$month][$debtName];
                    $actualAmount = $paymentData['actual_amount'];
                    $principalPaid = $paymentData['principal_paid'];
                    $cumulativePrincipalPaid = $paymentData['cumulative_principal_paid'];
                    $totalPayment = $actualAmount;
                    $minimumPayment = $debt['minimum_payment'];
                    $extraForThisDebt = max(0, $actualAmount - $minimumPayment);
                    $isReconciliation = $paymentData['is_reconciliation'] ?? false;
                    $paymentNotes = $paymentData['notes'] ?? null;
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
                    // When using actual payments, use cumulative principal_paid to calculate remaining
                    // because database balance = original_balance - SUM(principal_paid)
                    if ($hasActualPayments && isset($actualPayments[$month][$debtName])) {
                        // Calculate remaining balance based on cumulative principal paid
                        // This matches how updateDebtBalances() calculates: original_balance - SUM(principal_paid)
                        $newBalance = round($debt['original_balance'] - $cumulativePrincipalPaid, 2);
                    } else {
                        $newBalance = round($debt['balance'] + $interest - $totalPayment, 2);
                    }
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
                    'due_day' => $debt['due_day'],
                    'is_reconciliation' => $isReconciliation,
                    'notes' => $paymentNotes,
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
     * Compare snowball, avalanche, and custom strategies.
     *
     * @param  Collection  $debts  Collection of Debt models
     * @param  float  $extraPayment  Extra monthly payment beyond minimums
     * @return array Comparison of all strategies with savings vs minimum payments
     */
    public function compareStrategies(Collection $debts, float $extraPayment): array
    {
        $snowballSchedule = $this->generatePaymentSchedule($debts, $extraPayment, 'snowball');
        $avalancheSchedule = $this->generatePaymentSchedule($debts, $extraPayment, 'avalanche');
        $customSchedule = $this->generatePaymentSchedule($debts, $extraPayment, 'custom');

        $snowballOrder = $this->orderBySnowball($debts)->pluck('name')->toArray();
        $avalancheOrder = $this->orderByAvalanche($debts)->pluck('name')->toArray();
        $customOrder = $this->orderByCustom($debts)->pluck('name')->toArray();

        // Calculate minimum payment interest as baseline
        $minimumPaymentInterest = $this->calculateMinimumPaymentsInterest($debts);

        // Calculate savings: positive value means strategy saves money vs minimum payments
        $snowballSavings = $minimumPaymentInterest - $snowballSchedule['totalInterest'];
        $avalancheSavings = $minimumPaymentInterest - $avalancheSchedule['totalInterest'];
        $customSavings = $minimumPaymentInterest - $customSchedule['totalInterest'];

        return [
            'snowball' => [
                'months' => $snowballSchedule['months'],
                'totalInterest' => $snowballSchedule['totalInterest'],
                'order' => $snowballOrder,
                'savings' => round($snowballSavings, 2),
            ],
            'avalanche' => [
                'months' => $avalancheSchedule['months'],
                'totalInterest' => $avalancheSchedule['totalInterest'],
                'order' => $avalancheOrder,
                'savings' => round($avalancheSavings, 2),
            ],
            'custom' => [
                'months' => $customSchedule['months'],
                'totalInterest' => $customSchedule['totalInterest'],
                'order' => $customOrder,
                'savings' => round($customSavings, 2),
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
            $balance = $debt->balance;
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
            $balance = $debt->balance;
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
