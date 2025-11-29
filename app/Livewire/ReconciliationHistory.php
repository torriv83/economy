<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\HasReconciliationModals;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ReconciliationHistory extends Component
{
    use HasReconciliationModals;

    /**
     * When set, locks the component to show only this debt's reconciliations (no filter shown)
     */
    public ?int $debtId = null;

    /**
     * Used for filtering when viewing all reconciliations (debtId is null)
     */
    public ?int $filterDebtId = null;

    public function mount(?int $debtId = null): void
    {
        $this->debtId = $debtId;
    }

    /**
     * Reset computed properties cache when filter changes
     */
    public function updatedFilterDebtId(): void
    {
        unset($this->reconciliations);
    }

    /**
     * Get all reconciliations, filtered by debt when debtId is set
     *
     * @return Collection<int, Payment>
     */
    #[Computed]
    public function reconciliations(): Collection
    {
        $query = Payment::with('debt')
            ->where('is_reconciliation_adjustment', true);

        // If viewing specific debt history, always filter by that debt
        if ($this->debtId !== null) {
            $query->where('debt_id', $this->debtId);
        } elseif ($this->filterDebtId !== null) {
            // Otherwise use the filter dropdown value
            $query->where('debt_id', $this->filterDebtId);
        }

        return $query->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the debt being viewed (when debtId is set)
     */
    #[Computed]
    public function debt(): ?Debt
    {
        if ($this->debtId === null) {
            return null;
        }

        return Debt::find($this->debtId);
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
        unset($this->reconciliations);
    }

    /**
     * Hook called after a reconciliation is deleted
     */
    protected function afterReconciliationDeleted(): void
    {
        unset($this->reconciliations);
    }

    public function render(): View
    {
        return view('livewire.reconciliation-history');
    }
}
