<?php

declare(strict_types=1);

use App\Models\Debt;
use App\Services\AccelerationService;
use App\Services\DebtCalculationService;
use App\Services\PayoffSettingsService;
use App\Services\YnabService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a mock YnabService
    $this->ynabService = Mockery::mock(YnabService::class);
    $this->calculationService = app(DebtCalculationService::class);
    $this->settingsService = app(PayoffSettingsService::class);

    $this->service = new AccelerationService(
        $this->ynabService,
        $this->calculationService,
        $this->settingsService
    );
});

it('throws exception when YNAB API fails', function () {
    $debt = Debt::factory()->create();

    $this->ynabService->shouldReceive('fetchBudgetSummary')
        ->andThrow(new \Exception('API Error'));

    $this->service->getOpportunities($debt);
})->throws(\Exception::class, 'API Error');

it('includes ready to assign when available', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $this->ynabService->shouldReceive('fetchBudgetSummary')
        ->andReturn([
            'ready_to_assign' => 2000.0,
            'currency_format' => null,
        ]);

    $this->ynabService->shouldReceive('fetchCategories')
        ->andReturn(collect());

    $opportunities = $this->service->getOpportunities($debt);

    expect($opportunities)->toHaveCount(1);
    expect($opportunities->first()['source'])->toBe('ready_to_assign');
    expect($opportunities->first()['amount'])->toBe(2000.0);
    expect($opportunities->first()['tier'])->toBe(1);
});

it('skips ready to assign when zero', function () {
    $debt = Debt::factory()->create();

    $this->ynabService->shouldReceive('fetchBudgetSummary')
        ->andReturn([
            'ready_to_assign' => 0.0,
            'currency_format' => null,
        ]);

    $this->ynabService->shouldReceive('fetchCategories')
        ->andReturn(collect());

    $opportunities = $this->service->getOpportunities($debt);

    expect($opportunities)->toBeEmpty();
});

it('identifies overfunded categories as tier 1', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $this->ynabService->shouldReceive('fetchBudgetSummary')
        ->andReturn(['ready_to_assign' => 0.0, 'currency_format' => null]);

    // Using NEED goal type (essential) so it's only detected as overfunded, not savings
    $this->ynabService->shouldReceive('fetchCategories')
        ->andReturn(collect([
            [
                'id' => 'cat-1',
                'name' => 'Groceries',
                'group_name' => 'Needs',
                'balance' => 1500.0,
                'budgeted' => 1000.0,
                'activity' => -500.0, // Some spending activity
                'goal_type' => 'NEED', // Essential goal type
                'goal_target' => 1000.0,
                'goal_under_funded' => 0.0,
                'goal_percentage_complete' => 100,
                'is_overfunded' => true,
                'has_goal' => true,
            ],
        ]));

    $opportunities = $this->service->getOpportunities($debt);

    expect($opportunities)->toHaveCount(1);
    $opp = $opportunities->first();
    expect($opp['name'])->toBe('Groceries');
    expect($opp['amount'])->toBe(500.0); // 1500 balance - 1000 goal target
    expect($opp['tier'])->toBe(1);
});

it('identifies categories with balance but no goal as tier 1', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $this->ynabService->shouldReceive('fetchBudgetSummary')
        ->andReturn(['ready_to_assign' => 0.0, 'currency_format' => null]);

    $this->ynabService->shouldReceive('fetchCategories')
        ->andReturn(collect([
            [
                'id' => 'cat-1',
                'name' => 'Miscellaneous',
                'group_name' => 'Other',
                'balance' => 800.0,
                'budgeted' => 800.0,
                'activity' => 0.0,
                'goal_type' => null,
                'goal_target' => 0.0,
                'goal_under_funded' => 0.0,
                'goal_percentage_complete' => null,
                'is_overfunded' => false,
                'has_goal' => false,
            ],
        ]));

    $opportunities = $this->service->getOpportunities($debt);

    expect($opportunities)->toHaveCount(1);
    $opp = $opportunities->first();
    expect($opp['name'])->toBe('Miscellaneous');
    expect($opp['amount'])->toBe(800.0);
    expect($opp['tier'])->toBe(1);
});

it('identifies discretionary categories as tier 2', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $this->ynabService->shouldReceive('fetchBudgetSummary')
        ->andReturn(['ready_to_assign' => 0.0, 'currency_format' => null]);

    // Smart detection: category with NEED goal, properly funded, but zero activity
    // This means money is sitting unused - potentially discretionary
    $this->ynabService->shouldReceive('fetchCategories')
        ->andReturn(collect([
            [
                'id' => 'cat-1',
                'name' => 'Entertainment',
                'group_name' => 'Fun',
                'balance' => 200.0,
                'budgeted' => 200.0,
                'activity' => 0.0, // No activity = money sitting unused
                'goal_type' => 'NEED', // Essential goal, properly funded
                'goal_target' => 200.0,
                'goal_under_funded' => 0.0,
                'goal_percentage_complete' => 100,
                'is_overfunded' => false, // Not overfunded (so won't be tier 1)
                'has_goal' => true,
            ],
        ]));

    $opportunities = $this->service->getOpportunities($debt);

    expect($opportunities)->toHaveCount(1);
    $opp = $opportunities->first();
    expect($opp['name'])->toBe('Entertainment');
    expect($opp['tier'])->toBe(2); // Tier 2: discretionary (zero activity = unused money)
});

