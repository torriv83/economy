<?php

declare(strict_types=1);

namespace App\Livewire\Debts;

use Livewire\Attributes\On;
use Livewire\Component;

class DebtLayout extends Component
{
    public string $currentView = 'overview';

    public ?int $editingDebtId = null;

    public function mount(): void
    {
        $view = request()->query('view');
        $debtId = request()->query('debtId');

        if ($view === 'edit' && $debtId) {
            $this->currentView = 'edit';
            $this->editingDebtId = (int) $debtId;
        } elseif (in_array($view, ['overview', 'create', 'progress'])) {
            $this->currentView = $view;
        }
    }

    public function showOverview(): void
    {
        $this->currentView = 'overview';
        $this->editingDebtId = null;
    }

    public function showCreate(): void
    {
        $this->currentView = 'create';
        $this->editingDebtId = null;
    }

    public function showProgress(): void
    {
        $this->currentView = 'progress';
        $this->editingDebtId = null;
    }

    public function editDebt(int $debtId): void
    {
        $this->editingDebtId = $debtId;
        $this->currentView = 'edit';
    }

    public function cancelEdit(): void
    {
        $this->editingDebtId = null;
        $this->currentView = 'overview';
    }

    #[On('debtUpdated')]
    public function onDebtUpdated(): void
    {
        $this->editingDebtId = null;
        $this->currentView = 'overview';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $editingDebt = $this->editingDebtId
            ? \App\Models\Debt::find($this->editingDebtId)
            : null;

        return view('livewire.debts.debt-layout', [
            'editingDebt' => $editingDebt,
        ])->layout('components.layouts.app');
    }
}
