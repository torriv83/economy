<?php

declare(strict_types=1);

use App\Livewire\DebtList;
use App\Models\Debt;
use App\Services\YnabService;
use Illuminate\Support\Facades\Http;

test('shows user friendly message when YNAB is down', function () {
    // Mock the YnabService to simulate it being down
    $mockService = Mockery::mock(YnabService::class);
    $mockService->shouldReceive('isAccessible')->once()->andReturn(false);

    Debt::factory()->create(['name' => 'Test Debt']);

    $component = new DebtList;
    $component->boot(app(\App\Services\DebtCalculationService::class), $mockService, app(\App\Services\PaymentService::class), app(\App\Services\PayoffSettingsService::class));

    $component->checkYnab();

    // Check that the error message was flashed to the session
    expect(session()->get('error'))->toBe('YNAB er for tiden nede. Prøv igjen senere.')
        ->and($component->showYnabSync)->toBeFalse();
});

test('shows user friendly message when YNAB times out', function () {
    // Mock the YnabService to simulate timeout
    $mockService = Mockery::mock(YnabService::class);
    $mockService->shouldReceive('isAccessible')->once()->andReturn(false);

    Debt::factory()->create(['name' => 'Test Debt']);

    $component = new DebtList;
    $component->boot(app(\App\Services\DebtCalculationService::class), $mockService, app(\App\Services\PaymentService::class), app(\App\Services\PayoffSettingsService::class));

    $component->checkYnab();

    // Check that the error message was flashed to the session
    expect(session()->get('error'))->toBe('YNAB er for tiden nede. Prøv igjen senere.')
        ->and($component->showYnabSync)->toBeFalse();
});

test('proceeds with sync when YNAB is accessible', function () {
    // Mock the YnabService
    $mockService = Mockery::mock(YnabService::class);
    $mockService->shouldReceive('isAccessible')->once()->andReturn(true);
    $mockService->shouldReceive('fetchDebtAccounts')->once()->andReturn(collect([
        [
            'ynab_id' => 'ynab-123',
            'name' => 'YNAB Debt',
            'type' => 'creditCard',
            'balance' => 5000,
            'interest_rate' => 10.0,
            'minimum_payment' => 250,
            'closed' => false,
        ],
    ]));

    Debt::factory()->create(['name' => 'Local Debt', 'ynab_account_id' => null]);

    $component = new DebtList;
    $component->boot(app(\App\Services\DebtCalculationService::class), $mockService, app(\App\Services\PaymentService::class), app(\App\Services\PayoffSettingsService::class));

    $component->checkYnab();

    // Should have found potential matches or new debts
    expect($component->showYnabSync)->toBeTrue()
        ->and($component->ynabDiscrepancies)->not->toBeEmpty();
});

test('YnabService isAccessible returns false when API is down', function () {
    Http::fake([
        'api.ynab.com/v1/user' => Http::response(null, 500),
    ]);

    $service = new YnabService(
        token: 'test-token',
        budgetId: 'test-budget'
    );

    expect($service->isAccessible())->toBeFalse();
});

test('YnabService isAccessible returns true when API is up', function () {
    Http::fake([
        'api.ynab.com/v1/user' => Http::response(['data' => ['user' => []]], 200),
    ]);

    $service = new YnabService(
        token: 'test-token',
        budgetId: 'test-budget'
    );

    expect($service->isAccessible())->toBeTrue();
});
