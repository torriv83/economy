<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'value' => fake()->word(),
            'type' => 'string',
            'group' => 'general',
        ];
    }

    /**
     * Encrypted setting type.
     */
    public function encrypted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'encrypted',
        ]);
    }

    /**
     * Boolean setting type.
     */
    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'value' => fake()->boolean() ? '1' : '0',
        ]);
    }

    /**
     * Integer setting type.
     */
    public function integer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'integer',
            'value' => (string) fake()->numberBetween(1, 1000),
        ]);
    }

    /**
     * Float setting type.
     */
    public function float(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'float',
            'value' => (string) fake()->randomFloat(2, 0, 100),
        ]);
    }

    /**
     * YNAB group setting.
     */
    public function ynabGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'group' => 'ynab',
        ]);
    }

    /**
     * Debt group setting.
     */
    public function debtGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'group' => 'debt',
        ]);
    }
}
