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

        $balance = fake()->randomFloat(2, 10000, 500000);
        $type = fake()->randomElement(['kredittkort', 'forbrukslån']);
        $interestRate = fake()->randomFloat(2, 2.5, 15.0);

        // Calculate compliant minimum payment based on type
        if ($type === 'kredittkort') {
            $minimumPayment = max($balance * 0.03, 300);
        } else {
            $monthlyRate = ($interestRate / 100) / 12;
            if ($monthlyRate == 0) {
                $minimumPayment = $balance / 60;
            } else {
                $minimumPayment = ($monthlyRate * $balance) / (1 - pow(1 + $monthlyRate, -60));
            }
        }

        return [
            'name' => fake()->randomElement($debtTypes),
            'type' => $type,
            'balance' => $balance,
            'original_balance' => $balance,
            'interest_rate' => $interestRate,
            'minimum_payment' => round($minimumPayment, 2),
        ];
    }
}
