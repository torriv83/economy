<?php

declare(strict_types=1);

use App\Livewire\DebtList;
use App\Models\Debt;
use Livewire\Volt\Volt;

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

    Volt::test(DebtList::class)
        ->call('openLinkConfirmation', $debt->id, $ynabDebt)
        ->assertSet('showLinkConfirmation', true)
        ->assertSet('linkingLocalDebtId', $debt->id)
        ->assertSet('linkingYnabDebt', $ynabDebt)
        ->assertSet('selectedFieldsToUpdate', []);
});

test('can close link confirmation modal', function () {
    $debt = Debt::factory()->create();

    Volt::test(DebtList::class)
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

    Volt::test(DebtList::class)
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

    Volt::test(DebtList::class)
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
    $linkedDebt = Debt::factory()->create([
        'name' => 'Linked Debt',
        'balance' => 5000,
        'ynab_account_id' => 'ynab-123',
    ]);

    // Create an unlinked local debt
    $unlinkedDebt = Debt::factory()->create([
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

    $component = new DebtList;
    $component->boot(app(\App\Services\DebtCalculationService::class), app(\App\Services\YnabService::class), app(\App\Services\PaymentService::class), app(\App\Services\PayoffSettingsService::class));

    $discrepancies = $component->findDiscrepancies($ynabDebts, $localDebts);

    // The linked debt should not appear in any discrepancy list
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

    $component = Volt::test(DebtList::class)
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
        ])
        ->set('linkingLocalDebtId', $debt->id)
        ->set('linkingYnabDebt', $ynabDebt)
        ->set('selectedFieldsToUpdate', [])
        ->call('confirmLinkToExistingDebt');

    // Potential matches should be empty after linking
    $component->assertSet('ynabDiscrepancies.potential_matches', []);
});
