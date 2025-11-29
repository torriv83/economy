<?php

declare(strict_types=1);

use App\Livewire\Ynab\AccelerationOpportunities;
use App\Models\Debt;
use App\Models\Setting;
use App\Services\AccelerationService;
use App\Services\YnabService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows not configured message when YNAB is not set up', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean']);
    config(['services.ynab.token' => null]);
    config(['services.ynab.budget_id' => null]);

    // Re-bind the service with empty strings to avoid constructor error
    app()->singleton(YnabService::class, function () {
        return new YnabService(
            token: config('services.ynab.token') ?? '',
            budgetId: config('services.ynab.budget_id') ?? ''
        );
    });

    $debt = Debt::factory()->create();

    Livewire::test(AccelerationOpportunities::class, ['debt' => $debt])
        ->assertSet('isConfigured', false)
        ->assertSet('isLoading', false)
        ->assertSee(__('app.ynab_not_configured'));
});

it('shows loading state initially with deferred loading via wire:init', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string']);

    config(['services.ynab.token' => 'test-token']);
    config(['services.ynab.budget_id' => 'test-budget']);

    $debt = Debt::factory()->create();

    // Component should start with isLoading=true (deferred loading via wire:init)
    // The mount() method no longer calls loadOpportunities - that's done via wire:init
    Livewire::test(AccelerationOpportunities::class, ['debt' => $debt])
        ->assertSet('isLoading', true)
        ->assertSet('ynabEnabled', true)
        ->assertSet('isConfigured', true);
});

it('loads data when loadOpportunities is called via wire:init', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string']);

    config(['services.ynab.token' => 'test-token']);
    config(['services.ynab.budget_id' => 'test-budget']);

    $debt = Debt::factory()->create();

    $opportunities = collect([
        [
            'source' => 'ready_to_assign',
            'name' => 'Ready to Assign',
            'group_name' => null,
            'amount' => 1500.0,
            'type' => 'one_time',
            'tier' => 1,
            'impact' => ['months_saved' => 1, 'weeks_saved' => 0, 'interest_saved' => 500.0, 'new_payoff_date' => now()->addMonths(11)->format('Y-m-d')],
            'warning' => null,
        ],
    ]);

    $this->mock(AccelerationService::class, function ($mock) use ($opportunities) {
        $mock->shouldReceive('getOpportunities')
            ->once()
            ->andReturn($opportunities);
    });

    // Test that calling loadOpportunities (which wire:init will do) loads the data
    Livewire::test(AccelerationOpportunities::class, ['debt' => $debt])
        ->assertSet('isLoading', true)
        ->call('loadOpportunities')
        ->assertSet('isLoading', false)
        ->assertSee('Ready to Assign')
        ->assertSee('1 500 kr');
});

it('shows opportunities when available', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string']);

    config(['services.ynab.token' => 'test-token']);
    config(['services.ynab.budget_id' => 'test-budget']);

    $debt = Debt::factory()->create(['name' => 'Test Debt']);

    $opportunities = collect([
        [
            'source' => 'ready_to_assign',
            'name' => 'Ready to Assign',
            'group_name' => null,
            'amount' => 2500.0,
            'type' => 'one_time',
            'tier' => 1,
            'impact' => [
                'months_saved' => 2,
                'weeks_saved' => 0,
                'interest_saved' => 1500.0,
                'new_payoff_date' => now()->addMonths(10)->format('Y-m-d'),
            ],
            'warning' => null,
        ],
        [
            'source' => 'category',
            'name' => 'Entertainment',
            'group_name' => 'Fun Money',
            'amount' => 1000.0,
            'type' => 'one_time',
            'tier' => 2,
            'impact' => [
                'months_saved' => 1,
                'weeks_saved' => 0,
                'interest_saved' => 500.0,
                'new_payoff_date' => now()->addMonths(11)->format('Y-m-d'),
            ],
            'warning' => null,
        ],
    ]);

    $this->mock(AccelerationService::class, function ($mock) use ($opportunities) {
        $mock->shouldReceive('getOpportunities')
            ->andReturn($opportunities);
    });

    Livewire::test(AccelerationOpportunities::class, ['debt' => $debt])
        ->call('loadOpportunities')
        ->assertSet('isLoading', false)
        ->assertSee('Ready to Assign')
        ->assertSee('2 500 kr')
        ->assertSee('Entertainment')
        ->assertSee('1 000 kr');
});

