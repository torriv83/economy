<?php

use App\Livewire\SelfLoans\Overview;
use App\Models\SelfLoan\SelfLoan;
use App\Services\SettingsService;
use App\Services\YnabService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows buffer status when YNAB is configured', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([
            ['id' => 'acc-1', 'name' => 'Savings', 'balance' => 30000],
        ]));
        $mock->shouldReceive('fetchPayPeriodShortfall')->with(20)->andReturn([
            'shortfall' => 0.0,
            'monthly_essential' => 15000.0,
            'funded' => 15000.0,
        ]);
        $mock->shouldReceive('fetchCategories')->andReturn(collect([]));
    });

    SelfLoan::factory()->create();

    Livewire::test(Overview::class)
        ->assertSee(__('app.security_buffer'));
});

it('hides buffer status when YNAB is not configured', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(false);
        $mock->shouldReceive('getYnabToken')->andReturn(null);
        $mock->shouldReceive('getYnabBudgetId')->andReturn(null);
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    SelfLoan::factory()->create();

    Livewire::test(Overview::class)
        ->assertDontSee(__('app.security_buffer'));
});

it('calculates layer 1 operational buffer correctly', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([]));
        $mock->shouldReceive('fetchPayPeriodShortfall')->with(20)->andReturn([
            'shortfall' => 0.0,
            'monthly_essential' => 15000.0,
            'funded' => 15000.0,
        ]);
        $mock->shouldReceive('fetchCategories')->andReturn(collect([]));
    });

    SelfLoan::factory()->create();

    $component = Livewire::test(Overview::class);
    $bufferStatus = $component->instance()->bufferStatus;

    expect($bufferStatus['layer1']['amount'])->toEqual(15000.0);
    expect($bufferStatus['layer1']['percentage'])->toEqual(100.0);
    expect($bufferStatus['layer1']['is_month_ahead'])->toBeTrue();
});

it('shows month ahead checkmark when layer 1 is 100%', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([]));
        $mock->shouldReceive('fetchPayPeriodShortfall')->with(20)->andReturn([
            'shortfall' => 0.0,
            'monthly_essential' => 15000.0,
            'funded' => 15000.0,
        ]);
        $mock->shouldReceive('fetchCategories')->andReturn(collect([]));
    });

    SelfLoan::factory()->create();

    Livewire::test(Overview::class)
        ->assertSee(__('app.month_ahead'));
});

it('calculates layer 2 emergency buffer in months correctly', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([
            ['id' => 'acc-1', 'name' => 'Savings', 'balance' => 30000],
        ]));
        $mock->shouldReceive('fetchPayPeriodShortfall')->with(20)->andReturn([
            'shortfall' => 15000.0,
            'monthly_essential' => 15000.0,
            'funded' => 0.0,
        ]);
        $mock->shouldReceive('fetchCategories')->andReturn(collect([]));
    });

    SelfLoan::factory()->create();

    $component = Livewire::test(Overview::class);
    $bufferStatus = $component->instance()->bufferStatus;

    expect($bufferStatus['layer2']['amount'])->toEqual(30000.0);
    expect($bufferStatus['layer2']['months'])->toEqual(2.0);
});

it('calculates total buffer correctly from layer 1 plus layer 2', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([
            ['id' => 'acc-1', 'name' => 'Savings', 'balance' => 30000],
        ]));
        $mock->shouldReceive('fetchPayPeriodShortfall')->with(20)->andReturn([
            'shortfall' => 0.0,
            'monthly_essential' => 15000.0,
            'funded' => 15000.0,
        ]);
        $mock->shouldReceive('fetchCategories')->andReturn(collect([]));
    });

    SelfLoan::factory()->create();

    $component = Livewire::test(Overview::class);
    $bufferStatus = $component->instance()->bufferStatus;

    expect($bufferStatus['total_buffer'])->toEqual(45000.0);
    expect($bufferStatus['months_of_security'])->toEqual(3.0);
});

it('shows critical status when total buffer is less than 1 month', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([
            ['id' => 'acc-1', 'name' => 'Savings', 'balance' => 5000],
        ]));
        $mock->shouldReceive('fetchPayPeriodShortfall')->with(20)->andReturn([
            'shortfall' => 10000.0,
            'monthly_essential' => 15000.0,
            'funded' => 5000.0,
        ]);
        $mock->shouldReceive('fetchCategories')->andReturn(collect([]));
    });

    SelfLoan::factory()->create();

    $component = Livewire::test(Overview::class);
    $bufferStatus = $component->instance()->bufferStatus;

    expect($bufferStatus['status'])->toBe('critical');
});

it('shows warning status when total buffer is 1-2 months', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([
            ['id' => 'acc-1', 'name' => 'Savings', 'balance' => 10000],
        ]));
        $mock->shouldReceive('fetchPayPeriodShortfall')->with(20)->andReturn([
            'shortfall' => 5000.0,
            'monthly_essential' => 15000.0,
            'funded' => 10000.0,
        ]);
        $mock->shouldReceive('fetchCategories')->andReturn(collect([]));
    });

    SelfLoan::factory()->create();

    $component = Livewire::test(Overview::class);
    $bufferStatus = $component->instance()->bufferStatus;

    expect($bufferStatus['status'])->toBe('warning');
});

it('shows healthy status when total buffer is 2+ months', function () {
    $this->mock(SettingsService::class, function ($mock) {
        $mock->shouldReceive('isYnabConfigured')->andReturn(true);
        $mock->shouldReceive('getYnabToken')->andReturn('test-token');
        $mock->shouldReceive('getYnabBudgetId')->andReturn('test-budget-id');
        $mock->shouldReceive('getYnabBackgroundSyncInterval')->andReturn(30);
    });

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldReceive('fetchSavingsAccounts')->andReturn(collect([
            ['id' => 'acc-1', 'name' => 'Savings', 'balance' => 30000],
        ]));
        $mock->shouldReceive('fetchPayPeriodShortfall')->with(20)->andReturn([
            'shortfall' => 0.0,
            'monthly_essential' => 15000.0,
            'funded' => 15000.0,
        ]);
        $mock->shouldReceive('fetchCategories')->andReturn(collect([]));
    });

    SelfLoan::factory()->create();

    $component = Livewire::test(Overview::class);
    $bufferStatus = $component->instance()->bufferStatus;

    expect($bufferStatus['status'])->toBe('healthy');
});
