<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class SettingsLayout extends Component
{
    public string $currentView = 'plan';

    public function mount(): void
    {
        $view = request()->query('view');
        if (in_array($view, ['plan'])) {
            $this->currentView = $view;
        }
    }

    public function showPlan(): void
    {
        $this->currentView = 'plan';
    }

    public function render(): View
    {
        return view('livewire.settings.settings-layout')->layout('components.layouts.app');
    }
}
