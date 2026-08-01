<?php

namespace App\Services;

use App\Cache\DebtCacheVersion;
use App\Contracts\DebtOrderingStrategy;
use App\Models\Debt;
use App\Services\DebtOrdering\AvalancheStrategy;
use App\Services\DebtOrdering\CustomStrategy;
use App\Services\DebtOrdering\SnowballStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DebtCalculationService
{
    private const CACHE_TTL_HOURS = 24;

    /**
     * Registered debt ordering strategies.
     *
     * @var array<string, DebtOrderingStrategy>
     */
    protected array $strategies = [];

    public function __construct(
        protected PaymentService $paymentService
    ) {
        $this->registerDefaultStrategies();
    }

    /**
     * Register the default debt ordering strategies.
     */
    protected function registerDefaultStrategies(): void
    {
        $this->registerStrategy(new SnowballStrategy);
        $this->registerStrategy(new AvalancheStrategy);
        $this->registerStrategy(new CustomStrategy);
    }

    /**
     * Register a debt ordering strategy.
     */
    public function registerStrategy(DebtOrderingStrategy $strategy): self
    {
        $this->strategies[$strategy->getKey()] = $strategy;

        return $this;
    }

    /**
     * Get a registered strategy by key.
     *
     * @throws \InvalidArgumentException If strategy is not found
     */
    public function getStrategy(string $key): DebtOrderingStrategy
    {
        if (! isset($this->strategies[$key])) {
            throw new \InvalidArgumentException("Unknown debt ordering strategy: {$key}");
        }

        return $this->strategies[$key];
    }

    /**
     * Get all registered strategies.
     *
     * @return array<string, DebtOrderingStrategy>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Order debts using the specified strategy.
     *
     * @param  string  $strategy  Strategy key (e.g., 'snowball', 'avalanche', 'custom')
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts  Collection of Debt models
     * @return \Illuminate\Support\Collection<int, \App\Models\Debt> Ordered collection
     *
     * @throws \InvalidArgumentException If strategy is not found
     */
    public function order(string $strategy, Collection $debts): Collection
    {
        return $this->getStrategy($strategy)->order($debts);
    }

    /**
     * Order debts by lowest balance first (Snowball method).
     * This method provides psychological wins by paying off smaller debts first.
     *
     * @deprecated Use order('snowball', $debts) instead
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts  Collection of Debt models
     * @return \Illuminate\Support\Collection<int, \App\Models\Debt> Ordered collection with lowest balance first
     */
    public function orderBySnowball(Collection $debts): Collection
    {
        return $this->order('snowball', $debts);
    }

    /**
     * Order debts by highest interest rate first (Avalanche method).
     * This method minimizes total interest paid over time.
     *
     * @deprecated Use order('avalanche', $debts) instead
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts  Collection of Debt models
     * @return \Illuminate\Support\Collection<int, \App\Models\Debt> Ordered collection with highest interest rate first
     */
    public function orderByAvalanche(Collection $debts): Collection
    {
        return $this->order('avalanche', $debts);
    }

    /**
     * Order debts by custom priority order set by the user.
     * This method allows users to choose their own repayment priority.
     *
     * @deprecated Use order('custom', $debts) instead
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts  Collection of Debt models
     * @return \Illuminate\Support\Collection<int, \App\Models\Debt> Ordered collection by custom_priority_order (ascending)
     */
    public function orderByCustom(Collection $debts): Collection
    {
        return $this->order('custom', $debts);
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

    /**
     * Get actual payments organized by month and debt name.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts
     * @return array<int, array<string, array<string, mixed>>>
     */
    protected function getActualPaymentsByMonth(Collection $debts): array
    {
        $payments = [];
        $cumulativePrincipal = [];

        foreach ($debts as $debt) {
            $debtName = $debt->name;

            // Initialize cumulative principal with any reconciliation adjustments
            // Reconciliation adjustments establish a new baseline and their principal
            // must be included when calculating remaining balances
            $reconciliationPrincipal = $debt->payments
                ->where('is_reconciliation_adjustment', true)
                ->sum('principal_paid');

            $cumulativePrincipal[$debtName] = $reconciliationPrincipal;

            // Sort payments by month_number to ensure correct cumulative calculation
            // Exclude reconciliation adjustments (month_number = NULL) as they don't represent monthly payments
            /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $sortedPayments */
            $sortedPayments = $debt->payments
                ->filter(fn (\App\Models\Payment $payment) => $payment->month_number !== null)
                ->sortBy('month_number');

            foreach ($sortedPayments as $payment) {
                /** @var \App\Models\Payment $payment */
                $monthNumber = $payment->month_number;
                $actualAmount = $payment->actual_amount;
                $principalPaid = $payment->principal_paid ?? $actualAmount;

                // Track cumulative principal paid up to and including this month
                $cumulativePrincipal[$debtName] += $principalPaid;

                if (! isset($payments[$monthNumber])) {
                    $payments[$monthNumber] = [];
                }

                $payments[$monthNumber][$debtName] = [
                    'actual_amount' => $actualAmount,
                    'principal_paid' => $principalPaid,
                    'cumulative_principal_paid' => $cumulativePrincipal[$debtName],
                    'is_reconciliation' => $payment->is_reconciliation_adjustment ?? false,
                    'notes' => $payment->notes,
                ];
            }
        }

        return $payments;
    }

    /**
     * Generate a cache key for the payment schedule.
     *
     * The current date is part of the key because the schedule is built from
     * now(): a plan cached yesterday would otherwise be served today with
     * every month shifted by one day.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts
     */
    protected function getPaymentScheduleCacheKey(Collection $debts, float $extraPayment, string $strategy): string
    {
        $debtData = $this->getDebtDataForCacheKey($debts);

        return DebtCacheVersion::key('payment_schedule:'.md5(json_encode([
            'debts' => $debtData,
            'extra_payment' => $extraPayment,
            'strategy' => $strategy,
            'date' => now()->format('Y-m-d'),
        ])));
    }

    /**
     * Generate a cache key for strategy comparison.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts
     */
    protected function getStrategyComparisonCacheKey(Collection $debts, float $extraPayment): string
    {
        $debtData = $this->getDebtDataForCacheKey($debts);

        return DebtCacheVersion::key('strategy_comparison:'.md5(json_encode([
            'debts' => $debtData,
            'extra_payment' => $extraPayment,
            'date' => now()->format('Y-m-d'),
        ])));
    }

    /**
     * Get debt data array for cache key generation.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts
     * @return array<int, array<string, mixed>>
     */
    protected function getDebtDataForCacheKey(Collection $debts): array
    {
        return $debts->map(fn (Debt $debt) => [
            'id' => $debt->id,
            'balance' => $debt->balance,
            'interest_rate' => $debt->interest_rate,
            'minimum_payment' => $debt->minimum_payment,
            'custom_priority_order' => $debt->custom_priority_order,
            'payments_hash' => $debt->payments->count().'_'.$debt->payments->max('updated_at'),
        ])->toArray();
    }

    /**
     * Generate a cache key for minimum payment calculations.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts
     */
    protected function getMinimumPaymentsCacheKey(Collection $debts, string $type): string
    {
        // For minimum payments, we only need debt financial data (not payments or custom order).
        // No date in the key: the result holds no calendar dates.
        $debtData = $debts->map(fn (Debt $debt) => [
            'id' => $debt->id,
            'balance' => $debt->balance,
            'interest_rate' => $debt->interest_rate,
            'minimum_payment' => $debt->minimum_payment,
        ])->toArray();

        return DebtCacheVersion::key('minimum_payments:'.$type.':'.md5(json_encode($debtData)));
    }

    /**
     * Clear all calculation caches (payment schedules, strategy comparisons, minimum payments, and progress data).
     */
    public static function clearAllCalculationCaches(): void
    {
        DebtCacheVersion::bump();
    }

    /**
     * Generate a payment schedule based on the selected strategy.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts
     * @param  int  $actualPaymentMonthOffset  Offset to align schedule months with actual payment month_numbers
     * @return array<string, mixed>
     */
    public function generatePaymentSchedule(Collection $debts, float $extraPayment, string $strategy = 'avalanche', int $actualPaymentMonthOffset = 0): array
    {
        if ($debts->isEmpty()) {
            return [
                'months' => 0,
                'totalInterest' => 0.00,
                'payoffDate' => now()->format('Y-m-d'),
                'schedule' => [],
            ];
        }

        $cacheKey = $this->getPaymentScheduleCacheKey($debts, $extraPayment, $strategy).':'.$actualPaymentMonthOffset;

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($debts, $extraPayment, $strategy, $actualPaymentMonthOffset) {
            return $this->calculatePaymentSchedule($debts, $extraPayment, $strategy, $actualPaymentMonthOffset);
        });
    }

    /**
     * Calculate the payment schedule (uncached).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts
     * @param  int  $actualPaymentMonthOffset  Offset to align schedule months with actual payment month_numbers
     * @return array<string, mixed>
     */
    protected function calculatePaymentSchedule(Collection $debts, float $extraPayment, string $strategy, int $actualPaymentMonthOffset = 0): array
    {
        $orderedDebts = match ($strategy) {
            'snowball' => $this->orderBySnowball($debts),
            'custom' => $this->orderByCustom($debts),
            default => $this->orderByAvalanche($debts),
        };

        // Get all actual payments organized by month and debt
        $actualPayments = $this->getActualPaymentsByMonth($debts);

        $remainingDebts = $orderedDebts->map(function ($debt) use ($actualPayments, $actualPaymentMonthOffset) {
            // When offset > 0, we're projecting from current state (historical months handled separately)
            // Always use current balance since it already reflects historical payments
            if ($actualPaymentMonthOffset > 0) {
                $startingBalance = $debt->balance;
            } else {
                // Check if this debt has reconciliation adjustments
                $hasReconciliation = $debt->payments()
                    ->where('is_reconciliation_adjustment', true)
                    ->exists();

                // If reconciled: Use current balance (reconciliation sets the truth from this point forward)
                // If not reconciled but has payments: Replay from original_balance to simulate payment history
                // If no payments: Use current balance for accurate projections
                if ($hasReconciliation) {
                    // Reconciliation means the current balance IS the truth - don't replay from original
                    $startingBalance = $debt->balance;
                } else {
                    // Check if there are actual payments for this debt
                    $hasActualPaymentsForDebt = collect($actualPayments)->contains(function ($monthPayments) use ($debt) {
                        return isset($monthPayments[$debt->name]);
                    });

                    $startingBalance = $hasActualPaymentsForDebt
                        ? ($debt->original_balance ?? $debt->balance)
                        : $debt->balance;
                }
            }

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
            $baseMonth = now()->addMonths($month - 1);
            $monthDate = $baseMonth->copy()->day(min($dueDay, $baseMonth->daysInMonth));

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

            $actualPaymentMonth = $month + $actualPaymentMonthOffset;
            $hasActualPayments = isset($actualPayments[$actualPaymentMonth]);

            // Calculate how much of the extra payment budget has already been used this month
            // This handles the case where user changes strategy after paying one debt with extra
            $extraPaymentUsedThisMonth = 0;
            if ($hasActualPayments) {
                foreach ($actualPayments[$actualPaymentMonth] as $debtName => $paymentData) {
                    $debtMinimum = collect($remainingDebts)->firstWhere('name', $debtName)['minimum_payment'] ?? 0;
                    $extraPaid = max(0, $paymentData['actual_amount'] - $debtMinimum);
                    $extraPaymentUsedThisMonth += $extraPaid;
                }
            }

            // Calculate remaining extra budget for this month (never negative)
            $availableExtraThisMonth = max(0, $availableExtraPayment - $extraPaymentUsedThisMonth);

            // Stafett-flagg: prioritet ruller videre til neste gjeld i SAMME måned
            // når en prioritert gjeld blir helt nedbetalt, slik at ubrukt ekstra
            // ikke "forsvinner" til måneden etter.
            $priorityActive = true;

            foreach ($remainingDebts as $index => $debt) {
                if ($debt['balance'] <= 0.01) {
                    continue;
                }

                $isPriority = $priorityActive;
                $debtName = $debt['name'];
                $hasActualPaymentForDebt = $hasActualPayments && isset($actualPayments[$actualPaymentMonth][$debtName]);

                $interest = $this->calculateMonthlyInterest($debt['balance'], $debt['interest_rate']);

                $isReconciliation = false;
                $paymentNotes = null;
                $cumulativePrincipalPaid = 0;

                if ($hasActualPaymentForDebt) {
                    $paymentData = $actualPayments[$actualPaymentMonth][$debtName];
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
                    $extraForThisDebt = $isPriority ? $availableExtraThisMonth : 0;
                    $totalPayment = $minimumPayment + $extraForThisDebt;
                }

                if ($hasActualPaymentForDebt) {
                    // Faktiske betalinger er virkelighet og skal ikke klippes mot
                    // simulert saldo: når planen projiseres fra dagens saldo er
                    // betalingen allerede trukket fra, og klipping ville telle den
                    // dobbelt og feilaktig markere gjelden som nedbetalt.
                    // Gjenstående beregnes som i updateDebtBalances():
                    // original_balance - SUM(principal_paid), inkl. avstemminger.
                    $newBalance = max(0, round($debt['original_balance'] - $cumulativePrincipalPaid, 2));
                } else {
                    $maxPayment = $debt['balance'] + $interest;
                    $totalPayment = min($totalPayment, $maxPayment);

                    if ($maxPayment - $totalPayment > 0 && $maxPayment - $totalPayment <= 1) {
                        $totalPayment = $maxPayment;
                    }

                    // Trekk fra availableExtraThisMonth basert på FAKTISK brukt ekstra
                    // (etter kapping) — slik at differansen kan rulle til neste gjeld.
                    $actualExtraUsed = max(0, $totalPayment - $minimumPayment);
                    $extraForThisDebt = round($actualExtraUsed, 2);
                    $availableExtraThisMonth = max(0, $availableExtraThisMonth - $actualExtraUsed);

                    if ($totalPayment >= $maxPayment - 0.01) {
                        $newBalance = 0;
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

                // Stafett: hvis prioritert gjeld ikke ble nedbetalt denne iterasjonen,
                // stopper rolloveren her — videre gjelder får 0 ekstra.
                // Hvis den BLE nedbetalt, forblir $priorityActive = true slik at
                // neste ikke-nedbetalte gjeld arver prioriteten i samme måned.
                if ($isPriority && $newBalance > 0.01) {
                    $priorityActive = false;
                }
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

        // Use the last schedule entry's date as the payoff date (when the final payment is made)
        if (! empty($schedule)) {
            $lastEntry = end($schedule);
            $payoffDate = $lastEntry['date'];
            $actualMonths = count($schedule);
        } else {
            $payoffDate = now()->format('Y-m-d');
            $actualMonths = 0;
        }

        return [
            'months' => $actualMonths,
            'totalInterest' => round($totalInterest, 2),
            'payoffDate' => $payoffDate,
            'schedule' => $schedule,
        ];
    }

    /**
     * Compare snowball, avalanche, and custom strategies.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts  Collection of Debt models
     * @param  float  $extraPayment  Extra monthly payment beyond minimums
     * @return array<string, array<string, mixed>> Comparison of all strategies with savings vs minimum payments
     */
    public function compareStrategies(Collection $debts, float $extraPayment): array
    {
        if ($debts->isEmpty()) {
            return [
                'snowball' => ['months' => 0, 'totalInterest' => 0.0, 'order' => [], 'savings' => 0.0],
                'avalanche' => ['months' => 0, 'totalInterest' => 0.0, 'order' => [], 'savings' => 0.0],
                'custom' => ['months' => 0, 'totalInterest' => 0.0, 'order' => [], 'savings' => 0.0],
            ];
        }

        $cacheKey = $this->getStrategyComparisonCacheKey($debts, $extraPayment);

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($debts, $extraPayment) {
            return $this->calculateStrategyComparison($debts, $extraPayment);
        });
    }

    /**
     * Calculate strategy comparison (uncached).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts
     * @return array<string, array<string, mixed>>
     */
    protected function calculateStrategyComparison(Collection $debts, float $extraPayment): array
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
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts  Collection of Debt models
     * @return int Maximum months to pay off all debts (the longest-running debt)
     */
    public function calculateMinimumPaymentsOnly(Collection $debts): int
    {
        if ($debts->isEmpty()) {
            return 0;
        }

        $cacheKey = $this->getMinimumPaymentsCacheKey($debts, 'months');

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($debts) {
            return $this->performCalculateMinimumPaymentsOnly($debts);
        });
    }

    /**
     * Perform the actual minimum payments calculation (uncached).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts
     */
    protected function performCalculateMinimumPaymentsOnly(Collection $debts): int
    {
        $maxMonths = 0;

        foreach ($debts as $debt) {
            $balance = $debt->balance;
            $interestRate = $debt->interest_rate;
            $minimumPayment = $debt->minimum_payment;

            // Skip if no minimum payment set or if balance is already zero
            if ($minimumPayment === null) {
                continue;
            }

            if ($balance <= 0.01) {
                continue;
            }

            if ($minimumPayment <= 0) {
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
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts  Collection of Debt models
     * @return float Total interest paid across all debts
     */
    public function calculateMinimumPaymentsInterest(Collection $debts): float
    {
        if ($debts->isEmpty()) {
            return 0.0;
        }

        $cacheKey = $this->getMinimumPaymentsCacheKey($debts, 'interest');

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($debts) {
            return $this->performCalculateMinimumPaymentsInterest($debts);
        });
    }

    /**
     * Perform the actual minimum payments interest calculation (uncached).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts
     */
    protected function performCalculateMinimumPaymentsInterest(Collection $debts): float
    {
        $totalInterest = 0.0;

        foreach ($debts as $debt) {
            $balance = $debt->balance;
            $interestRate = $debt->interest_rate;
            $minimumPayment = $debt->minimum_payment;

            // Skip if no minimum payment set or if balance is already zero
            if ($minimumPayment === null) {
                continue;
            }

            if ($balance <= 0.01) {
                continue;
            }

            if ($minimumPayment <= 0) {
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
