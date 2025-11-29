<?php

declare(strict_types=1);

use App\Livewire\Ynab\ReadyToAssign;
use App\Models\Setting;
use App\Services\YnabService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear YNAB cache before each test to ensure clean state
    Cache::forget('ynab:budget_summary:test-budget');
});

it('shows loading state initially then displays amount', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);

    app()->singleton(YnabService::class, fn () => new YnabService('test-token', 'test-budget'));

    Http::fake([
        'api.ynab.com/v1/budgets/*' => Http::response([
            'data' => [
                'budget' => [
                    'to_be_budgeted' => 2500000, // 2500 kr
                ],
            ],
        ], 200),
    ]);

    Livewire::test(ReadyToAssign::class)
        ->assertSet('isLoading', false)
        ->assertSet('hasError', false)
        ->assertSet('amount', 2500.0)
        ->assertSee('2 500 kr');
});

it('shows nothing when YNAB is not configured', function () {
    // Don't create any YNAB settings - this simulates unconfigured state
    // RefreshDatabase ensures we start with an empty settings table

    app()->singleton(YnabService::class, fn () => new YnabService('', ''));

    Livewire::test(ReadyToAssign::class)
        ->assertSet('isConfigured', false)
        ->assertSet('isLoading', false)
        ->assertDontSee('Ready to Assign');
});

it('shows error state when YNAB API fails', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);

    app()->singleton(YnabService::class, fn () => new YnabService('test-token', 'test-budget'));

    Http::fake([
        'api.ynab.com/v1/budgets/*' => Http::response(null, 500),
    ]);

    Livewire::test(ReadyToAssign::class)
        ->assertSet('hasError', true)
        ->assertSet('isLoading', false);
});

it('can refresh the data', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);

    app()->singleton(YnabService::class, fn () => new YnabService('test-token', 'test-budget'));

    Http::fake([
        'api.ynab.com/v1/budgets/*' => Http::sequence()
            ->push([
                'data' => [
                    'budget' => [
                        'to_be_budgeted' => 1000000, // 1000 kr initially
                    ],
                ],
            ], 200)
            ->push([
                'data' => [
                    'budget' => [
                        'to_be_budgeted' => 2000000, // 2000 kr after refresh
                    ],
                ],
            ], 200),
    ]);

    Livewire::test(ReadyToAssign::class)
        ->assertSet('amount', 1000.0)
        ->call('refresh')
        ->assertSet('amount', 2000.0);
});

it('handles zero ready to assign amount', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);

    app()->singleton(YnabService::class, fn () => new YnabService('test-token', 'test-budget'));

    Http::fake([
        'api.ynab.com/v1/budgets/*' => Http::response([
            'data' => [
                'budget' => [
                    'to_be_budgeted' => 0,
                ],
            ],
        ], 200),
    ]);

    Livewire::test(ReadyToAssign::class)
        ->assertSet('amount', 0.0)
        ->assertSee('0 kr');
});

it('handles negative ready to assign (overbudgeted)', function () {
    Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
    Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
    Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);

    app()->singleton(YnabService::class, fn () => new YnabService('test-token', 'test-budget'));

    Http::fake([
        'api.ynab.com/v1/budgets/*' => Http::response([
            'data' => [
                'budget' => [
                    'to_be_budgeted' => -500000, // -500 kr overbudgeted
                ],
            ],
        ], 200),
    ]);

    Livewire::test(ReadyToAssign::class)
        ->assertSet('amount', -500.0)
        ->assertSee('-500 kr');
});
