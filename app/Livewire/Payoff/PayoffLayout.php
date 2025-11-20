<?php

declare(strict_types=1);

namespace App\Livewire\Payoff;

use Livewire\Component;

class PayoffLayout extends Component
{
    public string $currentView = 'strategies';

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

    public function render()
    {
        return view('livewire.payoff.payoff-layout')->layout('components.layouts.app');
    }
}