it('shows no opportunities message when list is empty', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string']);

    config(['services.ynab.token' => 'test-token']);
    config(['services.ynab.budget_id' => 'test-budget']);

    $debt = Debt::factory()->create();

    $this->mock(AccelerationService::class, function ($mock) {
        $mock->shouldReceive('getOpportunities')
            ->andReturn(collect());
    });

    Livewire::test(AccelerationOpportunities::class, ['debt' => $debt])
        ->call('loadOpportunities')
        ->assertSet('isLoading', false)
        ->assertSet('opportunities', fn ($o) => $o->isEmpty())
        ->assertSee(__('app.no_opportunities'));
});

it('shows error state when API fails', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string']);

    config(['services.ynab.token' => 'test-token']);
    config(['services.ynab.budget_id' => 'test-budget']);

    $debt = Debt::factory()->create();

    $this->mock(AccelerationService::class, function ($mock) {
        $mock->shouldReceive('getOpportunities')
            ->andThrow(new \Exception('API Error'));
    });

    Livewire::test(AccelerationOpportunities::class, ['debt' => $debt])
        ->call('loadOpportunities')
        ->assertSet('hasError', true)
        ->assertSet('isLoading', false);
});

it('can refresh opportunities', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string']);

    config(['services.ynab.token' => 'test-token']);
    config(['services.ynab.budget_id' => 'test-budget']);

    $debt = Debt::factory()->create();

    $initialOpportunities = collect([
        [
            'source' => 'ready_to_assign',
            'name' => 'Ready to Assign',
            'group_name' => null,
            'amount' => 1000.0,
            'type' => 'one_time',
            'tier' => 1,
            'impact' => ['months_saved' => 1, 'weeks_saved' => 0, 'interest_saved' => 500.0, 'new_payoff_date' => now()->addMonths(11)->format('Y-m-d')],
            'warning' => null,
        ],
    ]);

    $refreshedOpportunities = collect([
        [
            'source' => 'ready_to_assign',
            'name' => 'Ready to Assign',
            'group_name' => null,
            'amount' => 3000.0,
            'type' => 'one_time',
            'tier' => 1,
            'impact' => ['months_saved' => 3, 'weeks_saved' => 0, 'interest_saved' => 2000.0, 'new_payoff_date' => now()->addMonths(9)->format('Y-m-d')],
            'warning' => null,
        ],
    ]);

    $mock = $this->mock(AccelerationService::class);
    $mock->shouldReceive('getOpportunities')
        ->once()
        ->andReturn($initialOpportunities);
    $mock->shouldReceive('getOpportunities')
        ->once()
        ->andReturn($refreshedOpportunities);

    Livewire::test(AccelerationOpportunities::class, ['debt' => $debt])
        ->call('loadOpportunities')
        ->assertSee('1 000 kr')
        ->call('refresh')
        ->assertSee('3 000 kr');
});

it('displays savings warning for tier 3 opportunities', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string']);

    config(['services.ynab.token' => 'test-token']);
    config(['services.ynab.budget_id' => 'test-budget']);

    $debt = Debt::factory()->create();

    $opportunities = collect([
        [
            'source' => 'savings',
            'name' => 'Emergency Fund',
            'group_name' => 'Savings',
            'amount' => 5000.0,
            'type' => 'one_time',
            'tier' => 3,
            'impact' => ['months_saved' => 4, 'weeks_saved' => 0, 'interest_saved' => 3000.0, 'new_payoff_date' => now()->addMonths(8)->format('Y-m-d')],
            'warning' => __('app.savings_redirect_warning'),
        ],
    ]);

    $this->mock(AccelerationService::class, function ($mock) use ($opportunities) {
        $mock->shouldReceive('getOpportunities')
            ->andReturn($opportunities);
    });

    Livewire::test(AccelerationOpportunities::class, ['debt' => $debt])
        ->call('loadOpportunities')
        ->assertSee('Emergency Fund')
        ->assertSee(__('app.savings_redirect_warning'));
});

it('returns correct tier labels', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string']);

    config(['services.ynab.token' => 'test-token']);
    config(['services.ynab.budget_id' => 'test-budget']);

    $debt = Debt::factory()->create();

    $this->mock(AccelerationService::class, function ($mock) {
        $mock->shouldReceive('getOpportunities')
            ->andReturn(collect());
    });

    $component = Livewire::test(AccelerationOpportunities::class, ['debt' => $debt])
        ->call('loadOpportunities');

    expect($component->instance()->getTierLabel(1))->toBe(__('app.acceleration_tier_1'));
    expect($component->instance()->getTierLabel(2))->toBe(__('app.acceleration_tier_2'));
    expect($component->instance()->getTierLabel(3))->toBe(__('app.acceleration_tier_3'));
});
