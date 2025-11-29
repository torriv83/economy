<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Debt;
use Illuminate\Support\Collection;

class AccelerationService
{
    /**
     * YNAB goal types that indicate savings/sinking fund behavior.
     * TB = Target Category Balance
     * TBD = Target Category Balance by Date
     * MF = Monthly Funding (regular savings contribution).
     *
     * @var array<string>
     */
    protected array $savingsGoalTypes = ['TB', 'TBD', 'MF'];

    /**
     * YNAB goal type for essential spending (bills, needs).
     */
    protected string $essentialGoalType = 'NEED';

    /**
     * Minimum balance threshold for savings tier (avoid noise from small amounts).
     */
    protected float $savingsThreshold = 1000.0;

    /**
     * Balance:Activity ratio threshold to detect savings-like behavior.
     * If balance is 10x+ higher than monthly activity, it behaves like savings.
     */
    protected float $savingsRatioThreshold = 10.0;

    public function __construct(
        protected YnabService $ynabService,
        protected DebtCalculationService $calculationService,
        protected PayoffSettingsService $settingsService
    ) {}

    /**
     * Get acceleration opportunities for a specific debt.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getOpportunities(Debt $debt): Collection
    {
        $opportunities = collect();

        // Fetch YNAB data (throws on API errors - component handles display)
        $budgetSummary = $this->ynabService->fetchBudgetSummary();
        $categories = $this->ynabService->fetchCategories();

        // Tier 1: Ready to Assign
        if ($budgetSummary['ready_to_assign'] > 0) {
            $opportunities->push($this->createOpportunity(
                source: 'ready_to_assign',
                name: __('app.ready_to_assign'),
                amount: $budgetSummary['ready_to_assign'],
                type: 'one_time',
                tier: 1,
                debt: $debt
            ));
        }

        // Tier 1: Overfunded categories
        $overfunded = $categories->filter(fn ($cat) => $cat['is_overfunded']);
        foreach ($overfunded as $category) {
            $excessAmount = $category['balance'] - $category['goal_target'];
            if ($excessAmount > 0) {
                $opportunities->push($this->createOpportunity(
                    source: 'category',
                    name: $category['name'],
                    amount: $excessAmount,
                    type: 'one_time',
                    tier: 1,
                    debt: $debt,
                    groupName: $category['group_name']
                ));
            }
        }

        // Tier 1: Categories with balance but no goal
        $noGoalWithBalance = $categories->filter(
            fn ($cat) => ! $cat['has_goal'] && $cat['balance'] > 0 && ! $this->isSavingsCategory($cat)
        );
        foreach ($noGoalWithBalance as $category) {
            $opportunities->push($this->createOpportunity(
                source: 'category',
                name: $category['name'],
                amount: $category['balance'],
                type: 'one_time',
                tier: 1,
                debt: $debt,
                groupName: $category['group_name']
            ));
        }

        // Tier 2: Discretionary categories
        $discretionary = $categories->filter(
            fn ($cat) => $this->isDiscretionaryCategory($cat) && $cat['balance'] > 0
        );
        foreach ($discretionary as $category) {
            // Skip if already added as overfunded or no-goal
            if ($opportunities->contains(fn ($o) => $o['name'] === $category['name'])) {
                continue;
            }

            $opportunities->push($this->createOpportunity(
                source: 'category',
                name: $category['name'],
                amount: $category['balance'],
                type: 'one_time',
                tier: 2,
                debt: $debt,
                groupName: $category['group_name']
            ));
        }

        // Tier 3: Savings categories (with warning)
        // Uses smart detection based on YNAB goal types and balance:activity ratios
        $savings = $categories->filter(
            fn ($cat) => $this->isSavingsCategory($cat)
        );
        foreach ($savings as $category) {
            $opportunities->push($this->createOpportunity(
                source: 'savings',
                name: $category['name'],
                amount: $category['balance'],
                type: 'one_time',
                tier: 3,
                debt: $debt,
                groupName: $category['group_name'],
                warning: __('app.savings_redirect_warning')
            ));
        }

        // Sort by tier, then by impact (descending)
        return $opportunities
            ->sortBy('tier')
            ->sortByDesc('impact.interest_saved')
            ->values();
    }

    /**
     * Create an opportunity with impact calculation.
     *
     * @return array<string, mixed>
     */
    protected function createOpportunity(
        string $source,
        string $name,
        float $amount,
        string $type,
        int $tier,
        Debt $debt,
        ?string $groupName = null,
        ?string $warning = null
    ): array {
        $impact = $this->calculateImpact($debt, $amount);

        return [
            'source' => $source,
            'name' => $name,
            'group_name' => $groupName,
            'amount' => $amount,
            'type' => $type,
            'tier' => $tier,
            'impact' => $impact,
            'warning' => $warning,
        ];
    }

    /**
     * Calculate the impact of redirecting an amount to debt as a ONE-TIME payment.
     *
     * This reflects the reality that YNAB category balances are available once -
     * when you redirect them to debt, they're gone from the category.
     *
     * @return array<string, mixed>
     */
    protected function calculateImpact(Debt $debt, float $amount): array
    {
        $debts = Debt::with('payments')->get();
        $extraPayment = $this->settingsService->getExtraPayment();
        $strategy = $this->settingsService->getStrategy();

        // Current scenario
        $currentSchedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $extraPayment,
            $strategy
        );

