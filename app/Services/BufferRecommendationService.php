<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Debt;
use Illuminate\Support\Collection;

class BufferRecommendationService
{
    /**
     * Minimum buffer level considered acceptable (in months).
     * This is kept as a constant as it represents a critical safety threshold.
     */
    private const MINIMUM_ACCEPTABLE_BUFFER_MONTHS = 1.0;

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

    /**
     * Get smart financial recommendations based on buffer status and debt situation.
     *
     * @param  array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}  $bufferStatus
     * @return array<int, array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>, action?: array<string, mixed>}>
     */
    public function getRecommendations(array $bufferStatus): array
    {
        if (! $this->settingsService->isYnabConfigured()) {
            return [];
        }

        $recommendations = [];

        // Layer 1: Operational buffer (one month ahead)
        $recommendations[] = $this->getLayer1Recommendation($bufferStatus);

        // Layer 2 & Debt: Dynamic tradeoff (always show - no hard rules)
        $layer2DebtRecommendations = $this->getLayer2AndDebtRecommendations($bufferStatus);
        $recommendations = array_merge($recommendations, $layer2DebtRecommendations);

        return $recommendations;
    }

    /**
     * Get Layer 1 (operational buffer) recommendation.
     *
     * @param  array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}  $bufferStatus
     * @return array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>, action?: array{type: string, amount: float, target: string}}
     */
    private function getLayer1Recommendation(array $bufferStatus): array
    {
        if ($bufferStatus['layer1']['is_month_ahead']) {
            return [
                'priority' => 1,
                'type' => 'layer1',
                'icon' => 'check-circle',
                'status' => 'success',
                'title' => 'buffer.layer1_success_title',
                'description' => 'buffer.layer1_success_description',
                'params' => [],
            ];
        }

        // Not one month ahead - recommend transferring from savings
        $shortfall = $bufferStatus['monthly_essential'] - $bufferStatus['layer1']['amount'];
        $availableInSavings = $bufferStatus['layer2']['amount'];

        // Can only transfer what's actually available in savings
        $transferAmount = min($shortfall, $availableInSavings);
        $remainingBufferAfterTransfer = max(0, $availableInSavings - $transferAmount);
        $stillNeededAfterTransfer = max(0, $shortfall - $transferAmount);

        // Choose description based on situation:
        // - No savings: layer1_no_savings_description
        // - Partial (some but not enough): layer1_partial_description
        // - Full (can cover shortfall): layer1_action_description
        $description = match (true) {
            $availableInSavings <= 0 => 'buffer.layer1_no_savings_description',
            $transferAmount < $shortfall => 'buffer.layer1_partial_description',
            default => 'buffer.layer1_action_description',
        };

        return [
            'priority' => 1,
            'type' => 'layer1',
            'icon' => 'arrow-right',
            'status' => 'action',
            'title' => 'buffer.layer1_action_title',
            'description' => $description,
            'params' => [
                'shortfall' => round($shortfall, 2),
                'transfer_amount' => round($transferAmount, 2),
                'remaining_buffer' => round($availableInSavings, 2),
                'remaining_months' => round($remainingBufferAfterTransfer / max(1, $bufferStatus['monthly_essential']), 1),
                'still_needed' => round($stillNeededAfterTransfer, 2),
            ],
            'action' => [
                'type' => 'transfer',
                'amount' => round($transferAmount, 2),
                'target' => 'checking',
            ],
        ];
    }

