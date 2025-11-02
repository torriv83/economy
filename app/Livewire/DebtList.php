<?php

namespace App\Livewire;

use App\Models\Debt;
use Livewire\Component;

class DebtList extends Component
{
    public function getDebtsProperty(): array
    {
        return Debt::all()->map(function ($debt) {
            return [
                'id' => $debt->id,
                'name' => $debt->name,
                'balance' => $debt->balance,
                'interestRate' => $debt->interest_rate,
                'minimumPayment' => $debt->minimum_payment,
            ];
        })->toArray();
    }

    public function getTotalDebtProperty(): float
    {
        return Debt::all()->sum('balance');
    }

    public function getDebtsCountProperty(): int
    {
        return Debt::count();
    }

    public function deleteDebt(int $id): void
    {
        $debt = Debt::find($id);

        if ($debt) {
            $debt->delete();
            session()->flash('message', 'Gjeld slettet.');
        }
    }

    public function render()
    {
        return view('livewire.debt-list')->layout('components.layouts.app');
    }
}
