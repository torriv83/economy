<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use Livewire\Component;

class StrategyComparison extends Component
{
    public float $extraPayment = 2000;

    protected DebtCalculationService $calculationService;

    public function boot(DebtCalculationService $service): void
    {
        $this->calculationService = $service;
    }

    public function rules(): array
    {
        return [
            'extraPayment' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'extraPayment.required' => __('validation.required', ['attribute' => __('app.extra_monthly_payment')]),
            'extraPayment.numeric' => __('validation.numeric', ['attribute' => __('app.extra_monthly_payment')]),
            'extraPayment.min' => __('validation.min.numeric', ['attribute' => __('app.extra_monthly_payment'), 'min' => 0]),
            'extraPayment.max' => __('validation.max.numeric', ['attribute' => __('app.extra_monthly_payment'), 'max' => '1 000 000']),
        ];
    }

    public function updatedExtraPayment(): void
    {
        $this->validate(['extraPayment' => $this->rules()['extraPayment']]);
    }

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

        $comparison = $this->calculationService->compareStrategies($debts, $this->extraPayment);

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

        $comparison = $this->calculationService->compareStrategies($debts, $this->extraPayment);

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

        return [
            'snowball' => $this->calculationService->orderBySnowball($debts)->map(function ($debt) {
                return [
                    'name' => $debt->name,
                    'balance' => $debt->balance,
                    'interestRate' => $debt->interest_rate,
                ];
            })->toArray(),
            'avalanche' => $this->calculationService->orderByAvalanche($debts)->map(function ($debt) {
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
