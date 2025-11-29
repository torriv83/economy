<?php

declare(strict_types=1);

use App\Livewire\Ynab\ReadyToAssign;
use App\Services\SettingsService;
use App\Services\YnabService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    // Clear YNAB cache before each test to ensure clean state
    Cache::forget('ynab:budget_summary:test-budget');

    // Set up YNAB credentials in settings for all tests
    $settings = app(SettingsService::class);
    $settings->setYnabEnabled(true);
    $settings->setYnabToken('test-token');
    $settings->setYnabBudgetId('test-budget');

    // Re-bind YnabService to use the settings
    app()->singleton(YnabService::class, function ($app) {
        $settings = $app->make(SettingsService::class);

        return new YnabService(
            token: $settings->getYnabToken() ?? '',
            budgetId: $settings->getYnabBudgetId() ?? ''
        );
    });
});

it('shows loading state initially then displays amount', function () {
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
    // Disable YNAB and clear credentials
    $settings = app(SettingsService::class);
    $settings->setYnabEnabled(false);
    $settings->setYnabToken(null);
    $settings->setYnabBudgetId(null);

    // Re-bind YnabService to use the updated settings
    app()->singleton(YnabService::class, function ($app) {
        $settings = $app->make(SettingsService::class);

        return new YnabService(
            token: $settings->getYnabToken() ?? '',
            budgetId: $settings->getYnabBudgetId() ?? ''
        );
    });

    Livewire::test(ReadyToAssign::class)
        ->assertSet('isConfigured', false)
        ->assertSet('isLoading', false)
        ->assertDontSee('Ready to Assign');
});

it('shows error state when YNAB API fails', function () {
    Http::fake([
        'api.ynab.com/v1/budgets/*' => Http::response(null, 500),
    ]);

    Livewire::test(ReadyToAssign::class)
        ->assertSet('hasError', true)
        ->assertSet('isLoading', false);
});

it('can refresh the data', function () {
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
