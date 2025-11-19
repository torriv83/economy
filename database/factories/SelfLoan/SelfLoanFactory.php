<?php

namespace Database\Factories\SelfLoan;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SelfLoan\SelfLoan>
 */
class SelfLoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $loanNames = [
            'Emergency Fund Withdrawal',
            'Car Repair Loan',
            'Home Improvement Advance',
            'Medical Expense Loan',
            'Vacation Fund Borrow',
        ];

        $originalAmount = fake()->randomFloat(2, 5000, 50000);

        return [
            'name' => fake()->randomElement($loanNames),
            'description' => fake()->optional(0.6)->sentence(),
            'original_amount' => $originalAmount,
            'current_balance' => fake()->randomFloat(2, 0, $originalAmount),
        ];
    }

    public function paidOff(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => 0,
        ]);
    }
}
