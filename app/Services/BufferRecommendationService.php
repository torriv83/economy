<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Debt;
use Illuminate\Support\Collection;

class BufferRecommendationService
{
    /**
     * @param  YnabService  $ynabService  Reserved for future YNAB-direct operations
     * @param  AccelerationService  $accelerationService  Reserved for future acceleration calculations
     */
    public function __construct(
        private readonly YnabService $ynabService,
        private readonly AccelerationService $accelerationService,
        private readonly DebtCalculationService $calculationService,
        private readonly SettingsService $settingsService
    ) {}

    private const MINIMUM_BUFFER_PERCENTAGE = 50.0;

    /**
     * Get smart financial recommendations based on buffer status, debt situation, and available funds.
     *
     * Priority order:
     * 1. Pay period not covered → Fund NEED categories first
     * 2. Buffer below 50% → Build minimum safety net
     * 3. High-interest debt (>10%) → Pay down mathematically optimal
     * 4. Dedicated categories below target → Fill planned expenses
     * 5. Buffer below 100% → Complete safety net
     * 6. Remaining debt → Pay down using strategy
     * 7. Everything covered → You're in good shape!
     *
     * @param  array{emergency_buffer: array{amount: float, target: float, percentage: float}, dedicated_categories: array<int, array{name: string, balance: float, target: float, percentage: float}>, pay_period: array{funded: float, needed: float, is_covered: bool}, status: string}  $bufferStatus
     * @param  float  $readyToAssign  Available funds from YNAB "Ready to Assign"
     * @return array<int, array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>, action?: array<string, mixed>}>
     */
    public function getRecommendations(array $bufferStatus, float $readyToAssign = 0.0): array
    {
        if (! $this->settingsService->isYnabConfigured()) {
            return [];
        }

        return $this->getSmartRecommendation($bufferStatus, $readyToAssign);
    }

    /**
     * Get the single most important recommendation based on financial priorities.
     *
     * @param  array{emergency_buffer: array{amount: float, target: float, percentage: float}, dedicated_categories: array<int, array{name: string, balance: float, target: float, percentage: float}>, pay_period: array{funded: float, needed: float, is_covered: bool}, status: string}  $bufferStatus
     * @return array<int, array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>}>
     */
    private function getSmartRecommendation(array $bufferStatus, float $readyToAssign): array
    {
        $recommendations = [];

        // Always show pay period status first
        $recommendations[] = $this->getPayPeriodRecommendation($bufferStatus);

        // Prioritet 1: Lønnsperiode ikke dekket - STOPP HER
        if (! $bufferStatus['pay_period']['is_covered']) {
            return $recommendations;
        }

        // Prioritet 2: Buffer under 50% (minimum sikkerhet)
        if ($bufferStatus['emergency_buffer']['percentage'] < self::MINIMUM_BUFFER_PERCENTAGE) {
            $recommendations[] = $this->getMinimumBufferRecommendation($bufferStatus, $readyToAssign);

            return $recommendations;
        }

        // Prioritet 3: Høyrentegjeld (>10%)
        $debts = Debt::with('payments')->get();
        $highInterestDebt = $this->getHighInterestDebt($debts);
        if ($highInterestDebt !== null) {
            $recommendations[] = $this->getHighInterestDebtRecommendation($highInterestDebt, $debts, $readyToAssign);

            return $recommendations;
        }

        // Prioritet 4: Dedikerte kategorier under mål
        $lowCategories = $this->getLowDedicatedCategories($bufferStatus['dedicated_categories']);
        if (! empty($lowCategories)) {
            // Sort by percentage (lowest first) and take the most urgent
            usort($lowCategories, fn ($a, $b) => $a['percentage'] <=> $b['percentage']);
            $mostUrgentCategory = $lowCategories[0];
            $recommendations[] = $this->getDedicatedCategoryRecommendation($mostUrgentCategory, $readyToAssign);

            return $recommendations;
        }

        // Prioritet 5: Buffer under 100%
        if ($bufferStatus['emergency_buffer']['percentage'] < 100) {
            $recommendations[] = $this->getEmergencyBufferRecommendation($bufferStatus, $readyToAssign);

            return $recommendations;
        }

        // Prioritet 6: Resterende gjeld
        if (! $debts->isEmpty()) {
            $recommendations[] = $this->getDebtRecommendation($debts, $readyToAssign);

            return $recommendations;
        }

        // Prioritet 7: Alt er perfekt!
        $recommendations[] = [
            'priority' => 7,
            'type' => 'all_good',
            'icon' => 'sparkles',
            'status' => 'success',
            'title' => 'buffer.all_good_title',
            'description' => 'buffer.all_good_description',
            'params' => [
                'buffer_amount' => round($bufferStatus['emergency_buffer']['amount'], 0),
            ],
        ];

        return $recommendations;
    }

