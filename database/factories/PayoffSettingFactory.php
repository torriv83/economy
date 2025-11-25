<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayoffSetting>
 */
class PayoffSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'extra_payment' => 2000.00,
            'strategy' => 'avalanche',
        ];
    }

    public function snowball(): static
    {
        return $this->state(fn (array $attributes) => [
            'strategy' => 'snowball',
        ]);
    }

    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'strategy' => 'custom',
        ]);
    }

    public function withExtraPayment(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'extra_payment' => $amount,
        ]);
    }
}
