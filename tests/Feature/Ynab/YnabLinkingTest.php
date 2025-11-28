<?php

declare(strict_types=1);

use App\Livewire\DebtList;
use App\Models\Debt;
use App\Services\YnabDiscrepancyService;
use Livewire\Livewire;

test('can open link confirmation modal', function () {
    $debt = Debt::factory()->create([
        'name' => 'Local Debt',
        'balance' => 5000,
        'interest_rate' => 10.0,
        'ynab_account_id' => null,
    ]);

    $ynabDebt = [
        'ynab_id' => 'ynab-123',
        'name' => 'YNAB Debt',
        'balance' => 5500,
        'interest_rate' => 10.5,
        'minimum_payment' => 250,
    ];

    Livewire::test(DebtList::class)
        ->call('openLinkConfirmation', $debt->id, $ynabDebt)
        ->assertSet('showLinkConfirmation', true)
        ->assertSet('linkingLocalDebtId', $debt->id)
        ->assertSet('linkingYnabDebt', $ynabDebt)
        ->assertSet('selectedFieldsToUpdate', []);
});

test('can close link confirmation modal', function () {
    $debt = Debt::factory()->create();

    Livewire::test(DebtList::class)
        ->set('showLinkConfirmation', true)
        ->set('linkingLocalDebtId', $debt->id)
        ->set('linkingYnabDebt', ['ynab_id' => 'ynab-123', 'name' => 'YNAB Debt', 'balance' => 1000, 'interest_rate' => 5.0, 'minimum_payment' => 100])
        ->set('selectedFieldsToUpdate', ['name', 'balance'])
        ->call('closeLinkConfirmation')
        ->assertSet('showLinkConfirmation', false)
        ->assertSet('linkingLocalDebtId', null)
        ->assertSet('linkingYnabDebt', [])
        ->assertSet('selectedFieldsToUpdate', []);
});

test('can link debt to YNAB and update selected fields', function () {
    $debt = Debt::factory()->create([
        'name' => 'Local Debt',
        'balance' => 5000,
        'interest_rate' => 10.0,
        'minimum_payment' => 200,
        'ynab_account_id' => null,
    ]);

    $ynabDebt = [
        'ynab_id' => 'ynab-123',
        'name' => 'YNAB Debt',
        'balance' => 5500,
        'interest_rate' => 10.5,
        'minimum_payment' => 250,
    ];

    Livewire::test(DebtList::class)
        ->set('linkingLocalDebtId', $debt->id)
        ->set('linkingYnabDebt', $ynabDebt)
        ->set('selectedFieldsToUpdate', ['name', 'balance'])
        ->call('confirmLinkToExistingDebt')
        ->assertHasNoErrors();

    $debt->refresh();

    expect($debt->ynab_account_id)->toBe('ynab-123')
        ->and($debt->name)->toBe('YNAB Debt')
        ->and($debt->balance)->toBe(5500.0)
        ->and($debt->interest_rate)->toBe(10.0) // Should NOT be updated
        ->and($debt->minimum_payment)->toBe(200.0); // Should NOT be updated
});

test('can link debt to YNAB without updating any fields', function () {
    $debt = Debt::factory()->create([
        'name' => 'Local Debt',
        'balance' => 5000,
        'interest_rate' => 10.0,
        'ynab_account_id' => null,
    ]);

    $ynabDebt = [
        'ynab_id' => 'ynab-123',
        'name' => 'YNAB Debt',
        'balance' => 5500,
        'interest_rate' => 10.5,
        'minimum_payment' => 250,
    ];

    Livewire::test(DebtList::class)
        ->set('linkingLocalDebtId', $debt->id)
        ->set('linkingYnabDebt', $ynabDebt)
        ->set('selectedFieldsToUpdate', [])
        ->call('confirmLinkToExistingDebt')
        ->assertHasNoErrors();

    $debt->refresh();

    // Only account ID should be updated
    expect($debt->ynab_account_id)->toBe('ynab-123')
        ->and($debt->name)->toBe('Local Debt')
        ->and($debt->balance)->toBe(5000.0)
        ->and($debt->interest_rate)->toBe(10.0);
});

test('linked debts do not appear in potential matches', function () {
    // Create a local debt already linked to YNAB
    Debt::factory()->create([
        'name' => 'Linked Debt',
        'balance' => 5000,
        'ynab_account_id' => 'ynab-123',
    ]);

    // Create an unlinked local debt
    Debt::factory()->create([
        'name' => 'Unlinked Debt',
        'balance' => 3000,
        'ynab_account_id' => null,
    ]);

    $ynabDebts = collect([
        [
            'ynab_id' => 'ynab-123',
            'name' => 'Linked Debt in YNAB',
            'balance' => 5100,
            'interest_rate' => 10.0,
            'minimum_payment' => 250,
            'closed' => false,
        ],
        [
            'ynab_id' => 'ynab-456',
            'name' => 'Unlinked Similar',
            'balance' => 3100,
            'interest_rate' => 12.0,
            'minimum_payment' => 150,
            'closed' => false,
        ],
    ]);

    $localDebts = Debt::all();
    $service = new YnabDiscrepancyService;

    $discrepancies = $service->findDiscrepancies($ynabDebts, $localDebts);

    // The linked debt should not appear in potential matches
    expect($discrepancies['potential_matches'])->toHaveCount(1)
        ->and($discrepancies['potential_matches'][0]['ynab']['ynab_id'])->toBe('ynab-456')
        ->and($discrepancies['new'])->toBeEmpty();
});

