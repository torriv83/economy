<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use Illuminate\Support\Collection;
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

    /**
     * Get all debts with caching to prevent N+1 queries.
     * Uses Laravel's once() helper to cache within a single request.
     */
    protected function getDebts(): Collection
    {
        return once(fn () => Debt::all());
    }

    public function getSnowballDataProperty(): array
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return [
                'months' => 0,
                'totalInterest' => 0,
                'order' => [],
                'savings' => 0,
            ];
        }

        $comparison = $this->calculationService->compareStrategies($debts, $this->extraPayment);

        return $comparison['snowball'];
    }

    public function getAvalancheDataProperty(): array
    {
        $debts = $this->getDebts();

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

    /**
     * Get the number of months to pay off all debts using only minimum payments.
     * Each debt is calculated independently with no reallocation of freed-up payments.
     */
    public function getMinimumPaymentMonthsProperty(): int
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return 0;
        }

        return $this->calculationService->calculateMinimumPaymentsOnly($debts);
    }

    /**
     * Get the total interest paid when using only minimum payments.
     * Each debt is calculated independently with no reallocation of freed-up payments.
     */
    public function getMinimumPaymentInterestProperty(): float
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return 0.0;
        }

        return $this->calculationService->calculateMinimumPaymentsInterest($debts);
    }

    /**
     * Get savings data for Snowball strategy compared to minimum payments.
     *
     * @return array{monthsSaved: int, yearsSaved: int, remainingMonths: int, interestSaved: float}
     */
    public function getSnowballSavingsProperty(): array
    {
        $monthsSaved = max(0, $this->minimumPaymentMonths - $this->snowballData['months']);
        $interestSaved = max(0, $this->minimumPaymentInterest - $this->snowballData['totalInterest']);

        return [
            'monthsSaved' => $monthsSaved,
            'yearsSaved' => (int) floor($monthsSaved / 12),
            'remainingMonths' => $monthsSaved % 12,
            'interestSaved' => $interestSaved,
        ];
    }

    /**
     * Get savings data for Avalanche strategy compared to minimum payments.
     *
     * @return array{monthsSaved: int, yearsSaved: int, remainingMonths: int, interestSaved: float}
     */
    public function getAvalancheSavingsProperty(): array
    {
        $monthsSaved = max(0, $this->minimumPaymentMonths - $this->avalancheData['months']);
        $interestSaved = max(0, $this->minimumPaymentInterest - $this->avalancheData['totalInterest']);

        return [
            'monthsSaved' => $monthsSaved,
            'yearsSaved' => (int) floor($monthsSaved / 12),
            'remainingMonths' => $monthsSaved % 12,
            'interestSaved' => $interestSaved,
        ];
    }

    public function getOrderedDebtsProperty(): array
    {
        $debts = $this->getDebts();

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

    /**
     * Determine which strategy saves the most money compared to minimum payments.
     * Returns 'snowball' or 'avalanche' based on which has higher savings.
     */
    public function getBestStrategyProperty(): string
    {
        $snowballSavings = $this->snowballData['savings'] ?? 0;
        $avalancheSavings = $this->avalancheData['savings'] ?? 0;

        return $avalancheSavings >= $snowballSavings ? 'avalanche' : 'snowball';
    }

    public function render()
    {
        return view('livewire.strategy-comparison')->layout('components.layouts.app');
    }
}
