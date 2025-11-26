<?php

declare(strict_types=1);

namespace App\Livewire\Payoff;

use App\Services\PayoffSettingsService;
use Livewire\Attributes\On;
use Livewire\Component;

class PayoffLayout extends Component
{
    public string $currentView = 'calendar';

    public float $extraPayment = 2000;

    public string $strategy = 'avalanche';

    protected PayoffSettingsService $settingsService;

    public function boot(PayoffSettingsService $service): void
    {
        $this->settingsService = $service;
    }

    public function mount(): void
    {
        $this->extraPayment = $this->settingsService->getExtraPayment();
        $this->strategy = $this->settingsService->getStrategy();

        $view = request()->query('view');
        if (in_array($view, ['calendar', 'plan', 'strategies'])) {
            $this->currentView = $view;
        }
    }

    #[On('planSettingsUpdated')]
    public function updateSettings(float $extraPayment, string $strategy): void
    {
        $this->extraPayment = $extraPayment;
        $this->strategy = $strategy;
    }

    public function showStrategies(): void
    {
        $this->currentView = 'strategies';
    }

    public function showPlan(): void
    {
        $this->currentView = 'plan';
    }

    public function showCalendar(): void
    {
        $this->currentView = 'calendar';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.payoff.payoff-layout')->layout('components.layouts.app');
    }
}