    /**
     * Get pay period (operational buffer) recommendation.
     *
     * @param  array{emergency_buffer: array{amount: float, target: float, percentage: float}, dedicated_categories: array<int, array{name: string, balance: float, target: float, percentage: float}>, pay_period: array{funded: float, needed: float, is_covered: bool}, status: string}  $bufferStatus
     * @return array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>}
     */
    private function getPayPeriodRecommendation(array $bufferStatus): array
    {
        if ($bufferStatus['pay_period']['is_covered']) {
            return [
                'priority' => 1,
                'type' => 'pay_period',
                'icon' => 'check-circle',
                'status' => 'success',
                'title' => 'buffer.pay_period_covered_title',
                'description' => 'buffer.pay_period_covered_description',
                'params' => [
                    'funded' => round($bufferStatus['pay_period']['funded'], 2),
                    'needed' => round($bufferStatus['pay_period']['needed'], 2),
                ],
            ];
        }

        $shortfall = $bufferStatus['pay_period']['needed'] - $bufferStatus['pay_period']['funded'];

        return [
            'priority' => 1,
            'type' => 'pay_period',
            'icon' => 'exclamation-triangle',
            'status' => 'warning',
            'title' => 'buffer.pay_period_not_covered_title',
            'description' => 'buffer.pay_period_not_covered_description',
            'params' => [
                'shortfall' => round($shortfall, 2),
                'funded' => round($bufferStatus['pay_period']['funded'], 2),
                'needed' => round($bufferStatus['pay_period']['needed'], 2),
            ],
        ];
    }

    /**
     * Get minimum buffer recommendation (for buffer under 50%).
     *
     * @param  array{emergency_buffer: array{amount: float, target: float, percentage: float}, dedicated_categories: array<int, array{name: string, balance: float, target: float, percentage: float}>, pay_period: array{funded: float, needed: float, is_covered: bool}, status: string}  $bufferStatus
     * @return array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>}
     */
    private function getMinimumBufferRecommendation(array $bufferStatus, float $readyToAssign): array
    {
        $target50 = $bufferStatus['emergency_buffer']['target'] * (self::MINIMUM_BUFFER_PERCENTAGE / 100);
        $shortfall = $target50 - $bufferStatus['emergency_buffer']['amount'];
        $recommendedAmount = $readyToAssign > 0 ? min($readyToAssign, $shortfall) : $shortfall;

        return [
            'priority' => 2,
            'type' => 'minimum_buffer',
            'icon' => 'shield-exclamation',
            'status' => 'warning',
            'title' => 'buffer.minimum_buffer_title',
            'description' => $readyToAssign > 0 ? 'buffer.minimum_buffer_description_with_funds' : 'buffer.minimum_buffer_description',
            'params' => [
                'current_amount' => round($bufferStatus['emergency_buffer']['amount'], 0),
                'target_50' => round($target50, 0),
                'shortfall' => round($shortfall, 0),
                'percentage' => round($bufferStatus['emergency_buffer']['percentage'], 0),
                'ready_to_assign' => round($readyToAssign, 0),
                'recommended_amount' => round($recommendedAmount, 0),
            ],
        ];
    }

