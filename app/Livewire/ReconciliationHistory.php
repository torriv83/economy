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

    public int $debtId;

    /**
     * Get reconciliations for the debt
     *
     * @return Collection<int, Payment>
     */
    #[Computed]
    public function reconciliations(): Collection
    {
        $debt = Debt::find($this->debtId);

        if (! $debt) {
            return collect();
        }

        return $this->paymentService->getReconciliationsForDebt($debt);
    }

    public function render(): View
    {
        return view('livewire.reconciliation-history');
    }
}