it('identifies savings categories as tier 3 with warning', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $this->ynabService->shouldReceive('fetchBudgetSummary')
        ->andReturn(['ready_to_assign' => 0.0, 'currency_format' => null]);

    $this->ynabService->shouldReceive('fetchCategories')
        ->andReturn(collect([
            [
                'id' => 'cat-1',
                'name' => 'Emergency Fund',
                'group_name' => 'Savings',
                'balance' => 5000.0,
                'budgeted' => 5000.0,
                'activity' => 0.0,
                'goal_type' => 'TB',
                'goal_target' => 5000.0,
                'goal_under_funded' => 0.0,
                'goal_percentage_complete' => 100,
                'is_overfunded' => false,
                'has_goal' => true,
            ],
        ]));

    $opportunities = $this->service->getOpportunities($debt);

    expect($opportunities)->toHaveCount(1);
    $opp = $opportunities->first();
    expect($opp['name'])->toBe('Emergency Fund');
    expect($opp['tier'])->toBe(3);
    expect($opp['warning'])->toBe(__('app.savings_redirect_warning'));
});

it('skips savings categories with balance under 1000', function () {
    $debt = Debt::factory()->create();

    $this->ynabService->shouldReceive('fetchBudgetSummary')
        ->andReturn(['ready_to_assign' => 0.0, 'currency_format' => null]);

    $this->ynabService->shouldReceive('fetchCategories')
        ->andReturn(collect([
            [
                'id' => 'cat-1',
                'name' => 'Emergency Fund',
                'group_name' => 'Savings',
                'balance' => 500.0, // Below 1000 threshold
                'budgeted' => 500.0,
                'activity' => 0.0,
                'goal_type' => 'TB',
                'goal_target' => 500.0,
                'goal_under_funded' => 0.0,
                'goal_percentage_complete' => 100,
                'is_overfunded' => false,
                'has_goal' => true,
            ],
        ]));

    $opportunities = $this->service->getOpportunities($debt);

    expect($opportunities)->toBeEmpty();
});

it('calculates impact for each opportunity', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 50000,
        'interest_rate' => 15,
        'minimum_payment' => 2000,
    ]);

    $this->ynabService->shouldReceive('fetchBudgetSummary')
        ->andReturn(['ready_to_assign' => 5000.0, 'currency_format' => null]);

    $this->ynabService->shouldReceive('fetchCategories')
        ->andReturn(collect());

    $opportunities = $this->service->getOpportunities($debt);

    expect($opportunities)->toHaveCount(1);
    $opp = $opportunities->first();
    expect($opp['impact'])->toHaveKeys(['months_saved', 'weeks_saved', 'interest_saved', 'new_payoff_date']);
    expect($opp['impact']['months_saved'])->toBeGreaterThanOrEqual(0);
    expect($opp['impact']['interest_saved'])->toBeGreaterThanOrEqual(0);
});

it('does not duplicate categories already in tier 1', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $this->ynabService->shouldReceive('fetchBudgetSummary')
        ->andReturn(['ready_to_assign' => 0.0, 'currency_format' => null]);

    // Category is overfunded (tier 1) and would also pass discretionary check (zero activity)
    // Should only appear once as tier 1, not duplicated in tier 2
    $this->ynabService->shouldReceive('fetchCategories')
        ->andReturn(collect([
            [
                'id' => 'cat-1',
                'name' => 'Movies',
                'group_name' => 'Entertainment',
                'balance' => 1500.0,
                'budgeted' => 1000.0,
                'activity' => 0.0, // Zero activity would trigger discretionary
                'goal_type' => 'NEED', // Using NEED so it doesn't also go to tier 3 savings
                'goal_target' => 1000.0,
                'goal_under_funded' => 0.0,
                'goal_percentage_complete' => 100,
                'is_overfunded' => true,
                'has_goal' => true,
            ],
        ]));

    $opportunities = $this->service->getOpportunities($debt);

    // Should only have 1 entry - tier 2 skips categories already in tier 1
    expect($opportunities->where('name', 'Movies')->count())->toBe(1);
    expect($opportunities->first()['tier'])->toBe(1); // Should be tier 1 (overfunded)
});

it('detects discretionary based on activity regardless of category language', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $this->ynabService->shouldReceive('fetchBudgetSummary')
        ->andReturn(['ready_to_assign' => 0.0, 'currency_format' => null]);

    // Smart detection works regardless of language - it's based on goal type and activity
    // Norwegian category name with zero activity = discretionary (tier 2)
    $this->ynabService->shouldReceive('fetchCategories')
        ->andReturn(collect([
            [
                'id' => 'cat-1',
                'name' => 'Kino',
                'group_name' => 'Underholdning', // Norwegian for Entertainment
                'balance' => 300.0,
                'budgeted' => 300.0,
                'activity' => 0.0, // Zero activity = unused money
                'goal_type' => 'NEED', // Not a savings goal
                'goal_target' => 300.0,
                'goal_under_funded' => 0.0,
                'goal_percentage_complete' => 100,
                'is_overfunded' => false,
                'has_goal' => true,
            ],
        ]));

    $opportunities = $this->service->getOpportunities($debt);

    expect($opportunities)->toHaveCount(1);
    expect($opportunities->first()['tier'])->toBe(2);
});
