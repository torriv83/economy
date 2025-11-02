<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use Livewire\Component;

class StrategyComparison extends Component
{
    public float $extraPayment = 2000;

    public function getSnowballDataProperty(): array
    {
        $debts = Debt::all();

        if ($debts->isEmpty()) {
            return [
                'months' => 0,
                'totalInterest' => 0,
                'order' => [],
            ];
        }

        $service = new DebtCalculationService;
        $comparison = $service->compareStrategies($debts, $this->extraPayment);

        return $comparison['snowball'];
    }

    public function getAvalancheDataProperty(): array
    {
        $debts = Debt::all();

        if ($debts->isEmpty()) {
            return [
                'months' => 0,
                'totalInterest' => 0,
                'order' => [],
                'savings' => 0,
            ];
        }

        $service = new DebtCalculationService;
        $comparison = $service->compareStrategies($debts, $this->extraPayment);

        return $comparison['avalanche'];
    }

    public function getOrderedDebtsProperty(): array
    {
        $debts = Debt::all();

        if ($debts->isEmpty()) {
            return [
                'snowball' => [],
                'avalanche' => [],
            ];
        }

        $service = new DebtCalculationService;

        return [
            'snowball' => $service->orderBySnowball($debts)->map(function ($debt) {
                return [
                    'name' => $debt->name,
                    'balance' => $debt->balance,
                    'interestRate' => $debt->interest_rate,
                ];
            })->toArray(),
            'avalanche' => $service->orderByAvalanche($debts)->map(function ($debt) {
                return [
                    'name' => $debt->name,
                    'balance' => $debt->balance,
                    'interestRate' => $debt->interest_rate,
                ];
            })->toArray(),
        ];
    }

    public function render()
    {
        return view('livewire.strategy-comparison')->layout('components.layouts.app');
    }
}
