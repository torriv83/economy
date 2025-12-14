<?php

declare(strict_types=1);

namespace App\Livewire\Insights;

use Livewire\Attributes\On;
use Livewire\Component;

class InsightsLayout extends Component
{
    public string $currentView = 'progress';

    public function mount(): void
    {
        $view = request()->query('view');

        if (in_array($view, ['progress', 'recommendations', 'interest'])) {
            $this->currentView = $view;
        }
    }

    public function showProgress(): void
    {
        $this->currentView = 'progress';
    }

    public function showRecommendations(): void
    {
        $this->currentView = 'recommendations';
    }

    public function showInterest(): void
    {
        $this->currentView = 'interest';
    }

    #[On('setView')]
    public function setView(string $view): void
    {
        match ($view) {
            'progress' => $this->showProgress(),
            'recommendations' => $this->showRecommendations(),
            'interest' => $this->showInterest(),
            default => null,
        };
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.insights.insights-layout')
            ->layout('components.layouts.app');
    }
}
