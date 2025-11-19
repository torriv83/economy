<?php

declare(strict_types=1);

use App\Livewire\DebtList;
use App\Models\Debt;
use Livewire\Livewire;

test('can import debt from YNAB with all required fields', function () {
    $ynabDebt = [
        'name' => 'Test YNAB Debt',
        'balance' => 5000.00,
        'interest_rate' => 15.5,
        'minimum_payment' => 150.00,
        'ynab_id' => 'ynab-test-id-123',
    ];

    Livewire::test(DebtList::class)
        ->call('importYnabDebt', $ynabDebt);

    expect(Debt::where('name', 'Test YNAB Debt')->exists())->toBeTrue();

    $debt = Debt::where('name', 'Test YNAB Debt')->first();
    expect($debt->balance)->toBe(5000.00);
    expect($debt->original_balance)->toBe(5000.00);
    expect($debt->interest_rate)->toBe(15.5);
    expect($debt->minimum_payment)->toBe(150.00);
    expect($debt->ynab_account_id)->toBe('ynab-test-id-123');
});

test('imported debt has original balance set to current balance', function () {
    $ynabDebt = [
        'name' => 'Another YNAB Debt',
        'balance' => 12000.50,
        'interest_rate' => 8.0,
        'minimum_payment' => 300.00,
        'ynab_id' => 'ynab-test-id-456',
    ];

    Livewire::test(DebtList::class)
        ->call('importYnabDebt', $ynabDebt);

    $debt = Debt::where('name', 'Another YNAB Debt')->first();
    expect($debt->original_balance)->toBe($debt->balance);
});

test('can import all debts from YNAB at once', function () {
    $ynabDebts = [
        [
            'name' => 'First Debt',
            'balance' => 1000.00,
            'interest_rate' => 10.0,
            'minimum_payment' => 50.00,
            'ynab_id' => 'ynab-1',
        ],
        [
            'name' => 'Second Debt',
            'balance' => 2000.00,
            'interest_rate' => 12.0,
            'minimum_payment' => 100.00,
            'ynab_id' => 'ynab-2',
        ],
        [
            'name' => 'Third Debt',
            'balance' => 3000.00,
            'interest_rate' => 15.0,
            'minimum_payment' => 150.00,
            'ynab_id' => 'ynab-3',
        ],
    ];

    Livewire::test(DebtList::class)
        ->set('ynabDiscrepancies', ['new' => $ynabDebts])
        ->call('importAllYnabDebts');

    expect(Debt::count())->toBe(3);
    expect(Debt::where('name', 'First Debt')->exists())->toBeTrue();
    expect(Debt::where('name', 'Second Debt')->exists())->toBeTrue();
    expect(Debt::where('name', 'Third Debt')->exists())->toBeTrue();

    $firstDebt = Debt::where('name', 'First Debt')->first();
    expect($firstDebt->original_balance)->toBe(1000.00);
    expect($firstDebt->ynab_account_id)->toBe('ynab-1');
});

test('import all clears the new debts array', function () {
    $ynabDebts = [
        [
            'name' => 'Test Debt',
            'balance' => 1000.00,
            'interest_rate' => 10.0,
            'minimum_payment' => 50.00,
            'ynab_id' => 'ynab-test',
        ],
    ];

    $component = Livewire::test(DebtList::class)
        ->set('ynabDiscrepancies', ['new' => $ynabDebts])
        ->call('importAllYnabDebts');

    expect($component->get('ynabDiscrepancies')['new'])->toBeEmpty();
});

test('import all does nothing when no new debts exist', function () {
    Livewire::test(DebtList::class)
        ->set('ynabDiscrepancies', ['new' => []])
        ->call('importAllYnabDebts');

    expect(Debt::count())->toBe(0);
});

test('can import debt with null minimum_payment', function () {
    $ynabDebt = [
        'name' => 'Debt Without Min Payment',
        'balance' => 5000.00,
        'interest_rate' => 10.0,
        'minimum_payment' => null,
        'ynab_id' => 'ynab-null-min',
    ];

    Livewire::test(DebtList::class)
        ->call('importYnabDebt', $ynabDebt);

    $debt = Debt::where('name', 'Debt Without Min Payment')->first();
    expect($debt->minimum_payment)->toBe(0.0);
});

test('can import all debts when some have null minimum_payment', function () {
    $ynabDebts = [
        [
            'name' => 'Debt With Min Payment',
            'balance' => 1000.00,
            'interest_rate' => 10.0,
            'minimum_payment' => 50.00,
            'ynab_id' => 'ynab-with-min',
        ],
        [
            'name' => 'Debt Without Min Payment',
            'balance' => 2000.00,
            'interest_rate' => 12.0,
            'minimum_payment' => null,
            'ynab_id' => 'ynab-without-min',
        ],
    ];

    Livewire::test(DebtList::class)
        ->set('ynabDiscrepancies', ['new' => $ynabDebts])
        ->call('importAllYnabDebts');

    expect(Debt::count())->toBe(2);

    $debtWithMin = Debt::where('name', 'Debt With Min Payment')->first();
    expect($debtWithMin->minimum_payment)->toBe(50.0);

    $debtWithoutMin = Debt::where('name', 'Debt Without Min Payment')->first();
    expect($debtWithoutMin->minimum_payment)->toBe(0.0);
});
