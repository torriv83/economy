<?php

declare(strict_types=1);

use App\Models\Debt;
use App\Services\YnabSyncService;

test('can import a single debt from YNAB data', function () {
    $service = new YnabSyncService;

    $ynabDebt = [
        'name' => 'Test YNAB Debt',
        'balance' => 5000.00,
        'interest_rate' => 15.5,
        'minimum_payment' => 150.00,
        'ynab_id' => 'ynab-test-id-123',
    ];

    $debt = $service->importDebt($ynabDebt);

    expect($debt)->toBeInstanceOf(Debt::class)
        ->and($debt->name)->toBe('Test YNAB Debt')
        ->and($debt->balance)->toBe(5000.00)
        ->and($debt->original_balance)->toBe(5000.00)
        ->and($debt->interest_rate)->toBe(15.5)
        ->and($debt->minimum_payment)->toBe(150.00)
        ->and($debt->ynab_account_id)->toBe('ynab-test-id-123');
});

test('import debt handles null minimum payment', function () {
    $service = new YnabSyncService;

    $ynabDebt = [
        'name' => 'Debt Without Min Payment',
        'balance' => 5000.00,
        'interest_rate' => 10.0,
        'minimum_payment' => null,
        'ynab_id' => 'ynab-null-min',
    ];

    $debt = $service->importDebt($ynabDebt);

    expect($debt->minimum_payment)->toBe(0.0);
});

test('can import multiple debts at once', function () {
    $service = new YnabSyncService;

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
    ];

    $count = $service->importAllDebts($ynabDebts);

    expect($count)->toBe(2)
        ->and(Debt::count())->toBe(2)
        ->and(Debt::where('name', 'First Debt')->exists())->toBeTrue()
        ->and(Debt::where('name', 'Second Debt')->exists())->toBeTrue();
});

test('import all debts returns zero for empty array', function () {
    $service = new YnabSyncService;

    $count = $service->importAllDebts([]);

    expect($count)->toBe(0)
        ->and(Debt::count())->toBe(0);
});

test('can link existing debt to YNAB account', function () {
    $service = new YnabSyncService;

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

    $service->linkDebtToYnab($debt, $ynabDebt, []);

    $debt->refresh();

    expect($debt->ynab_account_id)->toBe('ynab-123')
        ->and($debt->name)->toBe('Local Debt') // unchanged
        ->and($debt->balance)->toBe(5000.0) // unchanged
        ->and($debt->interest_rate)->toBe(10.0) // unchanged
        ->and($debt->minimum_payment)->toBe(200.0); // unchanged
});

test('can link debt and update selected fields', function () {
    $service = new YnabSyncService;

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

    $service->linkDebtToYnab($debt, $ynabDebt, ['name', 'balance']);

    $debt->refresh();

    expect($debt->ynab_account_id)->toBe('ynab-123')
        ->and($debt->name)->toBe('YNAB Debt') // updated
        ->and($debt->balance)->toBe(5500.0) // updated
        ->and($debt->interest_rate)->toBe(10.0) // NOT updated
        ->and($debt->minimum_payment)->toBe(200.0); // NOT updated
});

test('can link debt and update all fields', function () {
    $service = new YnabSyncService;

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

    $service->linkDebtToYnab($debt, $ynabDebt, ['name', 'balance', 'interest_rate', 'minimum_payment']);

    $debt->refresh();

    expect($debt->ynab_account_id)->toBe('ynab-123')
        ->and($debt->name)->toBe('YNAB Debt')
        ->and($debt->balance)->toBe(5500.0)
        ->and($debt->interest_rate)->toBe(10.5)
        ->and($debt->minimum_payment)->toBe(250.0);
});
