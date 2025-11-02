<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Debt>
 */
class DebtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $debtTypes = [
            'Kredittkort',
            'Studielån',
            'Billån',
            'Boliglån',
            'Forbrukslån',
        ];

        return [
            'name' => fake()->randomElement($debtTypes),
            'balance' => fake()->randomFloat(2, 10000, 500000),
            'interest_rate' => fake()->randomFloat(2, 2.5, 15.0),
            'minimum_payment' => fake()->optional(0.7)->randomFloat(2, 500, 5000),
        ];
    }
}
