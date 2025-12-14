<?php

use App\Livewire\SelfLoans\CreateSelfLoan;
use App\Models\SelfLoan\SelfLoan;
use App\Services\SettingsService;
use App\Services\YnabService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows YNAB connection options when YNAB is configured', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([]));
        $mock->shouldReceive('fetchCategories')->andReturn(collect([]));
    });

    Livewire::test(CreateSelfLoan::class)
        ->call('loadData')
        ->assertSee(__('app.link_to_ynab_optional'));
});

it('hides YNAB connection options when YNAB is not configured', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(false);
        $mock->shouldReceive('getYnabToken')->andReturn(null);
        $mock->shouldReceive('getYnabBudgetId')->andReturn(null);
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    Livewire::test(CreateSelfLoan::class)
        ->assertDontSee(__('app.link_to_ynab_optional'));
});

it('creates self loan without YNAB connection', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(false);
        $mock->shouldReceive('getYnabToken')->andReturn(null);
        $mock->shouldReceive('getYnabBudgetId')->andReturn(null);
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    Livewire::test(CreateSelfLoan::class)
        ->set('name', 'Test Loan')
        ->set('amount', '5000')
        ->call('createLoan');

    $loan = SelfLoan::first();
    expect($loan)->not->toBeNull();
    expect($loan->name)->toBe('Test Loan');
    expect($loan->ynab_account_id)->toBeNull();
    expect($loan->ynab_category_id)->toBeNull();
});

it('creates self loan with YNAB account connection', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([
            ['id' => 'acc-123', 'name' => 'Savings', 'balance' => 10000],
        ]));
        $mock->shouldReceive('fetchCategories')->andReturn(collect([]));
    });

    Livewire::test(CreateSelfLoan::class)
        ->set('name', 'Test Loan')
        ->set('amount', '5000')
        ->set('ynabConnectionType', 'account')
        ->set('ynabAccountId', 'acc-123')
        ->call('createLoan');

    $loan = SelfLoan::first();
    expect($loan->ynab_account_id)->toBe('acc-123');
    expect($loan->ynab_category_id)->toBeNull();
});

it('creates self loan with YNAB category connection', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([]));
        $mock->shouldReceive('fetchCategories')->andReturn(collect([
            ['id' => 'cat-456', 'name' => 'Emergency Fund', 'group_name' => 'Savings'],
        ]));
    });

    Livewire::test(CreateSelfLoan::class)
        ->set('name', 'Test Loan')
        ->set('amount', '5000')
        ->set('ynabConnectionType', 'category')
        ->set('ynabCategoryId', 'cat-456')
        ->call('createLoan');

    $loan = SelfLoan::first();
    expect($loan->ynab_account_id)->toBeNull();
    expect($loan->ynab_category_id)->toBe('cat-456');
});

it('does not save YNAB account ID when connection type is none', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([]));
        $mock->shouldReceive('fetchCategories')->andReturn(collect([]));
    });

    Livewire::test(CreateSelfLoan::class)
        ->set('name', 'Test Loan')
        ->set('amount', '5000')
        ->set('ynabConnectionType', 'none')
        ->set('ynabAccountId', 'acc-123')
        ->call('createLoan');

    $loan = SelfLoan::first();
    expect($loan->ynab_account_id)->toBeNull();
});
