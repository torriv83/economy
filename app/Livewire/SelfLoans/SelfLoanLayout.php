<?php

declare(strict_types=1);

namespace App\Livewire\SelfLoans;

use Livewire\Component;

class SelfLoanLayout extends Component
{
    public string $currentView = 'overview';

    public function showOverview(): void
    {
        $this->currentView = 'overview';
    }

    public function showCreate(): void
    {
        $this->currentView = 'create';
    }

    public function showHistory(): void
    {
        $this->currentView = 'history';
    }

    public function render()
    {
        return view('livewire.self-loans.self-loan-layout')->layout('components.layouts.app');
    }
}
