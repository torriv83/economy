<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\InterestInsightsService;
use App\Services\PayoffSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class InterestInsights extends Component
{
    public bool $isLoading = true;

    public string $period = 'month';

    protected InterestInsightsService $insightsService;

    protected PayoffSettingsService $settingsService;

    public function boot(InterestInsightsService $insightsService, PayoffSettingsService $settingsService): void
    {
        $this->insightsService = $insightsService;
        $this->settingsService = $settingsService;
    }

    public function loadData(): void
    {
        $this->isLoading = false;
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function breakdown(): array
    {
        return $this->insightsService->getInterestBreakdown($this->period);
    }

    /**
     * @return Collection<int, array{debt_id: int, debt_name: string, interest_paid: float, principal_paid: float, total_paid: float}>
     */
    #[Computed]
    public function perDebtBreakdown(): Collection
    {
        return $this->insightsService->getPerDebtInterestBreakdown($this->period);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function scenarios(): array
    {
        return $this->insightsService->getExtraPaymentScenarios([500, 1000, 2000, 5000]);
    }

    #[Computed]
    public function currentExtraPayment(): float
    {
        return $this->settingsService->getExtraPayment();
    }

    #[Computed]
    public function hasPayments(): bool
    {
        return $this->breakdown()['total_paid'] > 0;
    }

    #[Computed]
    public function principalPercentage(): float
    {
        $breakdown = $this->breakdown();
        $total = $breakdown['total_paid'];
        if ($total <= 0) {
            return 0.0;
        }

        return round(($breakdown['principal_paid'] / $total) * 100, 1);
    }

    public function render(): View
    {
        return view('livewire.interest-insights');
    }
}
