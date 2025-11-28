<?php

declare(strict_types=1);

namespace App\Services\DebtOrdering;

use App\Contracts\DebtOrderingStrategy;
use Illuminate\Support\Collection;

/**
 * Avalanche debt ordering strategy.
 *
 * Orders debts by highest interest rate first. This method
 * minimizes total interest paid over time, making it the
 * mathematically optimal approach for debt repayment.
 */
class AvalancheStrategy implements DebtOrderingStrategy
{
    /**
     * Order debts by highest interest rate first.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts  Collection of Debt models
     * @return \Illuminate\Support\Collection<int, \App\Models\Debt> Ordered collection with highest interest rate first
     */
    public function order(Collection $debts): Collection
    {
        return $debts->sortByDesc('interest_rate')->values();
    }

    /**
     * Get the strategy identifier.
     */
    public function getKey(): string
    {
        return 'avalanche';
    }

    /**
     * Get the strategy name for UI display.
     */
    public function getName(): string
    {
        return __('strategies.avalanche.name');
    }

    /**
     * Get the strategy description for UI display.
     */
    public function getDescription(): string
    {
        return __('strategies.avalanche.description');
    }
}
