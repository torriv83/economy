<?php

namespace Database\Factories\SelfLoan;

use App\Models\SelfLoan\SelfLoan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SelfLoan\SelfLoanRepayment>
 */
class SelfLoanRepaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'self_loan_id' => SelfLoan::factory(),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'notes' => fake()->optional(0.5)->sentence(),
            'paid_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
