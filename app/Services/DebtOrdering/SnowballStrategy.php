<?php

declare(strict_types=1);

namespace App\Services\DebtOrdering;

use App\Contracts\DebtOrderingStrategy;
use Illuminate\Support\Collection;

/**
 * Snowball debt ordering strategy.
 *
 * Orders debts by lowest balance first. This method provides
 * psychological wins by paying off smaller debts quickly,
 * which can help maintain motivation during the debt payoff journey.
 */
class SnowballStrategy implements DebtOrderingStrategy
{
    /**
     * Order debts by lowest balance first.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts  Collection of Debt models
     * @return \Illuminate\Support\Collection<int, \App\Models\Debt> Ordered collection with lowest balance first
     */
    public function order(Collection $debts): Collection
    {
        return $debts->sortBy('balance')->values();
    }

    /**
     * Get the strategy identifier.
     */
    public function getKey(): string
    {
        return 'snowball';
    }

    /**
     * Get the strategy name for UI display.
     */
    public function getName(): string
    {
        return __('strategies.snowball.name');
    }

    /**
     * Get the strategy description for UI display.
     */
    public function getDescription(): string
    {
        return __('strategies.snowball.description');
    }
}
