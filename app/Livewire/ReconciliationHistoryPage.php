<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\HasReconciliationModals;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Reconciliation History')]
class ReconciliationHistoryPage extends Component
{
    use HasReconciliationModals;

    public ?int $filterDebtId = null;

    /**
     * Reset pagination when filter changes
     */
    public function updatedFilterDebtId(): void
    {
        // Reset computed properties cache
        unset($this->reconciliations);
    }

    /**
     * Get all reconciliations, optionally filtered by debt
     *
     * @return Collection<int, Payment>
     */
    #[Computed]
    public function reconciliations(): Collection
    {
        $query = Payment::with('debt')
            ->where('is_reconciliation_adjustment', true);

        if ($this->filterDebtId !== null) {
            $query->where('debt_id', $this->filterDebtId);
        }

        return $query->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all debts for the filter dropdown
     *
     * @return Collection<int, Debt>
     */
    #[Computed]
    public function debts(): Collection
    {
        return Debt::orderBy('name')->get();
    }

    /**
     * Hook called after a reconciliation is saved
     */
    protected function afterReconciliationSaved(): void
    {
        // Reset computed property cache to refresh list
        unset($this->reconciliations);
    }

    /**
     * Hook called after a reconciliation is deleted
     */
    protected function afterReconciliationDeleted(): void
    {
        // Reset computed property cache to refresh list
        unset($this->reconciliations);
    }

    public function render(): View
    {
        return view('livewire.reconciliation-history-page')
            ->layout('components.layouts.app');
    }
}