    /**
     * Get emergency buffer recommendation (for buffer between 50-100%).
     *
     * @param  array{emergency_buffer: array{amount: float, target: float, percentage: float}, dedicated_categories: array<int, array{name: string, balance: float, target: float, percentage: float}>, pay_period: array{funded: float, needed: float, is_covered: bool}, status: string}  $bufferStatus
     * @return array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>}
     */
    private function getEmergencyBufferRecommendation(array $bufferStatus, float $readyToAssign): array
    {
        $shortfall = $bufferStatus['emergency_buffer']['target'] - $bufferStatus['emergency_buffer']['amount'];
        $recommendedAmount = $readyToAssign > 0 ? min($readyToAssign, $shortfall) : $shortfall;

        return [
            'priority' => 5,
            'type' => 'emergency_buffer',
            'icon' => 'shield',
            'status' => 'action',
            'title' => 'buffer.emergency_buffer_low_title',
            'description' => $readyToAssign > 0 ? 'buffer.emergency_buffer_low_description_with_funds' : 'buffer.emergency_buffer_low_description',
            'params' => [
                'current_amount' => round($bufferStatus['emergency_buffer']['amount'], 0),
                'target_amount' => round($bufferStatus['emergency_buffer']['target'], 0),
                'shortfall' => round($shortfall, 0),
                'percentage' => round($bufferStatus['emergency_buffer']['percentage'], 0),
                'ready_to_assign' => round($readyToAssign, 0),
                'recommended_amount' => round($recommendedAmount, 0),
            ],
        ];
    }

    /**
     * Get the debt with the highest interest rate above threshold.
     *
     * @param  Collection<int, Debt>  $debts
     */
    private function getHighInterestDebt(Collection $debts): ?Debt
    {
        $threshold = $this->settingsService->getHighInterestThreshold();
        $highInterestDebts = $debts->filter(fn (Debt $d) => $d->interest_rate >= $threshold);

        if ($highInterestDebts->isEmpty()) {
            return null;
        }

        return $highInterestDebts->sortByDesc('interest_rate')->first();
    }

    /**
     * Get recommendation for high-interest debt.
     *
     * @param  Collection<int, Debt>  $debts
     * @return array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>}
     */
    private function getHighInterestDebtRecommendation(Debt $debt, Collection $debts, float $readyToAssign): array
    {
        $totalDebt = (float) $debts->sum('balance');
        $impact = $readyToAssign > 0 ? $this->calculateDebtImpact($debt, $readyToAssign) : null;

        return [
            'priority' => 3,
            'type' => 'high_interest_debt',
            'icon' => 'fire',
            'status' => 'action',
            'title' => 'buffer.high_interest_debt_title',
            'description' => $readyToAssign > 0 ? 'buffer.high_interest_debt_description_with_funds' : 'buffer.high_interest_debt_description',
            'params' => [
                'debt_name' => $debt->name,
                'debt_balance' => round($debt->balance, 0),
                'interest_rate' => round($debt->interest_rate, 1),
                'total_debt' => round($totalDebt, 0),
                'debt_count' => $debts->count(),
                'ready_to_assign' => round($readyToAssign, 0),
                'interest_saved' => $impact ? round($impact['interest_saved'], 0) : 0,
                'months_saved' => $impact ? $impact['months_saved'] : 0,
            ],
        ];
    }

