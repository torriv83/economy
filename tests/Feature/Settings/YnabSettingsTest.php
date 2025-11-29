<?php

declare(strict_types=1);

use App\Livewire\Settings\YnabSettings;
use App\Services\SettingsService;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders correctly when YNAB is disabled', function () {
    Livewire::test(YnabSettings::class)
        ->assertSet('ynabEnabled', false)
        ->assertSet('isConfigured', false)
        ->assertSee(__('app.ynab_settings'))
        ->assertSee(__('app.ynab_enable_integration'))
        ->assertSee(__('app.ynab_disabled_message'));
});

it('renders correctly when YNAB is enabled but not configured', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);

    Livewire::test(YnabSettings::class)
        ->assertSet('ynabEnabled', true)
        ->assertSet('isConfigured', false)
        ->assertSee(__('app.ynab_api_token'))
        ->assertSee(__('app.ynab_budget_id'))
        ->assertSee(__('app.ynab_not_configured_status'));
});

it('renders correctly when YNAB is fully configured', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);
    $settingsService->setYnabToken('test-token-at-least-20-chars');
    $settingsService->setYnabBudgetId('550e8400-e29b-41d4-a716-446655440000');

    Livewire::test(YnabSettings::class)
        ->assertSet('ynabEnabled', true)
        ->assertSet('isConfigured', true)
        ->assertSet('budgetId', '550e8400-e29b-41d4-a716-446655440000')
        ->assertSee(__('app.ynab_configured'))
        ->assertSee(__('app.ynab_clear_credentials'));
});

it('saves YNAB enabled state when toggled', function () {
    Livewire::test(YnabSettings::class)
        ->assertSet('ynabEnabled', false)
        ->set('ynabEnabled', true)
        ->assertDispatched('ynab-settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->isYnabEnabled())->toBeTrue();
});

it('saves credentials and encrypts token', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);

    $token = 'test-ynab-api-token-at-least-20-characters';
    $budgetId = '550e8400-e29b-41d4-a716-446655440000';

    Livewire::test(YnabSettings::class)
        ->set('token', $token)
        ->set('budgetId', $budgetId)
        ->call('saveCredentials')
        ->assertSet('isConfigured', true)
        ->assertSet('token', '') // Token should be cleared after save
        ->assertDispatched('ynab-credentials-saved');

    // Verify token is stored (and decrypted correctly by service)
    expect($settingsService->getYnabToken())->toBe($token);
    expect($settingsService->getYnabBudgetId())->toBe($budgetId);
});

it('validates token is required', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);

    Livewire::test(YnabSettings::class)
        ->set('budgetId', '550e8400-e29b-41d4-a716-446655440000')
        ->call('saveCredentials')
        ->assertHasErrors(['token' => 'required']);
});

it('validates token minimum length', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);

    Livewire::test(YnabSettings::class)
        ->set('token', 'short')
        ->set('budgetId', '550e8400-e29b-41d4-a716-446655440000')
        ->call('saveCredentials')
        ->assertHasErrors(['token' => 'min']);
});

it('validates budget ID is required', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);

    Livewire::test(YnabSettings::class)
        ->set('token', 'test-ynab-api-token-at-least-20-characters')
        ->call('saveCredentials')
        ->assertHasErrors(['budgetId' => 'required']);
});

it('validates budget ID is a valid UUID', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);

    Livewire::test(YnabSettings::class)
        ->set('token', 'test-ynab-api-token-at-least-20-characters')
        ->set('budgetId', 'not-a-valid-uuid')
        ->call('saveCredentials')
        ->assertHasErrors(['budgetId' => 'uuid']);
});

it('sets connection status when test connection is called', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);
    $settingsService->setYnabToken('test-token-at-least-20-chars');
    $settingsService->setYnabBudgetId('550e8400-e29b-41d4-a716-446655440000');

    // The component creates its own YnabService instance that makes real HTTP calls
    // We can only verify the component state changes appropriately
    $component = Livewire::test(YnabSettings::class);

    expect($component->get('isConfigured'))->toBeTrue();
    expect($component->get('connectionStatus'))->toBeNull();

    // Call testConnection - this will make a real HTTP call which will fail
    // (since we don't have valid credentials), but the component should handle it
    $component->call('testConnection');

    // Connection status should be set (false because the token is invalid)
    expect($component->get('connectionStatus'))->toBeFalse();
    expect($component->get('isTesting'))->toBeFalse();
});

it('clears credentials and resets state', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);
    $settingsService->setYnabToken('test-token-at-least-20-chars');
    $settingsService->setYnabBudgetId('550e8400-e29b-41d4-a716-446655440000');

    Livewire::test(YnabSettings::class)
        ->assertSet('isConfigured', true)
        ->call('clearCredentials')
        ->assertSet('isConfigured', false)
        ->assertSet('token', '')
        ->assertSet('budgetId', '')
        ->assertSet('connectionStatus', null)
        ->assertDispatched('ynab-credentials-cleared');

    expect($settingsService->getYnabToken())->toBeNull();
    expect($settingsService->getYnabBudgetId())->toBeNull();
});

it('never exposes stored token in rendered HTML', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);
    $settingsService->setYnabToken('super-secret-token-at-least-20-chars');
    $settingsService->setYnabBudgetId('550e8400-e29b-41d4-a716-446655440000');

    $component = Livewire::test(YnabSettings::class);

    // Token should not be loaded into the property
    expect($component->get('token'))->toBe('');

    // Token should not appear in the rendered HTML
    $html = $component->html();
    expect($html)->not->toContain('super-secret-token');
});

it('does not test connection when not configured', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);

    Livewire::test(YnabSettings::class)
        ->assertSet('isConfigured', false)
        ->call('testConnection')
        ->assertSet('connectionStatus', null);
});

it('loads budget ID but not token on mount', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);
    $settingsService->setYnabToken('test-token-at-least-20-chars');
    $settingsService->setYnabBudgetId('550e8400-e29b-41d4-a716-446655440000');

    Livewire::test(YnabSettings::class)
        ->assertSet('token', '') // Token should never be loaded
        ->assertSet('budgetId', '550e8400-e29b-41d4-a716-446655440000'); // Budget ID should be loaded
});

it('shows disabled message when YNAB is turned off', function () {
    Livewire::test(YnabSettings::class)
        ->assertSet('ynabEnabled', false)
        ->assertSee(__('app.ynab_disabled_message'))
        ->assertDontSee(__('app.ynab_api_token'));
});

it('hides configuration form when disabled', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);
    $settingsService->setYnabToken('test-token-at-least-20-chars');
    $settingsService->setYnabBudgetId('550e8400-e29b-41d4-a716-446655440000');

    Livewire::test(YnabSettings::class)
        ->assertSee(__('app.ynab_api_token'))
        ->set('ynabEnabled', false)
        ->assertSee(__('app.ynab_disabled_message'))
        ->assertDontSee(__('app.ynab_api_token'));
});

it('resets connection status when saving new credentials', function () {
    $settingsService = app(SettingsService::class);
    $settingsService->setYnabEnabled(true);
    $settingsService->setYnabToken('old-token-at-least-20-chars');
    $settingsService->setYnabBudgetId('550e8400-e29b-41d4-a716-446655440000');

    $component = Livewire::test(YnabSettings::class);

    // Simulate that connection was previously tested
    $component->set('connectionStatus', true);

    // Save new credentials
    $component
        ->set('token', 'new-token-at-least-20-characters')
        ->set('budgetId', '550e8400-e29b-41d4-a716-446655440001')
        ->call('saveCredentials')
        ->assertSet('connectionStatus', null); // Should be reset
});
