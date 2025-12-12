<?php

declare(strict_types=1);

namespace App\Livewire\Debts;

use App\Services\BufferRecommendationService;
use App\Services\SettingsService;
use App\Services\YnabService;
use Livewire\Component;

class Recommendations extends Component
{
    private YnabService $ynabService;

    private SettingsService $settingsService;

    private BufferRecommendationService $recommendationService;

    public bool $showScenarioComparison = false;

    public float $scenarioAmount = 5000;

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
     * @return array{layer1: array{amount: float, percentage: float, is_month_ahead: bool}, layer2: array{amount: float, months: float, target_months: int}, total_buffer: float, monthly_essential: float, months_of_security: float, status: string}|null
     */
    public function getBufferStatusProperty(): ?array
    {
        if (! $this->settingsService->isYnabConfigured()) {
            return null;
        }

        try {
            $savingsAccounts = $this->ynabService->fetchSavingsAccounts();
            $payPeriodData = $this->ynabService->fetchPayPeriodShortfall(20);

            $monthlyEssential = $payPeriodData['monthly_essential'];
            $savingsTotal = $savingsAccounts->sum('balance');

            // Layer 1: Operational buffer (one month ahead)
            $layer1Amount = $payPeriodData['funded'];
            $layer1Percentage = $monthlyEssential > 0
                ? min(100, ($layer1Amount / $monthlyEssential) * 100)
                : 0;
            $isMonthAhead = $monthlyEssential > 0 && ($layer1Amount + $savingsTotal) >= $monthlyEssential;

            // Layer 2: Emergency buffer (savings accounts)
            $layer2Amount = $savingsTotal;
            $recommendedEmergencyMonths = 2;
            $layer2Months = $monthlyEssential > 0 ? $layer2Amount / $monthlyEssential : 0;

            // Total
            $totalBuffer = $layer1Amount + $layer2Amount;
            $totalMonths = $monthlyEssential > 0 ? $totalBuffer / $monthlyEssential : 0;

            return [
                'layer1' => [
                    'amount' => $layer1Amount,
                    'percentage' => round($layer1Percentage, 0),
                    'is_month_ahead' => $isMonthAhead,
                ],
                'layer2' => [
                    'amount' => $layer2Amount,
                    'months' => round($layer2Months, 1),
                    'target_months' => $recommendedEmergencyMonths,
                ],
                'total_buffer' => $totalBuffer,
                'monthly_essential' => $monthlyEssential,
                'months_of_security' => round($totalMonths, 1),
                'status' => $this->getBufferStatus($totalMonths),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getBufferStatus(float $months): string
    {
        if ($months < 1) {
            return 'critical';
        }
        if ($months < 2) {
            return 'warning';
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
            return $this->recommendationService->getRecommendations($bufferStatus);
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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.debts.recommendations');
    }
}