test('linking removes debt from potential matches', function () {
    $debt = Debt::factory()->create([
        'name' => 'Local Debt',
        'balance' => 5000,
        'ynab_account_id' => null,
    ]);

    $ynabDebt = [
        'ynab_id' => 'ynab-123',
        'name' => 'YNAB Debt',
        'balance' => 5500,
        'interest_rate' => 10.5,
        'minimum_payment' => 250,
    ];

    $component = Livewire::test(DebtList::class)
        ->set('ynabDiscrepancies', [
            'new' => [],
            'closed' => [],
            'potential_matches' => [
                [
                    'ynab' => $ynabDebt,
                    'local' => [
                        'id' => $debt->id,
                        'name' => $debt->name,
                        'balance' => $debt->balance,
                        'interest_rate' => $debt->interest_rate,
                    ],
                ],
            ],
            'balance_mismatch' => [],
        ])
        ->set('linkingLocalDebtId', $debt->id)
        ->set('linkingYnabDebt', $ynabDebt)
        ->set('selectedFieldsToUpdate', [])
        ->call('confirmLinkToExistingDebt');

    // Potential matches should be empty after linking
    $component->assertSet('ynabDiscrepancies.potential_matches', []);
});

test('YnabDiscrepancyService detects balance mismatch for linked debts', function () {
    // Create a local debt linked to YNAB
    $linkedDebt = Debt::factory()->create([
        'name' => 'Linked Debt',
        'balance' => 5000,
        'interest_rate' => 10.0,
        'ynab_account_id' => 'ynab-123',
    ]);

    // YNAB data with different balance
    $ynabDebts = collect([
        [
            'ynab_id' => 'ynab-123',
            'name' => 'Linked Debt in YNAB',
            'balance' => 5500, // Different from local balance of 5000
            'interest_rate' => 10.0,
            'minimum_payment' => 250,
            'closed' => false,
        ],
    ]);

    $localDebts = Debt::all();
    $service = new YnabDiscrepancyService;

    $discrepancies = $service->findDiscrepancies($ynabDebts, $localDebts);

    expect($discrepancies['balance_mismatch'])->toHaveCount(1)
        ->and($discrepancies['balance_mismatch'][0]['local_debt']->id)->toBe($linkedDebt->id)
        ->and($discrepancies['balance_mismatch'][0]['local_balance'])->toBe(5000.0)
        ->and($discrepancies['balance_mismatch'][0]['ynab_balance'])->toEqual(5500)
        ->and($discrepancies['balance_mismatch'][0]['difference'])->toEqual(500);
});

test('YnabDiscrepancyService does not report mismatch when balances match', function () {
    // Create a local debt linked to YNAB
    Debt::factory()->create([
        'name' => 'Linked Debt',
        'balance' => 5000,
        'interest_rate' => 10.0,
        'ynab_account_id' => 'ynab-123',
    ]);

    // YNAB data with same balance
    $ynabDebts = collect([
        [
            'ynab_id' => 'ynab-123',
            'name' => 'Linked Debt in YNAB',
            'balance' => 5000, // Same as local balance
            'interest_rate' => 10.0,
            'minimum_payment' => 250,
            'closed' => false,
        ],
    ]);

    $localDebts = Debt::all();
    $service = new YnabDiscrepancyService;

    $discrepancies = $service->findDiscrepancies($ynabDebts, $localDebts);

    expect($discrepancies['balance_mismatch'])->toBeEmpty();
});

test('YnabDiscrepancyService ignores balance differences within floating point tolerance', function () {
    // Create a local debt linked to YNAB
    Debt::factory()->create([
        'name' => 'Linked Debt',
        'balance' => 5000.0005, // Very close to YNAB balance
        'interest_rate' => 10.0,
        'ynab_account_id' => 'ynab-123',
    ]);

    // YNAB data with nearly identical balance (within 0.001 tolerance)
    $ynabDebts = collect([
        [
            'ynab_id' => 'ynab-123',
            'name' => 'Linked Debt in YNAB',
            'balance' => 5000.0008, // Difference of 0.0003, within tolerance
            'interest_rate' => 10.0,
            'minimum_payment' => 250,
            'closed' => false,
        ],
    ]);

    $localDebts = Debt::all();
    $service = new YnabDiscrepancyService;

    $discrepancies = $service->findDiscrepancies($ynabDebts, $localDebts);

    expect($discrepancies['balance_mismatch'])->toBeEmpty();
});

test('openReconciliationFromYnab sets up reconciliation modal with YNAB balance', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 5000,
        'ynab_account_id' => 'ynab-123',
    ]);

    $ynabBalance = 5500.0;

    // Set up the initial state before rendering begins
    $component = Livewire::test(DebtList::class, ['ynabDiscrepancies' => [
        'new' => [],
        'closed' => [],
        'potential_matches' => [],
        'balance_mismatch' => [],
    ]])
        ->set('showYnabSync', true)
        ->call('openReconciliationFromYnab', $debt->id, $ynabBalance)
        ->assertSet('reconciliations.'.$debt->id.'.show', true)
        ->assertSet('reconciliations.'.$debt->id.'.balance', '5500')
        ->assertSet('reconciliations.'.$debt->id.'.notes', 'Avstemt mot YNAB')
        ->assertSet('showYnabSync', false);
});

test('openReconciliationFromYnab sets correct date format', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 5000,
        'ynab_account_id' => 'ynab-123',
    ]);

    Livewire::test(DebtList::class)
        ->set('ynabDiscrepancies', [
            'new' => [],
            'closed' => [],
            'potential_matches' => [],
            'balance_mismatch' => [],
        ])
        ->call('openReconciliationFromYnab', $debt->id, 5500.0)
        ->assertSet('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'));
});
