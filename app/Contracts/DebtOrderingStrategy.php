<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface for debt ordering strategies.
 *
 * Implementations of this interface define how debts should be prioritized
 * for repayment. Common strategies include Snowball (lowest balance first)
 * and Avalanche (highest interest rate first).
 */
interface DebtOrderingStrategy
{
    /**
     * Order debts according to this strategy.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts  Collection of Debt models to order
     * @return \Illuminate\Support\Collection<int, \App\Models\Debt> Ordered collection with reindexed keys
     */
    public function order(Collection $debts): Collection;

    /**
     * Get the strategy identifier.
     *
     * This is used as a key in configuration and for strategy lookup.
     */
    public function getKey(): string;

    /**
     * Get the strategy name for UI display.
     *
     * This should return a localized, human-readable name.
     */
    public function getName(): string;

    /**
     * Get the strategy description for UI display.
     *
     * This should return a localized description explaining
     * how the strategy works and its benefits.
     */
    public function getDescription(): string;
}
