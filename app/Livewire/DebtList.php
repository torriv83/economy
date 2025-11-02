<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCalculationService;
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

    public function getLastUpdatedProperty(): ?string
    {
        $latestDebt = Debt::latest('updated_at')->first();

        if (! $latestDebt) {
            return null;
        }

        return $latestDebt->updated_at->locale('nb')->translatedFormat('d. F Y \k\l. H:i');
    }

    public function getPayoffEstimateProperty(): ?array
    {
        $debts = Debt::all();

        if ($debts->isEmpty()) {
            return null;
        }

        // Check if all debts have minimum payments
        $hasAllMinimums = $debts->every(fn ($debt) => $debt->minimum_payment !== null && $debt->minimum_payment > 0);

        if (! $hasAllMinimums) {
            return null;
        }

        $service = new DebtCalculationService;
        $schedule = $service->generatePaymentSchedule($debts, 0, 'avalanche');

        $months = $schedule['months'];
        $years = floor($months / 12);
        $remainingMonths = $months % 12;

        return [
            'years' => $years,
            'months' => $remainingMonths,
            'totalMonths' => $months,
        ];
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
