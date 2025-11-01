<?php

namespace App\Livewire;

use Livewire\Component;

class DebtList extends Component
{
    public array $debts = [
        ['id' => 1, 'name' => 'Kredittkort', 'balance' => 50000, 'interestRate' => 8.5, 'minimumPayment' => 500],
        ['id' => 2, 'name' => 'Studielån', 'balance' => 200000, 'interestRate' => 2.5, 'minimumPayment' => null],
        ['id' => 3, 'name' => 'Billån', 'balance' => 75000, 'interestRate' => 5.0, 'minimumPayment' => 1200],
    ];

    public function getTotalDebtProperty(): int
    {
        return array_sum(array_column($this->debts, 'balance'));
    }

    public function getDebtsCountProperty(): int
    {
        return count($this->debts);
    }

    public function deleteDebt(int $id): void
    {
        $this->debts = array_values(array_filter($this->debts, fn ($debt) => $debt['id'] !== $id));
    }

    public function render()
    {
        return view('livewire.debt-list')->layout('components.layouts.app');
    }
}
