<?php

declare(strict_types=1);

use App\Livewire\Settings\BufferSettings;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Clear settings cache to ensure test isolation
    Cache::flush();
});

it('renders with default values', function () {
    Livewire::test(BufferSettings::class)
        ->assertSet('bufferTargetAmount', 20000.0)
        ->assertSee(__('app.buffer_settings'))
        ->assertSee(__('app.buffer_target_amount'));
});

it('renders with custom values from settings service', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setBufferTargetAmount(50000);
    $settingsService->setBufferCategories([
        ['name' => 'Test Category', 'target' => 5000.0],
    ]);

    Livewire::test(BufferSettings::class)
        ->assertSet('bufferTargetAmount', 50000.0)
        ->assertSee('Test Category');
});

it('saves buffer target amount when updated', function () {
    Livewire::test(BufferSettings::class)
        ->set('bufferTargetAmount', 30000)
        ->assertDispatched('buffer-settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->getBufferTargetAmount())->toBe(30000.0);
});

it('validates buffer target amount is required', function () {
    Livewire::test(BufferSettings::class)
        ->set('bufferTargetAmount', '')
        ->assertHasErrors(['bufferTargetAmount' => 'required']);
});

it('validates buffer target amount cannot be negative', function () {
    Livewire::test(BufferSettings::class)
        ->set('bufferTargetAmount', -1000)
        ->assertHasErrors(['bufferTargetAmount' => 'min']);
});

it('can add a new buffer category', function () {
    Livewire::test(BufferSettings::class)
        ->set('newCategoryName', 'Dental')
        ->set('newCategoryTarget', 2000)
        ->call('addCategory')
        ->assertDispatched('buffer-settings-saved')
        ->assertSet('newCategoryName', '')
        ->assertSet('newCategoryTarget', 0);

    $settingsService = app(SettingsService::class);
    $categories = $settingsService->getBufferCategories();

    // Find the Dental category
    $dentalCategory = collect($categories)->first(fn ($cat) => $cat['name'] === 'Dental');
    expect($dentalCategory)->not->toBeNull();
    expect((float) $dentalCategory['target'])->toBe(2000.0);
});

it('validates category name is required when adding', function () {
    Livewire::test(BufferSettings::class)
        ->set('newCategoryName', '')
        ->set('newCategoryTarget', 1000)
        ->call('addCategory')
        ->assertHasErrors(['newCategoryName' => 'required']);
});

it('can remove a buffer category', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setBufferCategories([
        ['name' => 'Tannlege', 'target' => 1500.0],
        ['name' => 'Service', 'target' => 15000.0],
    ]);

    Livewire::test(BufferSettings::class)
        ->call('removeCategory', 0)
        ->assertDispatched('buffer-settings-saved');

    $categories = $settingsService->getBufferCategories();
    expect($categories)->toHaveCount(1);
    expect($categories[0]['name'])->toBe('Service');
});

it('can update a category target', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setBufferCategories([
        ['name' => 'Tannlege', 'target' => 1500.0],
    ]);

    Livewire::test(BufferSettings::class)
        ->call('updateCategoryTarget', 0, 3000)
        ->assertDispatched('buffer-settings-saved');

    $categories = $settingsService->getBufferCategories();
    expect((float) $categories[0]['target'])->toBe(3000.0);
});

it('resets to defaults when reset button is clicked', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setBufferTargetAmount(50000);
    $settingsService->setBufferCategories([
        ['name' => 'Custom Category', 'target' => 5000.0],
    ]);

    Livewire::test(BufferSettings::class)
        ->assertSet('bufferTargetAmount', 50000.0)
        ->call('resetToDefaults')
        ->assertSet('bufferTargetAmount', 20000.0)
        ->assertDispatched('buffer-settings-reset');

    $categories = $settingsService->getBufferCategories();
    expect($categories)->toHaveCount(2);
    expect($categories[0]['name'])->toBe('Tannlege');
    expect($categories[1]['name'])->toBe('Service/Vedlikehold');
});

it('persists values after page refresh', function () {
    Livewire::test(BufferSettings::class)
        ->set('bufferTargetAmount', 40000);

    // Simulate page refresh by creating a new instance
    Livewire::test(BufferSettings::class)
        ->assertSet('bufferTargetAmount', 40000.0);
});

it('displays existing categories on mount', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setBufferCategories([
        ['name' => 'Car Service', 'target' => 10000.0],
        ['name' => 'Dental', 'target' => 2000.0],
    ]);

    Livewire::test(BufferSettings::class)
        ->assertSee('Car Service')
        ->assertSee('Dental');
});

it('allows zero as buffer target amount', function () {
    Livewire::test(BufferSettings::class)
        ->set('bufferTargetAmount', 0)
        ->assertHasNoErrors(['bufferTargetAmount']);

    $settingsService = app(SettingsService::class);
    expect($settingsService->getBufferTargetAmount())->toBe(0.0);
});

it('allows zero as category target when adding', function () {
    Livewire::test(BufferSettings::class)
        ->set('newCategoryName', 'Zero Target')
        ->set('newCategoryTarget', 0)
        ->call('addCategory')
        ->assertHasNoErrors()
        ->assertDispatched('buffer-settings-saved');

    $settingsService = app(SettingsService::class);
    $categories = $settingsService->getBufferCategories();

    // Find the Zero Target category
    $zeroCategory = collect($categories)->first(fn ($cat) => $cat['name'] === 'Zero Target');
    expect($zeroCategory)->not->toBeNull();
    expect((float) $zeroCategory['target'])->toBe(0.0);
});
