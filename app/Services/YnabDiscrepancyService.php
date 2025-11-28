<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Debt;
use Illuminate\Support\Collection;

class YnabDiscrepancyService
{
    /**
     * Find all discrepancies between YNAB debts and local debts.
     *
     * @param  Collection<int, array<string, mixed>>  $ynabDebts
     * @param  Collection<int, Debt>  $localDebts
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function findDiscrepancies(Collection $ynabDebts, Collection $localDebts): array
    {
        return [
            'new' => $this->findNewDebts($ynabDebts, $localDebts),
            'closed' => $this->findClosedDebts($ynabDebts, $localDebts),
            'potential_matches' => $this->findPotentialMatches($ynabDebts, $localDebts),
            'balance_mismatch' => $this->findBalanceMismatches($ynabDebts, $localDebts),
        ];
    }

    /**
     * Find unlinked YNAB debts that don't exist locally.
     *
     * @param  Collection<int, array<string, mixed>>  $ynabDebts
     * @param  Collection<int, Debt>  $localDebts
     * @return array<int, array<string, mixed>>
     */
    private function findNewDebts(Collection $ynabDebts, Collection $localDebts): array
    {
        $newDebts = [];

        foreach ($ynabDebts as $ynabDebt) {
            // Skip if already linked by YNAB account ID
            $linkedByAccountId = $this->findLinkedDebt($ynabDebt, $localDebts);
            if ($linkedByAccountId) {
                continue;
            }

            // Skip closed debts
            if ($ynabDebt['closed']) {
                continue;
            }

            // Skip if there's a potential match
            $potentialMatch = $this->findPotentialMatch($ynabDebt['name'], $localDebts);
            if ($potentialMatch) {
                continue;
            }

            $newDebts[] = $ynabDebt;
        }

        return $newDebts;
    }

    /**
     * Find local debts that are closed in YNAB.
     *
     * @param  Collection<int, array<string, mixed>>  $ynabDebts
     * @param  Collection<int, Debt>  $localDebts
     * @return array<int, array<string, mixed>>
     */
    private function findClosedDebts(Collection $ynabDebts, Collection $localDebts): array
    {
        $closedDebts = [];

        foreach ($localDebts as $localDebt) {
            $ynabDebt = $ynabDebts->first(function ($ynabDebt) use ($localDebt) {
                return ($localDebt->ynab_account_id && $localDebt->ynab_account_id === $ynabDebt['ynab_id'])
                    || strtolower($ynabDebt['name']) === strtolower($localDebt->name);
            });

            if ($ynabDebt && $ynabDebt['closed']) {
                $closedDebts[] = [
                    'id' => $localDebt->id,
                    'name' => $localDebt->name,
                    'balance' => $localDebt->balance,
                ];
            }
        }

        return $closedDebts;
    }

    /**
     * Find YNAB debts that might match existing local debts by name.
     *
     * @param  Collection<int, array<string, mixed>>  $ynabDebts
     * @param  Collection<int, Debt>  $localDebts
     * @return array<int, array<string, mixed>>
     */
    private function findPotentialMatches(Collection $ynabDebts, Collection $localDebts): array
    {
        $potentialMatches = [];

        foreach ($ynabDebts as $ynabDebt) {
            // Skip if already linked by YNAB account ID
            $linkedByAccountId = $this->findLinkedDebt($ynabDebt, $localDebts);
            if ($linkedByAccountId) {
                continue;
            }

            // Skip closed debts
            if ($ynabDebt['closed']) {
                continue;
            }

            $potentialMatch = $this->findPotentialMatch($ynabDebt['name'], $localDebts);
            if ($potentialMatch) {
                $potentialMatches[] = [
                    'ynab' => $ynabDebt,
                    'local' => [
                        'id' => $potentialMatch->id,
                        'name' => $potentialMatch->name,
                        'balance' => $potentialMatch->balance,
                        'interest_rate' => $potentialMatch->interest_rate,
                    ],
                ];
            }
        }

        return $potentialMatches;
    }

    /**
     * Find linked debts with different balances.
     *
     * @param  Collection<int, array<string, mixed>>  $ynabDebts
     * @param  Collection<int, Debt>  $localDebts
     * @return array<int, array<string, mixed>>
     */
    private function findBalanceMismatches(Collection $ynabDebts, Collection $localDebts): array
    {
        $mismatches = [];

        foreach ($ynabDebts as $ynabDebt) {
            $linkedDebt = $this->findLinkedDebt($ynabDebt, $localDebts);

            if (! $linkedDebt) {
                continue;
            }

            // Check if balances are different (use 0.001 tolerance for floating point)
            if (abs($ynabDebt['balance'] - $linkedDebt->balance) > 0.001) {
                $mismatches[] = [
                    'local_debt' => $linkedDebt,
                    'ynab_debt' => $ynabDebt,
                    'local_balance' => $linkedDebt->balance,
                    'ynab_balance' => $ynabDebt['balance'],
                    'difference' => round($ynabDebt['balance'] - $linkedDebt->balance, 2),
                ];
            }
        }

        return $mismatches;
    }

    /**
     * Find a local debt that is linked to the given YNAB debt by account ID.
     *
     * @param  array<string, mixed>  $ynabDebt
     * @param  Collection<int, Debt>  $localDebts
     */
    private function findLinkedDebt(array $ynabDebt, Collection $localDebts): ?Debt
    {
        return $localDebts->first(function ($localDebt) use ($ynabDebt) {
            return $localDebt->ynab_account_id === $ynabDebt['ynab_id'];
        });
    }

    /**
     * Find a potential match for a YNAB debt by comparing names.
     *
     * @param  Collection<int, Debt>  $localDebts
     */
    private function findPotentialMatch(string $ynabName, Collection $localDebts): ?Debt
    {
        $normalizedYnabName = strtolower(trim($ynabName));

        return $localDebts->first(function ($localDebt) use ($normalizedYnabName) {
            // Skip debts that are already linked to a YNAB account
            if ($localDebt->ynab_account_id) {
                return false;
            }

            $normalizedLocalName = strtolower(trim($localDebt->name));

            // Check if one name contains the other
            return str_contains($normalizedYnabName, $normalizedLocalName)
                || str_contains($normalizedLocalName, $normalizedYnabName)
                || similar_text($normalizedYnabName, $normalizedLocalName) > 5;
        });
    }
}
