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
            'ynab_account_id' => null,
            'ynab_category_id' => null,
        ];
    }

    public function paidOff(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => 0,
        ]);
    }

    /**
     * Configure the loan as linked to a YNAB account.
     */
    public function linkedToYnabAccount(?string $accountId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'ynab_account_id' => $accountId ?? fake()->uuid(),
        ]);
    }

    /**
     * Configure the loan as linked to a YNAB category.
     */
    public function linkedToYnabCategory(?string $categoryId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'ynab_category_id' => $categoryId ?? fake()->uuid(),
        ]);
    }
}
