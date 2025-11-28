<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Rules\MinimumPaymentRule;
use Closure;

trait HasDebtValidation
{
    /**
     * Get the balance to use for validation.
     * Override this method in components that need to use a different source for balance.
     */
    protected function getBalanceForValidation(): float
    {
        return (float) $this->balance;
    }

    /**
     * Get validation rules for debt fields.
     *
     * @param  bool  $includeBalance  Whether to include balance validation rules
     * @return array<string, array<int, mixed>>
     */
    protected function debtValidationRules(bool $includeBalance = true): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:forbrukslån,kredittkort'],
            'interestRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimumPayment' => [
                'required',
                'numeric',
                'min:0.01',
                $this->getMinimumPaymentRule(),
            ],
            'dueDay' => ['nullable', 'integer', 'min:1', 'max:31'],
        ];

        if ($includeBalance) {
            $rules['balance'] = ['required', 'numeric', 'min:0.01'];
        }

        return $rules;
    }

    /**
     * Get validation messages for debt fields.
     *
     * @return array<string, string>
     */
    protected function debtValidationMessages(): array
    {
        return [
            'name.required' => __('validation.debt.name_required'),
            'name.string' => __('validation.debt.name_string'),
            'name.max' => __('validation.debt.name_max'),
            'type.required' => __('validation.debt.type_required'),
            'type.in' => __('validation.debt.type_in'),
            'balance.required' => __('validation.debt.balance_required'),
            'balance.numeric' => __('validation.debt.balance_numeric'),
            'balance.min' => __('validation.debt.balance_min'),
            'interestRate.required' => __('validation.debt.interest_rate_required'),
            'interestRate.numeric' => __('validation.debt.interest_rate_numeric'),
            'interestRate.min' => __('validation.debt.interest_rate_min'),
            'interestRate.max' => __('validation.debt.interest_rate_max'),
            'minimumPayment.required' => __('validation.debt.minimum_payment_required'),
            'minimumPayment.numeric' => __('validation.debt.minimum_payment_numeric'),
            'minimumPayment.min' => __('validation.debt.minimum_payment_min'),
        ];
    }

    /**
     * Get the closure for minimum payment validation.
     */
    protected function getMinimumPaymentRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $balance = $this->getBalanceForValidation();

            // For kredittkort: use the existing 3% or 300 kr rule
            if ($this->type === 'kredittkort') {
                $rule = new MinimumPaymentRule(
                    $this->type,
                    $balance,
                    (float) $this->interestRate
                );
                $rule->validate($attribute, $value, $fail);
            } else {
                // For forbrukslån: just ensure payment > monthly interest
                $monthlyInterest = ($balance * ((float) $this->interestRate / 100)) / 12;

                if ((float) $value <= $monthlyInterest) {
                    $fail(__('validation.minimum_payment_must_cover_interest', [
                        'interest' => number_format($monthlyInterest, 2, ',', ' '),
                    ]));
                }
            }
        };
    }
}