    /**
     * Get recommendation for a single dedicated category.
     *
     * @param  array{name: string, balance: float, target: float, percentage: float}  $category
     * @return array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>}
     */
    private function getDedicatedCategoryRecommendation(array $category, float $readyToAssign): array
    {
        $shortfall = $category['target'] - $category['balance'];
        $recommendedAmount = $readyToAssign > 0 ? min($readyToAssign, $shortfall) : $shortfall;

        return [
            'priority' => 4,
            'type' => 'dedicated_category',
            'icon' => 'wallet',
            'status' => 'action',
            'title' => 'buffer.category_low_title',
            'description' => $readyToAssign > 0 ? 'buffer.category_low_description_with_funds' : 'buffer.category_low_description',
            'params' => [
                'category_name' => $category['name'],
                'current_amount' => round($category['balance'], 0),
                'target_amount' => round($category['target'], 0),
                'shortfall' => round($shortfall, 0),
                'percentage' => round($category['percentage'], 0),
                'ready_to_assign' => round($readyToAssign, 0),
                'recommended_amount' => round($recommendedAmount, 0),
            ],
        ];
    }

    /**
     * Get low dedicated categories (below target).
     *
     * @param  array<int, array{name: string, balance: float, target: float, percentage: float}>  $categories
     * @return array<int, array{name: string, balance: float, target: float, percentage: float}>
     */
    private function getLowDedicatedCategories(array $categories): array
    {
        return array_filter($categories, function (array $category) {
            return $category['percentage'] < 100;
        });
    }

    /**
     * Get debt recommendation (for regular debt payoff, priority 6).
     *
     * @param  Collection<int, Debt>  $debts
     * @return array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>}
     */
    private function getDebtRecommendation(Collection $debts, float $readyToAssign): array
    {
        $strategy = $this->settingsService->get('payoff_settings.strategy', 'string') ?? 'avalanche';
        $targetDebt = $strategy === 'avalanche'
            ? $debts->sortByDesc('interest_rate')->first()
            : $debts->sortBy('balance')->first();

        if ($targetDebt === null) {
            return [
                'priority' => 6,
                'type' => 'debt',
                'icon' => 'check-circle',
                'status' => 'success',
                'title' => 'buffer.no_debt_title',
                'description' => 'buffer.no_debt_description',
                'params' => [],
            ];
        }

        $totalDebt = (float) $debts->sum('balance');
        $impact = $readyToAssign > 0 ? $this->calculateDebtImpact($targetDebt, $readyToAssign) : null;

        return [
            'priority' => 6,
            'type' => 'debt',
            'icon' => 'banknotes',
            'status' => 'action',
            'title' => 'buffer.pay_debt_title',
            'description' => $readyToAssign > 0 ? 'buffer.pay_debt_description_with_funds' : 'buffer.pay_debt_description',
            'params' => [
                'debt_name' => $targetDebt->name,
                'debt_balance' => round($targetDebt->balance, 0),
                'interest_rate' => round($targetDebt->interest_rate, 1),
                'total_debt' => round($totalDebt, 0),
                'debt_count' => $debts->count(),
                'strategy' => $strategy,
                'ready_to_assign' => round($readyToAssign, 0),
                'interest_saved' => $impact ? round($impact['interest_saved'], 0) : 0,
                'months_saved' => $impact ? $impact['months_saved'] : 0,
            ],
        ];
    }