    /**
     * Get Layer 2 (emergency buffer) and debt recommendations.
     *
     * @param  array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}  $bufferStatus
     * @return array<int, array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>, action?: array{type: string, amount: float, target: string, impact: array<string, mixed>}}>
     */
    private function getLayer2AndDebtRecommendations(array $bufferStatus): array
    {
        $recommendations = [];
        $debts = Debt::with('payments')->get();

        if ($debts->isEmpty()) {
            // No debts - focus on buffer
            return $this->getBufferOnlyRecommendations($bufferStatus);
        }

        $highestInterestDebt = $this->getHighestInterestDebt($debts);
        $bufferMonths = $bufferStatus['layer2']['months'];
        $bufferLevel = $this->categorizeBufferLevel($bufferMonths);

        // Scenario A: High debt interest
        if ($highestInterestDebt !== null && $highestInterestDebt->interest_rate >= $this->settingsService->getHighInterestThreshold()) {
            $recommendations[] = $this->getHighInterestDebtRecommendation(
                $highestInterestDebt,
                $bufferStatus,
                $bufferLevel
            );

            return $recommendations;
        }

        // Scenario B: Low debt interest
        if ($highestInterestDebt !== null && $highestInterestDebt->interest_rate < $this->settingsService->getLowInterestThreshold()) {
            $recommendations[] = $this->getLowInterestDebtRecommendation(
                $highestInterestDebt,
                $bufferStatus,
                $bufferLevel
            );

            return $recommendations;
        }

        // Scenario C: Good buffer + debt
        if ($bufferMonths >= $this->settingsService->getBufferTargetMonths() && $highestInterestDebt !== null) {
            $recommendations[] = $this->getGoodBufferWithDebtRecommendation(
                $highestInterestDebt,
                $bufferStatus
            );

            return $recommendations;
        }

        // Scenario D: Balanced situation (medium interest + medium buffer)
        if ($highestInterestDebt !== null) {
            $recommendations[] = $this->getBalancedRecommendation(
                $highestInterestDebt,
                $bufferStatus
            );

            return $recommendations;
        }

        return $recommendations;
    }

    /**
     * Get recommendations when there are no debts.
     *
     * @param  array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}  $bufferStatus
     * @return array<int, array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>}>
     */
    private function getBufferOnlyRecommendations(array $bufferStatus): array
    {
        $bufferMonths = $bufferStatus['layer2']['months'];

        if ($bufferMonths >= $this->settingsService->getBufferTargetMonths()) {
            return [
                [
                    'priority' => 2,
                    'type' => 'buffer',
                    'icon' => 'shield-check',
                    'status' => 'success',
                    'title' => 'buffer.buffer_good_title',
                    'description' => 'buffer.buffer_good_description',
                    'params' => [
                        'months' => round($bufferMonths, 1),
                    ],
                ],
            ];
        }

        $shortfall = ($bufferStatus['monthly_essential'] * $this->settingsService->getBufferTargetMonths()) - $bufferStatus['layer2']['amount'];

        return [
            [
                'priority' => 2,
                'type' => 'buffer',
                'icon' => 'shield',
                'status' => 'action',
                'title' => 'buffer.buffer_build_title',
                'description' => 'buffer.buffer_build_description',
                'params' => [
                    'current_months' => round($bufferMonths, 1),
                    'target_months' => $this->settingsService->getBufferTargetMonths(),
                    'shortfall' => round($shortfall, 2),
                ],
            ],
        ];
    }

    /**
     * Get recommendation for high-interest debt scenario.
     *
     * @param  array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}  $bufferStatus
     * @return array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>, action: array{type: string, amount: float, target: string, impact: array<string, mixed>}}
     */
    private function getHighInterestDebtRecommendation(
        Debt $debt,
        array $bufferStatus,
        string $bufferLevel
    ): array {
        $suggestedAmount = $this->calculateSuggestedPaymentAmount($bufferStatus, $bufferLevel);
        $impact = $this->calculateDebtImpact($debt, $suggestedAmount);

        return [
            'priority' => 2,
            'type' => 'debt',
            'icon' => 'banknotes',
            'status' => 'action',
            'title' => 'buffer.high_interest_debt_title',
            'description' => 'buffer.high_interest_debt_description',
            'params' => [
                'debt_name' => $debt->name,
                'interest_rate' => round($debt->interest_rate, 1),
                'interest_saved' => round($impact['interest_saved'], 2),
                'suggested_amount' => round($suggestedAmount, 2),
                'available_savings' => round($bufferStatus['layer2']['amount'], 2),
            ],
            'action' => [
                'type' => 'pay_debt',
                'amount' => round($suggestedAmount, 2),
                'target' => $debt->name,
                'impact' => $impact,
            ],
        ];
    }

    /**
     * Get recommendation for low-interest debt scenario.
     *
     * @param  array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}  $bufferStatus
     * @return array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>}
     */
    private function getLowInterestDebtRecommendation(
        Debt $debt,
        array $bufferStatus,
        string $bufferLevel
    ): array {
        return [
            'priority' => 2,
            'type' => 'buffer',
            'icon' => 'shield',
            'status' => 'action',
            'title' => 'buffer.low_interest_debt_title',
            'description' => 'buffer.low_interest_debt_description',
            'params' => [
                'debt_name' => $debt->name,
                'interest_rate' => round($debt->interest_rate, 1),
                'current_buffer_months' => round($bufferStatus['layer2']['months'], 1),
                'target_buffer_months' => $this->settingsService->getBufferTargetMonths(),
            ],
        ];
    }

