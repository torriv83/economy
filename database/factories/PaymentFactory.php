<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plannedAmount = fake()->randomFloat(2, 100, 2000);
        $paymentDate = fake()->dateTimeBetween('-12 months', 'now');
        $monthNumber = fake()->numberBetween(1, 60);

        return [
            'debt_id' => \App\Models\Debt::factory(),
            'planned_amount' => $plannedAmount,
            'actual_amount' => $plannedAmount,
            'interest_paid' => 0,
            'principal_paid' => $plannedAmount,
            'payment_date' => $paymentDate,
            'month_number' => $monthNumber,
            'payment_month' => $paymentDate->format('Y-m'),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Configure the factory to calculate interest and principal automatically
     */
    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\Payment $payment) {
            // Skip automatic interest calculation for reconciliations - they represent
            // balance adjustments (not real payments) and have pre-calculated principal_paid values
            if ($payment->debt && $payment->actual_amount > 0 && ! $payment->is_reconciliation_adjustment) {
                /** @var \App\Models\Debt $debt */
                $debt = $payment->debt;
                $monthlyInterest = round($debt->balance * ($debt->interest_rate / 100) / 12, 2);

                $payment->interest_paid = min($payment->actual_amount, $monthlyInterest);
                $payment->principal_paid = max(0, $payment->actual_amount - $monthlyInterest);
            }
        });
    }

    /**
     * Create a reconciliation adjustment payment
     */
    public function reconciliation(): static
    {
        $paymentDate = fake()->dateTimeBetween('-6 months', 'now');
        $principalPaid = fake()->randomFloat(2, -500, 500);

        return $this->state(fn (array $attributes) => [
            'planned_amount' => 0,
            'actual_amount' => -$principalPaid,
            'interest_paid' => 0,
            'principal_paid' => $principalPaid,
            'payment_date' => $paymentDate,
            'month_number' => null,
            'payment_month' => $paymentDate->format('Y-m'),
            'notes' => fake()->optional(0.5)->sentence(),
            'is_reconciliation_adjustment' => true,
        ]);
    }
}
