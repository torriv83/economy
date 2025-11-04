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
            'payment_date' => $paymentDate,
            'month_number' => $monthNumber,
            'payment_month' => $paymentDate->format('Y-m'),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