    /**
     * Get recommendation for good buffer with debt scenario.
     *
     * @param  array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}  $bufferStatus
     * @return array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>, action: array{type: string, amount: float, target: string, impact: array<string, mixed>}}
     */
    private function getGoodBufferWithDebtRecommendation(Debt $debt, array $bufferStatus): array
    {
        $excessBuffer = $bufferStatus['layer2']['amount'] - ($bufferStatus['monthly_essential'] * $this->settingsService->getBufferTargetMonths());
        $suggestedAmount = max(1000, min($excessBuffer, $debt->balance));
        $impact = $this->calculateDebtImpact($debt, $suggestedAmount);

        return [
            'priority' => 2,
            'type' => 'debt',
            'icon' => 'banknotes',
            'status' => 'action',
            'title' => 'buffer.good_buffer_debt_title',
            'description' => 'buffer.good_buffer_debt_description',
            'params' => [
                'debt_name' => $debt->name,
                'suggested_amount' => round($suggestedAmount, 2),
                'interest_saved' => round($impact['interest_saved'], 2),
                'months_saved' => $impact['months_saved'],
            ],
            'action' => [
                'type' => 'pay_debt',
                'amount' => round($suggestedAmount, 2),
                'target' => $debt->name,
                'impact' => $impact,
            ],
        ];
    }

    /**
     * Get recommendation for balanced scenario.
     *
     * @param  array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}  $bufferStatus
     * @return array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>}
     */
    private function getBalancedRecommendation(Debt $debt, array $bufferStatus): array
    {
        $bufferMonths = $bufferStatus['layer2']['months'];
        $bufferShortfall = ($bufferStatus['monthly_essential'] * $this->settingsService->getBufferTargetMonths()) - $bufferStatus['layer2']['amount'];

        return [
            'priority' => 2,
            'type' => 'balanced',
            'icon' => 'scale',
            'status' => 'info',
            'title' => 'buffer.balanced_title',
            'description' => 'buffer.balanced_description',
            'params' => [
                'debt_name' => $debt->name,
                'interest_rate' => round($debt->interest_rate, 1),
                'current_buffer_months' => round($bufferMonths, 1),
                'buffer_shortfall' => round($bufferShortfall, 2),
            ],
        ];
    }

    /**
     * Compare scenarios: putting money in buffer vs paying down each debt.
     *
     * @param  array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}  $bufferStatus
     * @return array{amount: float, options: array<int, array{target: string, debt_id?: int, debt_name?: string, label: string, impact: array<string, mixed>}>, recommendation: array{target: string, reason: string}}
     */
    public function compareScenarios(float $amount, array $bufferStatus): array
    {
        $options = [];

        // Option 1: Put in buffer
        $daysOfSecurity = ($amount / max(1, $bufferStatus['monthly_essential'])) * 30;
        $newBufferMonths = ($bufferStatus['layer2']['amount'] + $amount) / max(1, $bufferStatus['monthly_essential']);

        $options[] = [
            'target' => 'buffer',
            'label' => 'buffer.scenario_buffer',
            'impact' => [
                'days_of_security_added' => (int) round($daysOfSecurity),
                'new_buffer_months' => round($newBufferMonths, 1),
            ],
        ];

        // Options 2+: Pay down each debt
        $debts = Debt::with('payments')->get();
        foreach ($debts as $debt) {
            $impact = $this->calculateDebtImpact($debt, $amount);

            $options[] = [
                'target' => 'debt',
                'debt_id' => $debt->id,
                'debt_name' => $debt->name,
                'label' => 'buffer.scenario_debt',
                'impact' => $impact,
            ];
        }

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
     * @param  array<int, array{target: string, debt_id?: int, debt_name?: string, label: string, impact: array<string, mixed>}>  $options
     * @param  array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}  $bufferStatus
     * @return array{target: string, reason: string}
     */
    private function determineRecommendation(array $options, array $bufferStatus): array
    {
        $bufferMonths = $bufferStatus['layer2']['months'];

        // If buffer is critically low (< 1 month), recommend buffer
        if ($bufferMonths < self::MINIMUM_ACCEPTABLE_BUFFER_MONTHS) {
            return [
                'target' => 'buffer',
                'reason' => 'buffer.recommendation_critical_buffer',
            ];
        }

        // Find highest interest savings among debts
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

        // If we have high-interest debt (saves significant money), recommend that
        if ($bestDebtOption !== null && $highestInterestSaved > $this->settingsService->getMinInterestSavings()) {
            return [
                'target' => 'debt',
                'reason' => 'buffer.recommendation_high_interest',
            ];
        }

        // If buffer is below recommended, prioritize buffer
        if ($bufferMonths < $this->settingsService->getBufferTargetMonths()) {
            return [
                'target' => 'buffer',
                'reason' => 'buffer.recommendation_build_buffer',
            ];
        }

        // Otherwise, recommend debt if it exists
        if ($bestDebtOption !== null) {
            return [
                'target' => 'debt',
                'reason' => 'buffer.recommendation_pay_debt',
            ];
        }

        // Default to buffer
        return [
            'target' => 'buffer',
            'reason' => 'buffer.recommendation_maintain_buffer',
        ];
    }

