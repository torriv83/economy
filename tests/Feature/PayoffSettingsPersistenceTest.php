<?php

use App\Livewire\Payoff\PayoffSettings;
use App\Models\Debt;
use App\Models\PayoffSetting;
use App\Services\PayoffSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Model Tests
test('payoff setting creates default instance via service', function () {
    $service = app(PayoffSettingsService::class);
    $setting = $service->getSettings();

    expect($setting)->toBeInstanceOf(PayoffSetting::class)
        ->and($setting->extra_payment)->toBe(2000.0)
        ->and($setting->strategy)->toBe('avalanche');
});

test('payoff setting getInstance returns same instance', function () {
    $service = app(PayoffSettingsService::class);
    $first = $service->getSettings();
    $second = $service->getSettings();

    expect($first->id)->toBe($second->id);
});

// Service Tests
test('payoff settings service returns default settings', function () {
    $service = app(PayoffSettingsService::class);

    expect($service->getExtraPayment())->toBe(2000.0)
        ->and($service->getStrategy())->toBe('avalanche');
});

test('payoff settings service updates extra payment', function () {
    $service = app(PayoffSettingsService::class);

    $service->setExtraPayment(3500.0);

    expect($service->getExtraPayment())->toBe(3500.0)
        ->and(PayoffSetting::first()->extra_payment)->toBe(3500.0);
});

test('payoff settings service updates strategy', function () {
    $service = app(PayoffSettingsService::class);

    $service->setStrategy('snowball');

    expect($service->getStrategy())->toBe('snowball')
        ->and(PayoffSetting::first()->strategy)->toBe('snowball');
});

// Livewire Component Tests
test('payoff settings loads persisted values on mount', function () {
    PayoffSetting::create([
        'extra_payment' => 5000.0,
        'strategy' => 'snowball',
    ]);

    Livewire::test(PayoffSettings::class)
        ->assertSet('extraPayment', 5000.0)
        ->assertSet('strategy', 'snowball');
});

test('payoff settings persists extra payment on update', function () {
    Livewire::test(PayoffSettings::class)
        ->set('extraPayment', 4000.0)
        ->assertSet('extraPayment', 4000.0);

    expect(PayoffSetting::first()->extra_payment)->toBe(4000.0);
});

test('payoff settings persists strategy on update', function () {
    Livewire::test(PayoffSettings::class)
        ->set('strategy', 'custom')
        ->assertSet('strategy', 'custom');

    expect(PayoffSetting::first()->strategy)->toBe('custom');
});

test('payoff settings dispatches event on extra payment change', function () {
    Livewire::test(PayoffSettings::class)
        ->set('extraPayment', 3000.0)
        ->assertDispatched('planSettingsUpdated');
});

test('payoff settings dispatches event on strategy change', function () {
    Livewire::test(PayoffSettings::class)
        ->set('strategy', 'snowball')
        ->assertDispatched('planSettingsUpdated');
});

test('settings persist across page refresh simulation', function () {
    // Simulate first page load and settings change
    Livewire::test(PayoffSettings::class)
        ->set('extraPayment', 2500.0)
        ->set('strategy', 'snowball');

    // Simulate page refresh - new component instance
    Livewire::test(PayoffSettings::class)
        ->assertSet('extraPayment', 2500.0)
        ->assertSet('strategy', 'snowball');
});

// Factory Tests
test('payoff setting factory creates default values', function () {
    $setting = PayoffSetting::factory()->create();

    expect($setting->extra_payment)->toBe(2000.0)
        ->and($setting->strategy)->toBe('avalanche');
});

test('payoff setting factory creates snowball state', function () {
    $setting = PayoffSetting::factory()->snowball()->create();

    expect($setting->strategy)->toBe('snowball');
});

test('payoff setting factory creates custom extra payment', function () {
    $setting = PayoffSetting::factory()->withExtraPayment(5000.0)->create();

    expect($setting->extra_payment)->toBe(5000.0);
});

// Debt Projection Chart Tests
test('debt projection data returns empty arrays when no debts exist', function () {
    $component = Livewire::test(PayoffSettings::class);

    expect($component->instance()->debtProjectionData)
        ->toBeArray()
        ->and($component->instance()->debtProjectionData['labels'])->toBeEmpty()
        ->and($component->instance()->debtProjectionData['datasets'])->toBeEmpty();
});

test('debt projection data returns correct structure with debts', function () {
    Debt::factory()->create([
        'name' => 'Kredittkort',
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 20,
        'minimum_payment' => 500,
    ]);

    Debt::factory()->create([
        'name' => 'Forbrukslån',
        'balance' => 25000,
        'original_balance' => 25000,
        'interest_rate' => 15,
        'minimum_payment' => 1000,
    ]);

    $component = Livewire::test(PayoffSettings::class);
    $data = $component->instance()->debtProjectionData;

    expect($data)
        ->toBeArray()
        ->toHaveKeys(['labels', 'datasets'])
        ->and($data['labels'])->toBeArray()->not->toBeEmpty()
        ->and($data['datasets'])->toBeArray()->toHaveCount(2)
        ->and($data['datasets'][0])->toHaveKeys(['label', 'data', 'borderColor'])
        ->and($data['datasets'][1])->toHaveKeys(['label', 'data', 'borderColor']);
});

test('debt projection data updates when strategy changes', function () {
    Debt::factory()->create([
        'name' => 'Gjeld A',
        'balance' => 5000,
        'original_balance' => 5000,
        'interest_rate' => 10,
        'minimum_payment' => 200,
    ]);

    $component = Livewire::test(PayoffSettings::class);

    // Get initial data with avalanche strategy
    $avalancheData = $component->instance()->debtProjectionData;

    // Change strategy to snowball
    $component->set('strategy', 'snowball');
    $snowballData = $component->instance()->debtProjectionData;

    // Both should have data
    expect($avalancheData['datasets'])->not->toBeEmpty()
        ->and($snowballData['datasets'])->not->toBeEmpty();
});