        // What-if scenario: Apply amount as ONE-TIME payment to this specific debt
        // Start fresh from reduced current balance (no payment history replay)
        $modifiedDebts = $debts->map(function (Debt $d) use ($debt, $amount) {
            if ($d->id === $debt->id) {
                $reducedBalance = max(0, $d->balance - $amount);
                $clone = $d->replicate();
                $clone->id = $d->id;
                $clone->balance = $reducedBalance;
                // Set original_balance to match so calculation starts fresh from reduced balance
                $clone->original_balance = $reducedBalance;
                // Clear payments to prevent replay from old original_balance
                $clone->setRelation('payments', collect());

                return $clone;
            }

            return $d;
        });

        $whatIfSchedule = $this->calculationService->generatePaymentSchedule(
            $modifiedDebts,
            $extraPayment,
            $strategy
        );

        // Find when this specific debt is paid off
        // For what-if, we need to know the modified starting balance to detect immediate payoff
        $whatIfStartingBalance = max(0, $debt->balance - $amount);

        $currentPayoffMonth = $this->findDebtPayoffMonth($currentSchedule['schedule'], $debt->name, $debt->balance);
        $whatIfPayoffMonth = $this->findDebtPayoffMonth($whatIfSchedule['schedule'], $debt->name, $whatIfStartingBalance);

        $monthsSaved = max(0, $currentPayoffMonth - $whatIfPayoffMonth);
        $interestSaved = max(0, $currentSchedule['totalInterest'] - $whatIfSchedule['totalInterest']);

        return [
            'months_saved' => $monthsSaved,
            'weeks_saved' => $monthsSaved < 1 ? (int) round($monthsSaved * 4) : 0,
            'interest_saved' => $interestSaved,
            'new_payoff_date' => now()->addMonths($whatIfPayoffMonth)->format('Y-m-d'),
        ];
    }

    /**
     * Find the month number when a specific debt is paid off.
     *
     * @param  array<int, array<string, mixed>>  $schedule
     * @param  float|null  $startingBalance  The debt's balance at start of schedule (for detecting immediate payoff)
     */
    protected function findDebtPayoffMonth(array $schedule, string $debtName, ?float $startingBalance = null): int
    {
        // If starting balance is 0 or near 0, debt is already paid off (month 0)
        if ($startingBalance !== null && $startingBalance <= 0.01) {
            return 0;
        }

        // Check if debt appears in schedule at all
        $debtAppearsInSchedule = false;

        foreach ($schedule as $month) {
            foreach ($month['payments'] as $payment) {
                if ($payment['name'] === $debtName) {
                    $debtAppearsInSchedule = true;
                    if ($payment['remaining'] <= 0.01) {
                        return $month['month'];
                    }
                }
            }
        }

        // If debt never appeared in schedule and we know it has a starting balance,
        // it might have been paid off before schedule started (e.g., one-time payment covered it)
        if (! $debtAppearsInSchedule && $startingBalance !== null) {
            return 0;
        }

        return count($schedule);
    }

    /**
     * Check if a category looks like discretionary spending based on YNAB data.
     *
     * Discretionary categories are detected by:
     * - Having available balance > 0
     * - Money sitting unused (zero or low activity relative to balance)
     * - NOT having a savings-type goal (TB, TBD, MF)
     *
     * The key insight: if you have balance but zero/low activity, that money
     * could potentially be redirected to debt repayment.
     *
     * @param  array<string, mixed>  $category
     */
    protected function isDiscretionaryCategory(array $category): bool
    {
        // Must have balance to redirect
        if ($category['balance'] <= 0) {
            return false;
        }

        $goalType = $category['goal_type'] ?? null;
        $activity = abs($category['activity'] ?? 0);
        $balance = $category['balance'];

        // Skip if it's a savings-type goal (handled by tier 3)
        if ($goalType !== null && in_array($goalType, $this->savingsGoalTypes, true)) {
            return false;
        }

        // Key heuristic: if balance is sitting with zero/low activity,
        // it's potentially discretionary (could be redirected)
        // This catches "fun money" categories that haven't been spent yet
        if ($activity === 0.0) {
            return true;
        }

        // Low activity relative to balance (< 50% used) suggests excess
        if ($balance > 0 && $activity < $balance * 0.5) {
            return true;
        }

        // Overfunded categories have excess that could be redirected
        return $category['is_overfunded'];
    }

    /**
     * Check if a category behaves like savings based on YNAB data.
     *
     * Savings categories are detected by:
     * - Having a savings-type goal (TB, TBD, MF)
     * - OR having a very high balance relative to activity (behaves like savings)
     *
     * @param  array<string, mixed>  $category
     */
    protected function isSavingsCategory(array $category): bool
    {
        $goalType = $category['goal_type'] ?? null;
        $balance = $category['balance'];
        $activity = abs($category['activity'] ?? 0);

        // Must meet minimum threshold
        if ($balance < $this->savingsThreshold) {
            return false;
        }

        // Explicit savings goal types
        if ($goalType !== null && in_array($goalType, $this->savingsGoalTypes, true)) {
            return true;
        }

        // Behavioral detection: high balance with low/no activity = savings-like
        // (money accumulating rather than being spent)
        if ($activity > 0 && ($balance / $activity) >= $this->savingsRatioThreshold) {
            return true;
        }

        // Very high balance with zero activity is definitely savings-like
        if ($activity === 0.0 && $balance >= $this->savingsThreshold * 5) {
            return true;
        }

        return false;
    }
}
