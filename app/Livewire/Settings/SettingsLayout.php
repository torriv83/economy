<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class SettingsLayout extends Component
{
    public string $currentView = 'plan';

    public function mount(): void
    {
        $view = request()->query('view');
        if (in_array($view, ['plan', 'shortcuts'])) {
            $this->currentView = $view;
        }
    }

    #[On('setView')]
    public function setView(string $view): void
    {
        match ($view) {
            'plan' => $this->showPlan(),
            'shortcuts' => $this->showShortcuts(),
            default => null,
        };
    }

    public function showPlan(): void
    {
        $this->currentView = 'plan';
    }

    public function showShortcuts(): void
    {
        $this->currentView = 'shortcuts';
    }

    public function render(): View
    {
        return view('livewire.settings.settings-layout')->layout('components.layouts.app');
    }
}
