<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Debt;

class YnabSyncService
{
    /**
     * Import a single debt from YNAB data.
     *
     * @param  array<string, mixed>  $ynabDebt
     */
    public function importDebt(array $ynabDebt): Debt
    {
        $debt = Debt::create([
            'name' => $ynabDebt['name'],
            'balance' => $ynabDebt['balance'],
            'original_balance' => $ynabDebt['balance'],
            'interest_rate' => $ynabDebt['interest_rate'],
            'minimum_payment' => $ynabDebt['minimum_payment'] ?? 0,
            'ynab_account_id' => $ynabDebt['ynab_id'],
        ]);

        return $debt;
    }

    /**
     * Import multiple debts from YNAB at once.
     *
     * @param  array<int, array<string, mixed>>  $ynabDebts
     * @return int Number of debts imported
     */
    public function importAllDebts(array $ynabDebts): int
    {
        if (empty($ynabDebts)) {
            return 0;
        }

        foreach ($ynabDebts as $ynabDebt) {
            $this->importDebt($ynabDebt);
        }

        return count($ynabDebts);
    }

    /**
     * Link an existing local debt to a YNAB account.
     *
     * @param  array<string, mixed>  $ynabDebt
     * @param  array<int, string>  $fieldsToUpdate  Fields to update from YNAB data
     */
    public function linkDebtToYnab(Debt $debt, array $ynabDebt, array $fieldsToUpdate): void
    {
        $updateData = ['ynab_account_id' => $ynabDebt['ynab_id']];

        if (in_array('name', $fieldsToUpdate)) {
            $updateData['name'] = $ynabDebt['name'];
        }

        if (in_array('balance', $fieldsToUpdate)) {
            $updateData['balance'] = $ynabDebt['balance'];
        }

        if (in_array('interest_rate', $fieldsToUpdate)) {
            $updateData['interest_rate'] = $ynabDebt['interest_rate'];
        }

        if (in_array('minimum_payment', $fieldsToUpdate)) {
            $updateData['minimum_payment'] = $ynabDebt['minimum_payment'];
        }

        $debt->update($updateData);
    }
}
