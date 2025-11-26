<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * @property array<string, mixed> $snowballData
 * @property array<string, mixed> $avalancheData
 * @property array<string, mixed> $customData
 * @property int $minimumPaymentMonths
 * @property float $minimumPaymentInterest
 */
class StrategyComparison extends Component
{
    public float $extraPayment = 2000;

    protected DebtCalculationService $calculationService;

    public function boot(DebtCalculationService $service): void
    {
        $this->calculationService = $service;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'extraPayment' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'extraPayment.required' => __('validation.required', ['attribute' => __('app.extra_monthly_payment')]),
            'extraPayment.numeric' => __('validation.numeric', ['attribute' => __('app.extra_monthly_payment')]),
            'extraPayment.min' => __('validation.min.numeric', ['attribute' => __('app.extra_monthly_payment'), 'min' => 0]),
            'extraPayment.max' => __('validation.max.numeric', ['attribute' => __('app.extra_monthly_payment'), 'max' => '1 000 000']),
        ];
    }

    public function updatedExtraPayment(): void
    {
        $this->validate(['extraPayment' => $this->rules()['extraPayment']]);
    }

    /**
     * Get all debts with caching to prevent N+1 queries.
     * Uses Laravel's once() helper to cache within a single request.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Debt>
     */
    protected function getDebts(): Collection
    {
        return once(fn () => Debt::all());
    }

    /**
     * @return array<string, mixed>
     */
    public function getSnowballDataProperty(): array
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return [
                'months' => 0,
                'totalInterest' => 0,
                'order' => [],
                'savings' => 0,
            ];
        }

        $comparison = $this->calculationService->compareStrategies($debts, $this->extraPayment);

        return $comparison['snowball'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAvalancheDataProperty(): array
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return [
                'months' => 0,
                'totalInterest' => 0,
                'order' => [],
                'savings' => 0,
            ];
        }

        $comparison = $this->calculationService->compareStrategies($debts, $this->extraPayment);

        return $comparison['avalanche'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomDataProperty(): array
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return [
                'months' => 0,
                'totalInterest' => 0,
                'order' => [],
                'savings' => 0,
            ];
        }

        $comparison = $this->calculationService->compareStrategies($debts, $this->extraPayment);

        return $comparison['custom'];
    }

    /**
     * Get the number of months to pay off all debts using only minimum payments.
     * Each debt is calculated independently with no reallocation of freed-up payments.
     */
    public function getMinimumPaymentMonthsProperty(): int
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return 0;
        }

        return $this->calculationService->calculateMinimumPaymentsOnly($debts);
    }

    /**
     * Get the total interest paid when using only minimum payments.
     * Each debt is calculated independently with no reallocation of freed-up payments.
     */
    public function getMinimumPaymentInterestProperty(): float
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return 0.0;
        }

        return $this->calculationService->calculateMinimumPaymentsInterest($debts);
    }

    /**
     * Get savings data for Snowball strategy compared to minimum payments.
     *
     * @return array{monthsSaved: int, yearsSaved: int, remainingMonths: int, interestSaved: float}
     */
    public function getSnowballSavingsProperty(): array
    {
        $monthsSaved = max(0, $this->minimumPaymentMonths - $this->snowballData['months']);
        $interestSaved = max(0, $this->minimumPaymentInterest - $this->snowballData['totalInterest']);

        return [
            'monthsSaved' => $monthsSaved,
            'yearsSaved' => (int) floor($monthsSaved / 12),
            'remainingMonths' => $monthsSaved % 12,
            'interestSaved' => $interestSaved,
        ];
    }

    /**
     * Get savings data for Avalanche strategy compared to minimum payments.
     *
     * @return array{monthsSaved: int, yearsSaved: int, remainingMonths: int, interestSaved: float}
     */
    public function getAvalancheSavingsProperty(): array
    {
        $monthsSaved = max(0, $this->minimumPaymentMonths - $this->avalancheData['months']);
        $interestSaved = max(0, $this->minimumPaymentInterest - $this->avalancheData['totalInterest']);

        return [
            'monthsSaved' => $monthsSaved,
            'yearsSaved' => (int) floor($monthsSaved / 12),
            'remainingMonths' => $monthsSaved % 12,
            'interestSaved' => $interestSaved,
        ];
    }

    /**
     * Get savings data for Custom strategy compared to minimum payments.
     *
     * @return array{monthsSaved: int, yearsSaved: int, remainingMonths: int, interestSaved: float}
     */
    public function getCustomSavingsProperty(): array
    {
        $monthsSaved = max(0, $this->minimumPaymentMonths - $this->customData['months']);
        $interestSaved = max(0, $this->minimumPaymentInterest - $this->customData['totalInterest']);

        return [
            'monthsSaved' => $monthsSaved,
            'yearsSaved' => (int) floor($monthsSaved / 12),
            'remainingMonths' => $monthsSaved % 12,
            'interestSaved' => $interestSaved,
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getOrderedDebtsProperty(): array
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return [
                'snowball' => [],
                'avalanche' => [],
                'custom' => [],
            ];
        }

        return [
            'snowball' => $this->calculationService->orderBySnowball($debts)->map(function ($debt) {
                return [
                    'name' => $debt->name,
                    'balance' => $debt->balance,
                    'interestRate' => $debt->interest_rate,
                ];
            })->toArray(),
            'avalanche' => $this->calculationService->orderByAvalanche($debts)->map(function ($debt) {
                return [
                    'name' => $debt->name,
                    'balance' => $debt->balance,
                    'interestRate' => $debt->interest_rate,
                ];
            })->toArray(),
            'custom' => $this->calculationService->orderByCustom($debts)->map(function ($debt) {
                return [
                    'name' => $debt->name,
                    'balance' => $debt->balance,
                    'interestRate' => $debt->interest_rate,
                ];
            })->toArray(),
        ];
    }

    /**
     * Determine which strategy saves the most money compared to minimum payments.
     * Returns 'snowball', 'avalanche', or 'custom' based on which has higher savings.
     */
    public function getBestStrategyProperty(): string
    {
        $snowballSavings = $this->snowballData['savings'] ?? 0;
        $avalancheSavings = $this->avalancheData['savings'] ?? 0;
        $customSavings = $this->customData['savings'] ?? 0;

        $maxSavings = max($snowballSavings, $avalancheSavings, $customSavings);

        if ($customSavings === $maxSavings) {
            return 'custom';
        }

        return $avalancheSavings >= $snowballSavings ? 'avalanche' : 'snowball';
    }

    /**
     * Get milestone data for Snowball strategy showing when each debt is paid off.
     *
     * @return array<int, array<string, mixed>> Array of milestones with debt name and month paid off
     */
    public function getSnowballMilestonesProperty(): array
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return [];
        }

        $schedule = $this->calculationService->generatePaymentSchedule($debts, $this->extraPayment, 'snowball');

        return $this->extractMilestones($schedule['schedule']);
    }

    /**
     * Get milestone data for Avalanche strategy showing when each debt is paid off.
     *
     * @return array<int, array<string, mixed>> Array of milestones with debt name and month paid off
     */
    public function getAvalancheMilestonesProperty(): array
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return [];
        }

        $schedule = $this->calculationService->generatePaymentSchedule($debts, $this->extraPayment, 'avalanche');

        return $this->extractMilestones($schedule['schedule']);
    }

    /**
     * Get milestone data for Custom strategy showing when each debt is paid off.
     *
     * @return array<int, array<string, mixed>> Array of milestones with debt name and month paid off
     */
    public function getCustomMilestonesProperty(): array
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return [];
        }

        $schedule = $this->calculationService->generatePaymentSchedule($debts, $this->extraPayment, 'custom');

        return $this->extractMilestones($schedule['schedule']);
    }

    /**
     * Extract milestone data from payment schedule.
     * Finds the month when each debt's balance reaches zero.
     *
     * @param  array<int, array<string, mixed>>  $schedule  Payment schedule array
     * @return array<int, array<string, mixed>> Array of milestones [debt_name => month_paid_off]
     */
    protected function extractMilestones(array $schedule): array
    {
        $milestones = [];
        $paidOffDebts = [];

        foreach ($schedule as $monthData) {
            // Skip if payments key doesn't exist
            if (! isset($monthData['payments']) || ! is_array($monthData['payments'])) {
                continue;
            }

            foreach ($monthData['payments'] as $payment) {
                $debtName = $payment['name'];

                // If debt is paid off this month (remaining balance is 0) and we haven't recorded it yet
                if ($payment['remaining'] <= 0.01 && ! in_array($debtName, $paidOffDebts)) {
                    $milestones[] = [
                        'name' => $debtName,
                        'month' => $monthData['month'],
                    ];
                    $paidOffDebts[] = $debtName;
                }
            }
        }

        return $milestones;
    }

    /**
     * Get chart data for strategy comparison visualization.
     * Returns monthly total debt balance for each strategy.
     *
     * @return array{labels: array<string>, datasets: array<array<string, mixed>>}
     */
    public function getStrategyChartDataProperty(): array
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return ['labels' => [], 'datasets' => []];
        }

        // Generate schedules for all strategies
        $minimumSchedule = $this->calculationService->generatePaymentSchedule($debts, 0, 'avalanche');
        $snowballSchedule = $this->calculationService->generatePaymentSchedule($debts, $this->extraPayment, 'snowball');
        $avalancheSchedule = $this->calculationService->generatePaymentSchedule($debts, $this->extraPayment, 'avalanche');
        $customSchedule = $this->calculationService->generatePaymentSchedule($debts, $this->extraPayment, 'custom');

        // Find maximum months across all strategies
        $maxMonths = max(
            count($minimumSchedule['schedule']),
            count($snowballSchedule['schedule']),
            count($avalancheSchedule['schedule']),
            count($customSchedule['schedule'])
        );

        // Build labels (month names) and data arrays
        $labels = [];
        $minimumData = [];
        $snowballData = [];
        $avalancheData = [];
        $customData = [];

        // Calculate initial total debt
        $initialTotal = $debts->sum('balance');

        // First point: current month with initial balance
        $labels[] = now()->locale('nb')->translatedFormat('F Y');
        $minimumData[] = $initialTotal;
        $snowballData[] = $initialTotal;
        $avalancheData[] = $initialTotal;
        $customData[] = $initialTotal;

        // Remaining points: each month's balance after payment
        for ($i = 1; $i <= $maxMonths; $i++) {
            $monthLabel = $this->getChartMonthLabel($i, $minimumSchedule, $snowballSchedule);
            $labels[] = $monthLabel;

            $minimumData[] = $this->getChartRemainingBalance($minimumSchedule, $i);
            $snowballData[] = $this->getChartRemainingBalance($snowballSchedule, $i);
            $avalancheData[] = $this->getChartRemainingBalance($avalancheSchedule, $i);
            $customData[] = $this->getChartRemainingBalance($customSchedule, $i);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('app.minimum_payments_only'),
                    'data' => $minimumData,
                    'borderColor' => '#6B7280',
                    'backgroundColor' => 'rgba(107, 114, 128, 0.1)',
                    'borderDash' => [5, 5],
                ],
                [
                    'label' => __('app.snowball_method'),
                    'data' => $snowballData,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => __('app.avalanche_method'),
                    'data' => $avalancheData,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
                [
                    'label' => __('app.custom_order'),
                    'data' => $customData,
                    'borderColor' => '#F97316',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.1)',
                ],
            ],
        ];
    }

    /**
     * Get month label from schedule data for chart.
     *
     * @param  array<string, mixed>  $schedule1
     * @param  array<string, mixed>  $schedule2
     */
    protected function getChartMonthLabel(int $month, array $schedule1, array $schedule2): string
    {
        $entry = $schedule1['schedule'][$month] ?? $schedule2['schedule'][$month] ?? null;

        return $entry['monthName'] ?? __('app.month').' '.$month;
    }

    /**
     * Get remaining total balance at a specific month for chart.
     *
     * @param  array<string, mixed>  $schedule
     */
    protected function getChartRemainingBalance(array $schedule, int $month): float
    {
        if ($month > count($schedule['schedule'])) {
            return 0.0;
        }

        $monthData = $schedule['schedule'][$month - 1] ?? null;
        if (! $monthData) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($monthData['payments'] as $payment) {
            $total += $payment['remaining'];
        }

        return round($total, 2);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.strategy-comparison')->layout('components.layouts.app');
    }
}
