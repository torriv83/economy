<?php

declare(strict_types=1);

use App\Livewire\Debts\Recommendations;
use App\Models\Debt;
use App\Models\Setting;
use App\Services\YnabService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

afterEach(function () {
    Mockery::close();
    Cache::clearResolvedInstances();
});

describe('YNAB configuration', function () {
    it('shows message when YNAB is not configured', function () {
        // No YNAB settings - simulates unconfigured state

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertSee(__('app.ynab_required_for_recommendations'))
            ->assertSee(__('app.ynab_required_for_recommendations_description'))
            ->assertSee(__('app.configure_ynab'));
    });

    it('fetches data when YNAB is configured', function () {
        Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);

        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 50000],
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 30000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertDontSee(__('app.ynab_required_for_recommendations'))
            ->assertSee(__('app.security_buffer'));
    });
});

describe('Buffer status calculations', function () {
    beforeEach(function () {
        Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);
    });

    it('calculates layer 1 (operational buffer) correctly', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 30000],
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 20000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Layer 1 amount should be the funded amount
        expect($bufferStatus['layer1']['amount'])->toEqual(20000);

        // Layer 1 percentage should be (20000 / 25000) * 100 = 80%
        expect($bufferStatus['layer1']['percentage'])->toEqual(80.0);

        // is_month_ahead: funded (20000) + savings (30000) = 50000 >= 25000 = true
        expect($bufferStatus['layer1']['is_month_ahead'])->toBeTrue();
    });

    it('calculates layer 2 (emergency buffer) correctly', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings 1', 'balance' => 30000],
                ['name' => 'Savings 2', 'balance' => 20000],
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Layer 2 amount should be sum of savings: 30000 + 20000 = 50000
        expect($bufferStatus['layer2']['amount'])->toEqual(50000);

        // Layer 2 months: 50000 / 25000 = 2.0 months
        expect($bufferStatus['layer2']['months'])->toEqual(2.0);

        // Target months is the default of 2
        expect($bufferStatus['layer2']['target_months'])->toBe(2);
    });

    it('returns critical status when buffer is less than 1 month', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 10000],
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 5000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Total buffer: 5000 + 10000 = 15000
        // Months of security: 15000 / 25000 = 0.6 < 1
        expect($bufferStatus['status'])->toBe('critical');
        expect($bufferStatus['months_of_security'])->toBeLessThan(1.0);
    });

    it('returns warning status when buffer is between 1 and 2 months', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 25000],
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 10000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Total buffer: 10000 + 25000 = 35000
        // Months of security: 35000 / 25000 = 1.4 (between 1 and 2)
        expect($bufferStatus['status'])->toBe('warning');
        expect($bufferStatus['months_of_security'])->toBeGreaterThanOrEqual(1.0);
        expect($bufferStatus['months_of_security'])->toBeLessThan(2.0);
    });

    it('returns healthy status when buffer is 2 months or more', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 50000],
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Total buffer: 25000 + 50000 = 75000
        // Months of security: 75000 / 25000 = 3.0 >= 2
        expect($bufferStatus['status'])->toBe('healthy');
        expect($bufferStatus['months_of_security'])->toBeGreaterThanOrEqual(2.0);
    });

    it('handles zero monthly essential gracefully', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 50000],
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 0,
                'monthly_essential' => 0,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Should not crash, percentage should be 0
        expect($bufferStatus['layer1']['percentage'])->toEqual(0);
        expect($bufferStatus['layer2']['months'])->toEqual(0);
    });
});

describe('Livewire actions', function () {
    beforeEach(function () {
        Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);
    });

    it('toggleScenarioComparison toggles the boolean property', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([['name' => 'Savings', 'balance' => 50000]]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertSet('showScenarioComparison', false)
            ->call('toggleScenarioComparison')
            ->assertSet('showScenarioComparison', true)
            ->call('toggleScenarioComparison')
            ->assertSet('showScenarioComparison', false);
    });

    it('shows scenario comparison when toggled on', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([['name' => 'Savings', 'balance' => 50000]]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        // Create a debt so recommendations appear and scenario comparison can be shown
        Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'interest_rate' => 20,
            'minimum_payment' => 500,
        ]);

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertDontSee(__('app.buffer.scenario_comparison_title', ['amount' => '5 000 kr']))
            ->call('toggleScenarioComparison')
            ->assertSee(__('app.buffer.scenario_buffer'));
    });

    it('updates scenario amount via wire:model', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([['name' => 'Savings', 'balance' => 50000]]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertSet('scenarioAmount', 5000.0)
            ->set('scenarioAmount', 10000)
            ->assertSet('scenarioAmount', 10000);
    });
});

describe('Buffer status display', function () {
    beforeEach(function () {
        Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);
    });

    it('displays healthy status badge correctly', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([['name' => 'Savings', 'balance' => 75000]]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertSee(__('app.buffer_status_healthy'));
    });

    it('displays warning status badge correctly', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([['name' => 'Savings', 'balance' => 25000]]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 10000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertSee(__('app.buffer_status_warning'));
    });

    it('displays critical status badge correctly', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([['name' => 'Savings', 'balance' => 10000]]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 5000,
                'monthly_essential' => 25000,
            ]);

        app()->instance(YnabService::class, $mockYnabService);

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertSee(__('app.buffer_status_critical'));
    });
});

describe('Error handling', function () {
    beforeEach(function () {
        Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);
    });

    it('returns null buffer status when YNAB API throws exception', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andThrow(new \Exception('API Error'));

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        expect($bufferStatus)->toBeNull();
    });

    it('shows loading state when buffer status is null', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andThrow(new \Exception('API Error'));

        app()->instance(YnabService::class, $mockYnabService);

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertSee(__('app.loading_recommendations'));
    });
});
