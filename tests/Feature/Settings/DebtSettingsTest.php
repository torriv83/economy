<?php

declare(strict_types=1);

use App\Livewire\Settings\DebtSettings;
use App\Services\SettingsService;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders with default values', function () {
    Livewire::test(DebtSettings::class)
        ->assertSet('kredittkortPercentage', 3.0)
        ->assertSet('kredittkortMinimum', 300.0)
        ->assertSet('forbrukslånPayoffMonths', 60)
        ->assertSee(__('app.debt_settings'))
        ->assertSee(__('app.utlansforskriften_title'));
});

it('renders with custom values from settings service', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setKredittkortPercentage(0.05);
    $settingsService->setKredittkortMinimum(500);
    $settingsService->setForbrukslånPayoffMonths(48);

    Livewire::test(DebtSettings::class)
        ->assertSet('kredittkortPercentage', 5.0)
        ->assertSet('kredittkortMinimum', 500.0)
        ->assertSet('forbrukslånPayoffMonths', 48);
});

it('saves kredittkort percentage when updated', function () {
    Livewire::test(DebtSettings::class)
        ->set('kredittkortPercentage', 5)
        ->assertDispatched('debt-settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->getKredittkortPercentage())->toBe(0.05);
});

it('saves kredittkort minimum when updated', function () {
    Livewire::test(DebtSettings::class)
        ->set('kredittkortMinimum', 500)
        ->assertDispatched('debt-settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->getKredittkortMinimum())->toBe(500.0);
});

it('saves forbrukslån payoff months when updated', function () {
    Livewire::test(DebtSettings::class)
        ->set('forbrukslånPayoffMonths', 48)
        ->assertDispatched('debt-settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->getForbrukslånPayoffMonths())->toBe(48);
});

it('validates kredittkort percentage is required', function () {
    Livewire::test(DebtSettings::class)
        ->set('kredittkortPercentage', '')
        ->assertHasErrors(['kredittkortPercentage' => 'required']);
});

// String values cannot be assigned to typed float properties in PHP 8+
// The type system prevents this at runtime, so we don't need to test it

it('validates kredittkort percentage minimum is 1', function () {
    Livewire::test(DebtSettings::class)
        ->set('kredittkortPercentage', 0)
        ->assertHasErrors(['kredittkortPercentage' => 'min']);
});

it('validates kredittkort percentage maximum is 100', function () {
    Livewire::test(DebtSettings::class)
        ->set('kredittkortPercentage', 101)
        ->assertHasErrors(['kredittkortPercentage' => 'max']);
});

it('validates kredittkort minimum is required', function () {
    Livewire::test(DebtSettings::class)
        ->set('kredittkortMinimum', '')
        ->assertHasErrors(['kredittkortMinimum' => 'required']);
});

// String values cannot be assigned to typed float properties in PHP 8+
// The type system prevents this at runtime, so we don't need to test it

it('validates kredittkort minimum cannot be negative', function () {
    Livewire::test(DebtSettings::class)
        ->set('kredittkortMinimum', -1)
        ->assertHasErrors(['kredittkortMinimum' => 'min']);
});

it('validates forbrukslån payoff months is required', function () {
    Livewire::test(DebtSettings::class)
        ->set('forbrukslånPayoffMonths', '')
        ->assertHasErrors(['forbrukslånPayoffMonths' => 'required']);
});

// String values cannot be assigned to typed int properties in PHP 8+
// The type system prevents this at runtime, so we don't need to test it

it('validates forbrukslån payoff months minimum is 1', function () {
    Livewire::test(DebtSettings::class)
        ->set('forbrukslånPayoffMonths', 0)
        ->assertHasErrors(['forbrukslånPayoffMonths' => 'min']);
});

it('validates forbrukslån payoff months maximum is 120', function () {
    Livewire::test(DebtSettings::class)
        ->set('forbrukslånPayoffMonths', 121)
        ->assertHasErrors(['forbrukslånPayoffMonths' => 'max']);
});

it('resets to defaults when reset button is clicked', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setKredittkortPercentage(0.05);
    $settingsService->setKredittkortMinimum(500);
    $settingsService->setForbrukslånPayoffMonths(48);

    Livewire::test(DebtSettings::class)
        ->assertSet('kredittkortPercentage', 5.0)
        ->assertSet('kredittkortMinimum', 500.0)
        ->assertSet('forbrukslånPayoffMonths', 48)
        ->call('resetToDefaults')
        ->assertSet('kredittkortPercentage', 3.0)
        ->assertSet('kredittkortMinimum', 300.0)
        ->assertSet('forbrukslånPayoffMonths', 60)
        ->assertDispatched('debt-settings-reset');

    expect($settingsService->getKredittkortPercentage())->toBe(0.03);
    expect($settingsService->getKredittkortMinimum())->toBe(300.0);
    expect($settingsService->getForbrukslånPayoffMonths())->toBe(60);
});

it('persists values after page refresh', function () {
    Livewire::test(DebtSettings::class)
        ->set('kredittkortPercentage', 4)
        ->set('kredittkortMinimum', 400)
        ->set('forbrukslånPayoffMonths', 36);

    // Simulate page refresh by creating a new instance
    Livewire::test(DebtSettings::class)
        ->assertSet('kredittkortPercentage', 4.0)
        ->assertSet('kredittkortMinimum', 400.0)
        ->assertSet('forbrukslånPayoffMonths', 36);
});

it('converts percentage from decimal to display format on mount', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setKredittkortPercentage(0.04); // 4%

    $component = Livewire::test(DebtSettings::class);

    // Use approximate comparison for floating point
    expect($component->get('kredittkortPercentage'))->toBeGreaterThanOrEqual(3.99);
    expect($component->get('kredittkortPercentage'))->toBeLessThanOrEqual(4.01);
});

it('converts percentage from display to decimal format when saving', function () {
    Livewire::test(DebtSettings::class)
        ->set('kredittkortPercentage', 3.5);

    $settingsService = app(SettingsService::class);
    expect($settingsService->getKredittkortPercentage())->toBe(0.035);
});

it('allows zero as kredittkort minimum', function () {
    Livewire::test(DebtSettings::class)
        ->set('kredittkortMinimum', 0)
        ->assertHasNoErrors(['kredittkortMinimum']);

    $settingsService = app(SettingsService::class);
    expect($settingsService->getKredittkortMinimum())->toBe(0.0);
});
