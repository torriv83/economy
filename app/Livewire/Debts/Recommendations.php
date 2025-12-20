<?php

declare(strict_types=1);

namespace App\Livewire\Debts;

use App\Services\BufferRecommendationService;
use App\Services\SettingsService;
use App\Services\YnabService;
use Livewire\Component;

/**
 * @property-read float $readyToAssign
 */
class Recommendations extends Component
{
    private YnabService $ynabService;

    private SettingsService $settingsService;

    private BufferRecommendationService $recommendationService;

    public bool $isLoading = true;

    public bool $showScenarioComparison = false;

    public float $scenarioAmount = 5000;

    private ?float $cachedReadyToAssign = null;

    public function boot(
        YnabService $ynabService,
        SettingsService $settingsService,
        BufferRecommendationService $recommendationService
    ): void {
        $this->ynabService = $ynabService;
        $this->settingsService = $settingsService;
        $this->recommendationService = $recommendationService;
    }

    public function getIsYnabConfiguredProperty(): bool
    {
        return $this->settingsService->isYnabConfigured();
    }

    /**
     * Get YNAB "Ready to Assign" (available funds).
     */
    public function getReadyToAssignProperty(): float
    {
        if ($this->cachedReadyToAssign !== null) {
            return $this->cachedReadyToAssign;
        }

        if (! $this->settingsService->isYnabConfigured()) {
            return 0.0;
        }

        try {
            $budgetSummary = $this->ynabService->fetchBudgetSummary();
            $this->cachedReadyToAssign = $budgetSummary['ready_to_assign'];

            return $this->cachedReadyToAssign;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * @return array{emergency_buffer: array{amount: float, target: float, percentage: float}, dedicated_categories: array<int, array{name: string, balance: float, target: float, percentage: float}>, pay_period: array{funded: float, needed: float, is_covered: bool, start_date: string, end_date: string}, status: string}|null
     */
    public function getBufferStatusProperty(): ?array
    {
        if (! $this->settingsService->isYnabConfigured()) {
            return null;
        }

        try {
            // Emergency buffer from savings accounts
            $savingsAccounts = $this->ynabService->fetchSavingsAccounts();
            $savingsTotal = (float) $savingsAccounts->sum('balance');
            $bufferTarget = $this->settingsService->getBufferTargetAmount();
            $emergencyPercentage = $bufferTarget > 0
                ? min(100, ($savingsTotal / $bufferTarget) * 100)
                : 0;

            // Dedicated categories from settings
            $bufferCategoriesConfig = $this->settingsService->getBufferCategories();
            $categoryNames = array_column($bufferCategoriesConfig, 'name');
            $ynabCategories = $this->ynabService->fetchCategoriesByNames($categoryNames);

            // Map YNAB category balances to config targets
            $dedicatedCategories = [];
            foreach ($bufferCategoriesConfig as $configCategory) {
                $ynabCategory = $ynabCategories->firstWhere('name', $configCategory['name']);
                $balance = $ynabCategory['balance'] ?? 0.0;
                $target = $configCategory['target'];
                $percentage = $target > 0 ? min(100, ($balance / $target) * 100) : 0;

                $dedicatedCategories[] = [
                    'name' => $configCategory['name'],
                    'balance' => $balance,
                    'target' => $target,
                    'percentage' => round($percentage, 0),
                ];
            }

            // Pay period status
            $payPeriodData = $this->ynabService->fetchPayPeriodShortfall(20);
            $funded = $payPeriodData['funded'];
            $needed = $payPeriodData['monthly_essential'];
            $isCovered = $funded >= $needed;

            // Calculate pay period date range (20th to 19th)
            $now = new \DateTimeImmutable;
            $currentDay = (int) $now->format('j');
            $payDay = 20;

            if ($currentDay >= $payDay) {
                // We're in the period starting this month
                $startDate = $now->setDate((int) $now->format('Y'), (int) $now->format('n'), $payDay);
                $endDate = $startDate->modify('+1 month')->modify('-1 day');
            } else {
                // We're in the period that started last month
                $startDate = $now->modify('-1 month')->setDate((int) $now->modify('-1 month')->format('Y'), (int) $now->modify('-1 month')->format('n'), $payDay);
                $endDate = $now->setDate((int) $now->format('Y'), (int) $now->format('n'), $payDay - 1);
            }

            $startDateFormatted = $startDate->format('j').'. '.mb_strtolower($this->getMonthName((int) $startDate->format('n')));
            $endDateFormatted = $endDate->format('j').'. '.mb_strtolower($this->getMonthName((int) $endDate->format('n')));

            // Determine overall status
            $status = $this->calculateBufferStatus($emergencyPercentage, $dedicatedCategories, $isCovered);

            return [
                'emergency_buffer' => [
                    'amount' => $savingsTotal,
                    'target' => $bufferTarget,
                    'percentage' => round($emergencyPercentage, 0),
                ],
                'dedicated_categories' => $dedicatedCategories,
                'pay_period' => [
                    'funded' => $funded,
                    'needed' => $needed,
                    'is_covered' => $isCovered,
                    'start_date' => $startDateFormatted,
                    'end_date' => $endDateFormatted,
                ],
                'status' => $status,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get Norwegian month name.
     */
    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'jan',
            2 => 'feb',
            3 => 'mar',
            4 => 'apr',
            5 => 'mai',
            6 => 'jun',
            7 => 'jul',
            8 => 'aug',
            9 => 'sep',
            10 => 'okt',
            11 => 'nov',
            12 => 'des',
        ];

        return $months[$month] ?? '';
    }

    /**
     * Calculate overall buffer status based on all components.
     *
     * @param  array<int, array{name: string, balance: float, target: float, percentage: float}>  $dedicatedCategories
     */
    private function calculateBufferStatus(float $emergencyPercentage, array $dedicatedCategories, bool $payPeriodCovered): string
    {
        // Critical if:
        // - Pay period is not covered, OR
        // - Emergency buffer is below 25%
        if (! $payPeriodCovered || $emergencyPercentage < 25) {
            return 'critical';
        }

        // Warning if:
        // - Emergency buffer is below 75%, OR
        // - Any dedicated category is below 50%
        if ($emergencyPercentage < 75) {
            return 'warning';
        }

        foreach ($dedicatedCategories as $category) {
            if ($category['percentage'] < 50) {
                return 'warning';
            }
        }

        return 'healthy';
    }

    /**
     * Get buffer recommendations.
     *
     * @return array<int, array{priority: int, type: string, icon: string, status: string, title: string, description: string, params: array<string, mixed>, action?: array<string, mixed>}>
     */
    public function getRecommendationsProperty(): array
    {
        if (! $this->settingsService->isYnabConfigured()) {
            return [];
        }

        $bufferStatus = $this->getBufferStatusProperty();
        if ($bufferStatus === null) {
            return [];
        }

        try {
            $readyToAssign = $this->readyToAssign;

            return $this->recommendationService->getRecommendations($bufferStatus, $readyToAssign);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get scenario comparison data.
     *
     * @return array{amount: float, options: array<int, array<string, mixed>>, recommendation: array{target: string, reason: string}}|null
     */
    public function getScenarioComparisonProperty(): ?array
    {
        if (! $this->showScenarioComparison) {
            return null;
        }

        $bufferStatus = $this->getBufferStatusProperty();
        if ($bufferStatus === null) {
            return null;
        }

        try {
            return $this->recommendationService->compareScenarios($this->scenarioAmount, $bufferStatus);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function toggleScenarioComparison(): void
    {
        $this->showScenarioComparison = ! $this->showScenarioComparison;
    }

    public function updateScenarioAmount(): void
    {
        // Triggered by wire:model.live on the input
        // The property is already updated, so we just need to let the computed property recalculate
    }

    public function loadData(): void
    {
        $this->isLoading = false;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.debts.recommendations');
    }
}