    /**
     * Get the debt with the highest interest rate.
     *
     * @param  Collection<int, Debt>  $debts
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
     * @return array{interest_saved: float, months_saved: int, new_payoff_date: string}
     */
    private function calculateDebtImpact(Debt $debt, float $amount): array
    {
        // Use the existing AccelerationService pattern
        $debts = Debt::with('payments')->get();
        $extraPayment = $this->settingsService->get('payoff_settings.extra_payment', 'float') ?? 2000.0;
        $strategy = $this->settingsService->get('payoff_settings.strategy', 'string') ?? 'avalanche';

        // Current scenario
        $currentSchedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $extraPayment,
            $strategy
        );

        // What-if scenario: Apply amount as ONE-TIME payment to this specific debt
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

        // Find when this specific debt is paid off
        $whatIfStartingBalance = max(0, $debt->balance - $amount);

        $currentPayoffMonth = $this->findDebtPayoffMonth($currentSchedule['schedule'], $debt->name, $debt->balance);
        $whatIfPayoffMonth = $this->findDebtPayoffMonth($whatIfSchedule['schedule'], $debt->name, $whatIfStartingBalance);

        $monthsSaved = max(0, $currentPayoffMonth - $whatIfPayoffMonth);
        $interestSaved = max(0, $currentSchedule['totalInterest'] - $whatIfSchedule['totalInterest']);

        return [
            'interest_saved' => round($interestSaved, 2),
            'months_saved' => $monthsSaved,
            'new_payoff_date' => now()->addMonths($whatIfPayoffMonth)->format('Y-m-d'),
        ];
    }

    /**
     * Find the month number when a specific debt is paid off.
     *
     * @param  array<int, array<string, mixed>>  $schedule
     */
    private function findDebtPayoffMonth(array $schedule, string $debtName, ?float $startingBalance = null): int
    {
        // If starting balance is 0 or near 0, debt is already paid off (month 0)
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

    /**
     * Categorize buffer level into low/medium/high.
     */
    private function categorizeBufferLevel(float $bufferMonths): string
    {
        if ($bufferMonths < self::MINIMUM_ACCEPTABLE_BUFFER_MONTHS) {
            return 'low';
        }
        if ($bufferMonths < $this->settingsService->getBufferTargetMonths()) {
            return 'medium';
        }

        return 'high';
    }

    /**
     * Calculate suggested payment amount based on buffer level.
     *
     * @param  array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}  $bufferStatus
     */
    private function calculateSuggestedPaymentAmount(array $bufferStatus, string $bufferLevel): float
    {
        $layer2Amount = $bufferStatus['layer2']['amount'];
        $monthlyEssential = $bufferStatus['monthly_essential'];

        return match ($bufferLevel) {
            'low' => min(1000, $layer2Amount * 0.1), // 10% of buffer or 1000, whichever is less
            'medium' => min(2000, $layer2Amount * 0.2), // 20% of buffer or 2000, whichever is less
            'high' => min(5000, $layer2Amount - ($monthlyEssential * $this->settingsService->getBufferTargetMonths())), // Excess above recommended
            default => 1000,
        };
    }
}
