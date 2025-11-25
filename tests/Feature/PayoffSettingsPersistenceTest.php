<?php

use App\Livewire\Payoff\PayoffSettings;
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
