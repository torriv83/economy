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
        Setting::create(['key' => 'buffer.target_amount', 'value' => '50000', 'type' => 'float', 'group' => 'buffer']);
        Setting::create(['key' => 'buffer.categories', 'value' => json_encode([]), 'type' => 'json', 'group' => 'buffer']);

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
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

        app()->instance(YnabService::class, $mockYnabService);

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertDontSee(__('app.ynab_required_for_recommendations'))
            ->assertSee(__('app.preparedness'));
    });
});

describe('Buffer status calculations', function () {
    beforeEach(function () {
        Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);
        Setting::create(['key' => 'buffer.target_amount', 'value' => '50000', 'type' => 'float', 'group' => 'buffer']);
        Setting::create(['key' => 'buffer.categories', 'value' => json_encode([]), 'type' => 'json', 'group' => 'buffer']);
    });

    it('calculates emergency buffer correctly', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 30000],
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Emergency buffer amount should be the sum of savings accounts
        expect($bufferStatus['emergency_buffer']['amount'])->toEqual(30000);

        // Emergency buffer percentage should be (30000 / 50000) * 100 = 60%
        expect($bufferStatus['emergency_buffer']['percentage'])->toEqual(60);

        // Target should be the configured target amount
        expect($bufferStatus['emergency_buffer']['target'])->toEqual(50000);
    });

    it('calculates pay period status correctly', function () {
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
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Pay period should be covered when funded >= needed
        expect($bufferStatus['pay_period']['funded'])->toEqual(25000);
        expect($bufferStatus['pay_period']['needed'])->toEqual(25000);
        expect($bufferStatus['pay_period']['is_covered'])->toBeTrue();
    });

    it('calculates dedicated categories correctly', function () {
        // Override with categories config
        Setting::where('key', 'buffer.categories')->delete();
        Setting::create([
            'key' => 'buffer.categories',
            'value' => json_encode([
                ['name' => 'Bil vedlikehold', 'target' => 10000],
                ['name' => 'Forsikring', 'target' => 5000],
            ]),
            'type' => 'json',
            'group' => 'buffer',
        ]);

        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([['name' => 'Savings', 'balance' => 50000]]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->with(['Bil vedlikehold', 'Forsikring'])
            ->andReturn(collect([
                ['name' => 'Bil vedlikehold', 'balance' => 3000],
                ['name' => 'Forsikring', 'balance' => 5000],
            ]));

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        expect($bufferStatus['dedicated_categories'])->toHaveCount(2);

        // First category: 3000 / 10000 = 30%
        expect($bufferStatus['dedicated_categories'][0]['name'])->toBe('Bil vedlikehold');
        expect($bufferStatus['dedicated_categories'][0]['balance'])->toEqual(3000);
        expect($bufferStatus['dedicated_categories'][0]['target'])->toEqual(10000);
        expect($bufferStatus['dedicated_categories'][0]['percentage'])->toEqual(30);

        // Second category: 5000 / 5000 = 100%
        expect($bufferStatus['dedicated_categories'][1]['name'])->toBe('Forsikring');
        expect($bufferStatus['dedicated_categories'][1]['percentage'])->toEqual(100);
    });

    it('returns critical status when pay period is not covered', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 50000],
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 10000,
                'monthly_essential' => 25000,
            ]);
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Critical: pay period not covered
        expect($bufferStatus['status'])->toBe('critical');
        expect($bufferStatus['pay_period']['is_covered'])->toBeFalse();
    });

    it('returns critical status when emergency buffer is below 25%', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 10000], // 10000 / 50000 = 20%
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Critical: emergency buffer below 25%
        expect($bufferStatus['status'])->toBe('critical');
        expect($bufferStatus['emergency_buffer']['percentage'])->toEqual(20);
    });

    it('returns warning status when emergency buffer is below 75%', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 25000], // 25000 / 50000 = 50%
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Warning: emergency buffer between 25% and 75%
        expect($bufferStatus['status'])->toBe('warning');
        expect($bufferStatus['emergency_buffer']['percentage'])->toEqual(50);
    });

    it('returns warning status when any dedicated category is below 50%', function () {
        // Override with categories config
        Setting::where('key', 'buffer.categories')->delete();
        Setting::create([
            'key' => 'buffer.categories',
            'value' => json_encode([
                ['name' => 'Bil vedlikehold', 'target' => 10000],
            ]),
            'type' => 'json',
            'group' => 'buffer',
        ]);

        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 50000], // 100% - healthy emergency buffer
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->with(['Bil vedlikehold'])
            ->andReturn(collect([
                ['name' => 'Bil vedlikehold', 'balance' => 4000], // 40% - below 50%
            ]));

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Warning: dedicated category below 50%
        expect($bufferStatus['status'])->toBe('warning');
    });

    it('returns healthy status when all buffers are sufficient', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([
                ['name' => 'Savings', 'balance' => 50000], // 100%
            ]));
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        expect($bufferStatus['status'])->toBe('healthy');
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
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

        app()->instance(YnabService::class, $mockYnabService);

        $component = Livewire::test(Recommendations::class)
            ->call('loadData');

        $bufferStatus = $component->get('bufferStatus');

        // Should not crash, pay period should be considered covered when needed is 0
        expect($bufferStatus['pay_period']['is_covered'])->toBeTrue();
    });
});

describe('Livewire actions', function () {
    beforeEach(function () {
        Setting::create(['key' => 'ynab.enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.token', 'value' => encrypt('test-token'), 'type' => 'encrypted', 'group' => 'ynab']);
        Setting::create(['key' => 'ynab.budget_id', 'value' => 'test-budget', 'type' => 'string', 'group' => 'ynab']);
        Setting::create(['key' => 'buffer.target_amount', 'value' => '50000', 'type' => 'float', 'group' => 'buffer']);
        Setting::create(['key' => 'buffer.categories', 'value' => json_encode([]), 'type' => 'json', 'group' => 'buffer']);
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
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

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
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

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
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

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
        Setting::create(['key' => 'buffer.target_amount', 'value' => '50000', 'type' => 'float', 'group' => 'buffer']);
        Setting::create(['key' => 'buffer.categories', 'value' => json_encode([]), 'type' => 'json', 'group' => 'buffer']);
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
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

        app()->instance(YnabService::class, $mockYnabService);

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertSee(__('app.buffer_status_healthy'));
    });

    it('displays warning status badge correctly', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([['name' => 'Savings', 'balance' => 25000]])); // 50% - warning
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

        app()->instance(YnabService::class, $mockYnabService);

        Livewire::test(Recommendations::class)
            ->call('loadData')
            ->assertSee(__('app.buffer_status_warning'));
    });

    it('displays critical status badge correctly', function () {
        $mockYnabService = Mockery::mock(YnabService::class);
        $mockYnabService->shouldReceive('fetchSavingsAccounts')
            ->andReturn(collect([['name' => 'Savings', 'balance' => 10000]])); // 20% - critical
        $mockYnabService->shouldReceive('fetchPayPeriodShortfall')
            ->andReturn([
                'funded' => 25000,
                'monthly_essential' => 25000,
            ]);
        $mockYnabService->shouldReceive('fetchCategoriesByNames')
            ->andReturn(collect([]));

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
        Setting::create(['key' => 'buffer.target_amount', 'value' => '50000', 'type' => 'float', 'group' => 'buffer']);
        Setting::create(['key' => 'buffer.categories', 'value' => json_encode([]), 'type' => 'json', 'group' => 'buffer']);
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
