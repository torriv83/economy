<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinimumPaymentRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        public string $type,
        public float $balance,
        public float $interestRate
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $minimumPayment = (float) $value;
        $requiredMinimum = $this->calculateRequiredMinimum();

        if ($minimumPayment < $requiredMinimum) {
            // Round up to ensure users see the actual minimum they need to enter
            $formattedMinimum = number_format(ceil($requiredMinimum), 0, ',', ' ');

            if ($this->type === 'kredittkort') {
                $fail(__('app.minimum_payment_validation_kredittkort', ['minimum' => $formattedMinimum]));
            } else {
                $fail(__('app.minimum_payment_validation_forbrukslån', ['minimum' => $formattedMinimum]));
            }
        }
    }

    /**
     * Calculate the required minimum payment based on debt type.
     */
    protected function calculateRequiredMinimum(): float
    {
        if ($this->type === 'kredittkort') {
            // Credit card: 3% of balance or 300 kr, whichever is higher
            $percentage = config('debt.minimum_payment.kredittkort.percentage', 0.03);
            $minimumAmount = config('debt.minimum_payment.kredittkort.minimum_amount', 300);

            return max($this->balance * $percentage, $minimumAmount);
        }

        // Forbrukslån: Calculate payment that pays off debt in 60 months with interest
        // Using amortization formula: P = (r * PV) / (1 - (1 + r)^-n)
        // where r = monthly rate, PV = balance, n = 60 months
        $monthlyRate = ($this->interestRate / 100) / 12;
        $numberOfMonths = config('debt.minimum_payment.forbrukslån.payoff_months', 60);

        if ($monthlyRate == 0) {
            // If no interest, simply divide balance by number of months
            return $this->balance / $numberOfMonths;
        }

        $payment = ($monthlyRate * $this->balance) / (1 - pow(1 + $monthlyRate, -$numberOfMonths));

        return round($payment, 2);
    }
}
