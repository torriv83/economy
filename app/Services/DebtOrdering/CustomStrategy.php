<?php

declare(strict_types=1);

namespace App\Services\DebtOrdering;

use App\Contracts\DebtOrderingStrategy;
use Illuminate\Support\Collection;

/**
 * Custom debt ordering strategy.
 *
 * Orders debts by the user-defined custom_priority_order field.
 * This method allows users to choose their own repayment priority
 * based on personal preferences or circumstances.
 */
class CustomStrategy implements DebtOrderingStrategy
{
    /**
     * Order debts by custom priority order set by the user.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $debts  Collection of Debt models
     * @return \Illuminate\Support\Collection<int, \App\Models\Debt> Ordered collection by custom_priority_order (ascending)
     */
    public function order(Collection $debts): Collection
    {
        return $debts->sortBy('custom_priority_order')->values();
    }

    /**
     * Get the strategy identifier.
     */
    public function getKey(): string
    {
        return 'custom';
    }

    /**
     * Get the strategy name for UI display.
     */
    public function getName(): string
    {
        return __('strategies.custom.name');
    }

    /**
     * Get the strategy description for UI display.
     */
    public function getDescription(): string
    {
        return __('strategies.custom.description');
    }
}