    /**
     * Compare scenarios: putting money in buffer vs paying down each debt.
     *
     * @param  array{emergency_buffer: array{amount: float, target: float, percentage: float}, dedicated_categories: array<int, array{name: string, balance: float, target: float, percentage: float}>, pay_period: array{funded: float, needed: float, is_covered: bool}, status: string}  $bufferStatus
     * @return array{amount: float, options: array<int, array{target: string, debt_id?: int, debt_name?: string, label: string, impact: array<string, mixed>}>, recommendation: array{target: string, reason: string}}
     */
    public function compareScenarios(float $amount, array $bufferStatus): array
    {
        $options = [];

        // Option 1: Put in emergency buffer
        $newBufferAmount = $bufferStatus['emergency_buffer']['amount'] + $amount;
        $newBufferPercentage = $bufferStatus['emergency_buffer']['target'] > 0
            ? ($newBufferAmount / $bufferStatus['emergency_buffer']['target']) * 100
            : 0;

        $options[] = [
            'target' => 'buffer',
            'label' => 'buffer.scenario_buffer',
            'impact' => [
                'amount_added' => round($amount, 2),
                'new_amount' => round($newBufferAmount, 2),
                'target_amount' => round($bufferStatus['emergency_buffer']['target'], 2),
                'new_percentage' => round($newBufferPercentage, 1),
            ],
        ];

        // Options 2+: Pay down each debt
        $debts = Debt::with('payments')->get();
        $debtOptions = [];
        foreach ($debts as $debt) {
            $impact = $this->calculateDebtImpact($debt, $amount);

            $debtOptions[] = [
                'target' => 'debt',
                'debt_id' => $debt->id,
                'debt_name' => $debt->name,
                'label' => 'buffer.scenario_debt',
                'impact' => $impact,
            ];
        }

        // Sort debt options by interest saved (highest first)
        usort($debtOptions, function ($a, $b) {
            $aSaved = $a['impact']['interest_saved'] ?? 0;
            $bSaved = $b['impact']['interest_saved'] ?? 0;

            return $bSaved <=> $aSaved; // Descending order
        });

        // Add sorted debt options to main options array
        $options = array_merge($options, $debtOptions);

        // Determine recommendation
        $recommendation = $this->determineRecommendation($options, $bufferStatus);

        return [
            'amount' => $amount,
            'options' => $options,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Determine the best recommendation from scenario options.
     *
     * Priority order (matches getSmartRecommendation):
     * 1. Pay period not covered
     * 2. Buffer below 50% (minimum safety)
     * 3. High-interest debt (above threshold)
     * 4. Dedicated categories below target
     * 5. Buffer below 100%
     * 6. Remaining debt
     * 7. Default: maintain buffer
     *
     * @param  array<int, array{target: string, debt_id?: int, debt_name?: string, label: string, impact: array<string, mixed>}>  $options
     * @param  array{emergency_buffer: array{amount: float, target: float, percentage: float}, dedicated_categories: array<int, array{name: string, balance: float, target: float, percentage: float}>, pay_period: array{funded: float, needed: float, is_covered: bool}, status: string}  $bufferStatus
     * @return array{target: string, reason: string}
     */
    private function determineRecommendation(array $options, array $bufferStatus): array
    {
        // Prioritet 1: Lønnsperiode ikke dekket
        if (! $bufferStatus['pay_period']['is_covered']) {
            return [
                'target' => 'pay_period',
                'reason' => 'buffer.recommendation_pay_period',
            ];
        }

        // Prioritet 2: Buffer under 50% (minimum sikkerhet)
        if ($bufferStatus['emergency_buffer']['percentage'] < self::MINIMUM_BUFFER_PERCENTAGE) {
            return [
                'target' => 'buffer',
                'reason' => 'buffer.recommendation_minimum_buffer',
            ];
        }

        // Prioritet 3: Høyrentegjeld (over terskel)
        $threshold = $this->settingsService->getHighInterestThreshold();
        $highInterestDebtOption = null;
        $highestInterestRate = 0;

        foreach ($options as $option) {
            if ($option['target'] === 'debt') {
                $debtId = $option['debt_id'] ?? null;
                if ($debtId !== null) {
                    $debt = Debt::find($debtId);
                    if ($debt !== null && $debt->interest_rate >= $threshold && $debt->interest_rate > $highestInterestRate) {
                        $highestInterestRate = $debt->interest_rate;
                        $highInterestDebtOption = $option;
                    }
                }
            }
        }

        if ($highInterestDebtOption !== null) {
            return [
                'target' => 'debt',
                'reason' => 'buffer.recommendation_high_interest_debt',
            ];
        }

        // Prioritet 4: Dedikerte kategorier under mål
        $lowCategories = $this->getLowDedicatedCategories($bufferStatus['dedicated_categories']);
        if (! empty($lowCategories)) {
            return [
                'target' => 'category',
                'reason' => 'buffer.recommendation_dedicated_category',
            ];
        }

        // Prioritet 5: Buffer under 100%
        if ($bufferStatus['emergency_buffer']['percentage'] < 100) {
            return [
                'target' => 'buffer',
                'reason' => 'buffer.recommendation_emergency_buffer',
            ];
        }

        // Prioritet 6: Resterende gjeld (lav rente)
        $bestDebtOption = null;
        $highestInterestSaved = 0;

        foreach ($options as $option) {
            if ($option['target'] === 'debt') {
                $interestSaved = $option['impact']['interest_saved'] ?? 0;
                if ($interestSaved > $highestInterestSaved) {
                    $highestInterestSaved = $interestSaved;
                    $bestDebtOption = $option;
                }
            }
        }

        if ($bestDebtOption !== null) {
            return [
                'target' => 'debt',
                'reason' => 'buffer.recommendation_pay_debt',
            ];
        }

        // Default: Behold buffer
        return [
            'target' => 'buffer',
            'reason' => 'buffer.recommendation_maintain_buffer',
        ];
    }

    /**
     * Get the debt with the highest interest rate.
     *
     * @param  Collection<int, Debt>|null  $debts
     */
    public function getHighestInterestDebt(?Collection $debts = null): ?Debt
    {
        if ($debts === null) {
            $debts = Debt::all();
        }

        if ($debts->isEmpty()) {
            return null;
        }

        return $debts->sortByDesc('interest_rate')->first();
    }

    /**
     * Calculate the impact of paying an amount toward a specific debt.
     *
     * Returns total debt-free date impact (when ALL debts are paid off),
     * not just when this specific debt is paid off.
     *
     * @return array{interest_saved: float, months_saved: int, new_payoff_date: string}
     */
    private function calculateDebtImpact(Debt $debt, float $amount): array
    {
        $debts = Debt::with('payments')->get();
        $extraPayment = $this->settingsService->get('payoff_settings.extra_payment', 'float') ?? 2000.0;
        $strategy = $this->settingsService->get('payoff_settings.strategy', 'string') ?? 'avalanche';

        $currentSchedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $extraPayment,
            $strategy
        );

        $modifiedDebts = $debts->map(function (Debt $d) use ($debt, $amount) {
            if ($d->id === $debt->id) {
                $reducedBalance = max(0, $d->balance - $amount);
                $clone = $d->replicate();
                $clone->id = $d->id;
                $clone->balance = $reducedBalance;
                $clone->original_balance = $reducedBalance;
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

        $whatIfStartingBalance = max(0, $debt->balance - $amount);

        // Calculate total debt-free date (when ALL debts are paid off)
        $currentDebtFreeMonth = ! empty($currentSchedule['schedule'])
            ? end($currentSchedule['schedule'])['month']
            : 0;
        $whatIfDebtFreeMonth = ! empty($whatIfSchedule['schedule'])
            ? end($whatIfSchedule['schedule'])['month']
            : 0;

        $monthsSaved = max(0, $currentDebtFreeMonth - $whatIfDebtFreeMonth);
        $interestSaved = max(0, $currentSchedule['totalInterest'] - $whatIfSchedule['totalInterest']);

        return [
            'interest_saved' => round($interestSaved, 2),
            'months_saved' => $monthsSaved,
            'new_payoff_date' => now()->addMonths($whatIfDebtFreeMonth)->format('Y-m-d'),
        ];
    }

    /**
     * Find the month number when a specific debt is paid off.
     *
     * @param  array<int, array<string, mixed>>  $schedule
     */
    private function findDebtPayoffMonth(array $schedule, string $debtName, ?float $startingBalance = null): int
    {
        if ($startingBalance !== null && $startingBalance <= 0.01) {
            return 0;
        }

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

        if (! $debtAppearsInSchedule && $startingBalance !== null) {
            return 0;
        }

        return count($schedule);
    }
}
