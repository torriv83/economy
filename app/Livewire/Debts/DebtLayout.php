<?php

declare(strict_types=1);

namespace App\Livewire\Debts;

use Livewire\Attributes\On;
use Livewire\Component;

class DebtLayout extends Component
{
    public string $currentView = 'overview';

    public ?int $editingDebtId = null;

    public ?int $viewingDebtId = null;

    public function mount(): void
    {
        $view = request()->query('view');
        $debtId = request()->query('debtId');

        if ($view === 'detail' && $debtId) {
            $this->currentView = 'detail';
            $this->viewingDebtId = (int) $debtId;
        } elseif ($view === 'edit' && $debtId) {
            $this->currentView = 'edit';
            $this->editingDebtId = (int) $debtId;
        } elseif (in_array($view, ['overview', 'create', 'progress', 'recommendations', 'insights', 'reconciliations'])) {
            $this->currentView = $view;
        }
    }

    public function showOverview(): void
    {
        $this->currentView = 'overview';
        $this->editingDebtId = null;
        $this->viewingDebtId = null;
    }

    public function showDetail(int $debtId): void
    {
        $this->viewingDebtId = $debtId;
        $this->editingDebtId = null;
        $this->currentView = 'detail';
    }

    public function showCreate(): void
    {
        $this->currentView = 'create';
        $this->editingDebtId = null;
        $this->viewingDebtId = null;
    }

    public function showProgress(): void
    {
        $this->currentView = 'progress';
        $this->editingDebtId = null;
        $this->viewingDebtId = null;
    }

    public function showRecommendations(): void
    {
        $this->currentView = 'recommendations';
        $this->editingDebtId = null;
        $this->viewingDebtId = null;
    }

    public function showInsights(): void
    {
        $this->currentView = 'insights';
        $this->editingDebtId = null;
        $this->viewingDebtId = null;
    }

    public function showReconciliations(): void
    {
        $this->currentView = 'reconciliations';
        $this->editingDebtId = null;
        $this->viewingDebtId = null;
    }

    public function editDebt(int $debtId): void
    {
        $this->editingDebtId = $debtId;
        $this->viewingDebtId = null;
        $this->currentView = 'edit';
    }

    public function editFromDetail(): void
    {
        if ($this->viewingDebtId) {
            $this->editingDebtId = $this->viewingDebtId;
            $this->viewingDebtId = null;
            $this->currentView = 'edit';
        }
    }

    public function cancelEdit(): void
    {
        $this->editingDebtId = null;
        $this->viewingDebtId = null;
        $this->currentView = 'overview';
    }

    #[On('setView')]
    public function setView(string $view): void
    {
        match ($view) {
            'overview' => $this->showOverview(),
            'create' => $this->showCreate(),
            'progress' => $this->showProgress(),
            'recommendations' => $this->showRecommendations(),
            'insights' => $this->showInsights(),
            'reconciliations' => $this->showReconciliations(),
            default => null,
        };
    }

    #[On('debtUpdated')]
    public function onDebtUpdated(): void
    {
        $this->editingDebtId = null;
        $this->viewingDebtId = null;
        $this->currentView = 'overview';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $editingDebt = $this->editingDebtId
            ? \App\Models\Debt::find($this->editingDebtId)
            : null;

        $viewingDebt = $this->viewingDebtId
            ? \App\Models\Debt::with('payments')->find($this->viewingDebtId)
            : null;

        return view('livewire.debts.debt-layout', [
            'editingDebt' => $editingDebt,
            'viewingDebt' => $viewingDebt,
        ])->layout('components.layouts.app');
    }
}
