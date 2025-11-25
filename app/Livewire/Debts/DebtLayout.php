<?php

declare(strict_types=1);

namespace App\Livewire\Debts;

use Livewire\Component;

class DebtLayout extends Component
{
    public string $currentView = 'overview';

    public function mount(): void
    {
        $view = request()->query('view');
        if (in_array($view, ['overview', 'create', 'progress'])) {
            $this->currentView = $view;
        }
    }

    public function showOverview(): void
    {
        $this->currentView = 'overview';
    }

    public function showCreate(): void
    {
        $this->currentView = 'create';
    }

    public function showProgress(): void
    {
        $this->currentView = 'progress';
    }

    public function render()
    {
        return view('livewire.debts.debt-layout')->layout('components.layouts.app');
    }
}
