<?php

declare(strict_types=1);

use App\Models\Debt;
use App\Services\AccelerationService;
use App\Services\BufferRecommendationService;
use App\Services\DebtCalculationService;
use App\Services\SettingsService;
use App\Services\YnabService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock YNAB service
    $this->ynabService = Mockery::mock(YnabService::class);

    // Mock settings service
    $this->settingsService = Mockery::mock(SettingsService::class);
    $this->settingsService->shouldReceive('get')
        ->with('payoff_settings.extra_payment', 'float')
        ->andReturn(2000.0);
    $this->settingsService->shouldReceive('get')
        ->with('payoff_settings.strategy', 'string')
        ->andReturn('avalanche');
    $this->settingsService->shouldReceive('getHighInterestThreshold')
        ->andReturn(15.0);

    // Create real instances of services that need database interaction
    $this->calculationService = app(DebtCalculationService::class);
    $this->accelerationService = Mockery::mock(AccelerationService::class);

    $this->service = new BufferRecommendationService(
        $this->ynabService,
        $this->accelerationService,
        $this->calculationService,
        $this->settingsService
    );
});

/**
 * Helper to create a valid buffer status array with the new structure.
 *
 * @param  array<string, mixed>  $overrides
 * @return array{emergency_buffer: array{amount: float, target: float, percentage: float}, dedicated_categories: array<int, array{name: string, balance: float, target: float, percentage: float}>, pay_period: array{funded: float, needed: float, is_covered: bool, start_date: string, end_date: string}, status: string}
 */
function createBufferStatus(array $overrides = []): array
{
    $defaults = [
        'emergency_buffer' => [
            'amount' => 50000.0,
            'target' => 50000.0,
            'percentage' => 100.0,
        ],
        'dedicated_categories' => [],
        'pay_period' => [
            'funded' => 25000.0,
            'needed' => 25000.0,
            'is_covered' => true,
            'start_date' => '20. des',
            'end_date' => '19. jan',
        ],
        'status' => 'healthy',
    ];

    return array_replace_recursive($defaults, $overrides);
}

// Priority 1: Pay Period Tests

it('returns success recommendation when pay period is covered', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = createBufferStatus([
        'pay_period' => [
            'funded' => 30000.0,
            'needed' => 25000.0,
            'is_covered' => true,
        ],
    ]);

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->not->toBeEmpty()
        ->and($recommendations[0])->toMatchArray([
            'priority' => 1,
            'type' => 'pay_period',
            'icon' => 'check-circle',
            'status' => 'success',
            'title' => 'buffer.pay_period_covered_title',
            'description' => 'buffer.pay_period_covered_description',
        ])
        ->and($recommendations[0]['params'])->toHaveKeys(['funded', 'needed']);
});

it('returns warning recommendation when pay period is not covered', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = createBufferStatus([
        'pay_period' => [
            'funded' => 15000.0,
            'needed' => 25000.0,
            'is_covered' => false,
        ],
        'status' => 'critical',
    ]);

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->not->toBeEmpty()
        ->and($recommendations[0])->toMatchArray([
            'priority' => 1,
            'type' => 'pay_period',
            'icon' => 'exclamation-triangle',
            'status' => 'warning',
            'title' => 'buffer.pay_period_not_covered_title',
            'description' => 'buffer.pay_period_not_covered_description',
        ])
        ->and($recommendations[0]['params'])->toHaveKeys(['shortfall', 'funded', 'needed'])
        ->and($recommendations[0]['params']['shortfall'])->toBe(10000.0);
});

// Priority 2: Minimum Buffer Tests (buffer < 50%)

it('returns minimum buffer recommendation when emergency buffer is below 50%', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = createBufferStatus([
        'emergency_buffer' => [
            'amount' => 20000.0,
            'target' => 50000.0,
            'percentage' => 40.0, // Below 50%
        ],
        'status' => 'warning',
    ]);

    $recommendations = $this->service->getRecommendations($bufferStatus);

    // Should have pay period + minimum buffer recommendation (priority 2)
    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1])->toMatchArray([
            'priority' => 2,
            'type' => 'minimum_buffer',
            'icon' => 'shield-exclamation',
            'status' => 'warning',
            'title' => 'buffer.minimum_buffer_title',
            'description' => 'buffer.minimum_buffer_description',
        ])
        ->and($recommendations[1]['params'])->toHaveKeys(['current_amount', 'target_50', 'shortfall', 'percentage'])
        ->and($recommendations[1]['params']['target_50'])->toBe(25000.0); // 50% of 50000
});

it('shows all good when emergency buffer is at target and no debts', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = createBufferStatus([
        'emergency_buffer' => [
            'amount' => 50000.0,
            'target' => 50000.0,
            'percentage' => 100.0,
        ],
    ]);

    $recommendations = $this->service->getRecommendations($bufferStatus);

    // New algorithm: pay period + all_good (priority 7) when everything is covered
    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1])->toMatchArray([
            'priority' => 7,
            'type' => 'all_good',
            'icon' => 'sparkles',
            'status' => 'success',
            'title' => 'buffer.all_good_title',
            'description' => 'buffer.all_good_description',
        ]);
});

// Priority 4: Dedicated Category Tests

it('returns recommendation for lowest dedicated category', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = createBufferStatus([
        'dedicated_categories' => [
            [
                'name' => 'Bil vedlikehold',
                'balance' => 2000.0,
                'target' => 10000.0,
                'percentage' => 20.0,
            ],
            [
                'name' => 'Forsikring',
                'balance' => 8000.0,
                'target' => 10000.0,
                'percentage' => 80.0,
            ],
        ],
        'status' => 'warning',
    ]);

    $recommendations = $this->service->getRecommendations($bufferStatus);

    // New algorithm: pay period + ONE category recommendation (most urgent/lowest %)
    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1])->toMatchArray([
            'priority' => 4,
            'type' => 'dedicated_category',
            'icon' => 'wallet',
            'status' => 'action',
            'title' => 'buffer.category_low_title',
            'description' => 'buffer.category_low_description',
        ])
        ->and($recommendations[1]['params'])->toHaveKeys(['category_name', 'current_amount', 'target_amount', 'shortfall', 'percentage'])
        ->and($recommendations[1]['params']['category_name'])->toBe('Bil vedlikehold') // Lowest percentage first
        ->and($recommendations[1]['params']['shortfall'])->toBe(8000.0);
});

it('skips dedicated categories when all are at target', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = createBufferStatus([
        'dedicated_categories' => [
            [
                'name' => 'Bil vedlikehold',
                'balance' => 10000.0,
                'target' => 10000.0,
                'percentage' => 100.0,
            ],
        ],
    ]);

    $recommendations = $this->service->getRecommendations($bufferStatus);

    // Should have pay period + buffer success (no category recommendations)
    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1]['type'])->not->toBe('dedicated_category');
});

// Priority 3: High Interest Debt Tests (>10%)

it('recommends paying high interest debt when rate is above 10%', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    Debt::factory()->create([
        'name' => 'Credit Card',
        'balance' => 10000,
        'interest_rate' => 20.0, // Above 10% threshold
        'minimum_payment' => 300,
    ]);

    $bufferStatus = createBufferStatus();

    $recommendations = $this->service->getRecommendations($bufferStatus);

    // High interest debt (>10%) has priority 3
    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1])->toMatchArray([
            'priority' => 3,
            'type' => 'high_interest_debt',
            'icon' => 'fire',
            'status' => 'action',
            'title' => 'buffer.high_interest_debt_title',
            'description' => 'buffer.high_interest_debt_description',
        ])
        ->and($recommendations[1]['params'])->toHaveKeys(['debt_name', 'debt_balance', 'interest_rate', 'total_debt', 'debt_count'])
        ->and($recommendations[1]['params']['debt_name'])->toBe('Credit Card');
});

it('shows highest interest debt first in recommendation', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    Debt::factory()->create([
        'name' => 'Low Interest Loan',
        'balance' => 50000,
        'interest_rate' => 5.0, // Below 10% threshold
        'minimum_payment' => 1000,
    ]);

    Debt::factory()->create([
        'name' => 'High Interest Card',
        'balance' => 10000,
        'interest_rate' => 25.0, // Above 10% threshold
        'minimum_payment' => 300,
    ]);

    $bufferStatus = createBufferStatus();

    $recommendations = $this->service->getRecommendations($bufferStatus);

    // High interest debt should be recommended first (type: high_interest_debt)
    expect($recommendations[1]['type'])->toBe('high_interest_debt')
        ->and($recommendations[1]['params']['debt_name'])->toBe('High Interest Card')
        ->and($recommendations[1]['params']['interest_rate'])->toBe(25.0);
});

// Priority 6: Regular Debt Tests (low interest)

it('recommends paying low interest debt when buffers are full', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    Debt::factory()->create([
        'name' => 'Car Loan',
        'balance' => 50000,
        'interest_rate' => 5.0, // Below 10% threshold
        'minimum_payment' => 1000,
    ]);

    $bufferStatus = createBufferStatus();

    $recommendations = $this->service->getRecommendations($bufferStatus);

    // Low interest debt has priority 6 (regular debt)
    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1])->toMatchArray([
            'priority' => 6,
            'type' => 'debt',
            'icon' => 'banknotes',
            'status' => 'action',
            'title' => 'buffer.pay_debt_title',
            'description' => 'buffer.pay_debt_description',
        ])
        ->and($recommendations[1]['params']['debt_name'])->toBe('Car Loan');
});

// compareScenarios Tests

it('compares buffer vs debt scenarios correctly', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    Debt::factory()->create([
        'name' => 'Credit Card',
        'balance' => 10000,
        'interest_rate' => 20.0,
        'minimum_payment' => 300,
    ]);

    Debt::factory()->create([
        'name' => 'Car Loan',
        'balance' => 50000,
        'interest_rate' => 5.0,
        'minimum_payment' => 1000,
    ]);

    $bufferStatus = createBufferStatus();

    $comparison = $this->service->compareScenarios(5000.0, $bufferStatus);

    expect($comparison)->toHaveKeys(['amount', 'options', 'recommendation'])
        ->and($comparison['amount'])->toBe(5000.0)
        ->and($comparison['options'])->toHaveCount(3) // Buffer + 2 debts
        ->and($comparison['options'][0]['target'])->toBe('buffer')
        ->and($comparison['options'][0]['impact'])->toHaveKeys(['amount_added', 'new_amount', 'target_amount', 'new_percentage'])
        ->and($comparison['options'][1]['target'])->toBe('debt')
        ->and($comparison['options'][1]['debt_name'])->toBe('Credit Card')
        ->and($comparison['options'][2]['target'])->toBe('debt')
        ->and($comparison['options'][2]['debt_name'])->toBe('Car Loan')
        ->and($comparison['recommendation'])->toHaveKeys(['target', 'reason']);
});

it('recommends pay period when not covered in scenario comparison', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    Debt::factory()->create([
        'name' => 'Credit Card',
        'balance' => 10000,
        'interest_rate' => 20.0,
        'minimum_payment' => 300,
    ]);

    $bufferStatus = createBufferStatus([
        'pay_period' => [
            'funded' => 15000.0,
            'needed' => 25000.0,
            'is_covered' => false,
        ],
        'status' => 'critical',
    ]);

    $comparison = $this->service->compareScenarios(5000.0, $bufferStatus);

    expect($comparison['recommendation']['target'])->toBe('pay_period')
        ->and($comparison['recommendation']['reason'])->toBe('buffer.recommendation_pay_period');
});

it('recommends emergency buffer when below target in scenario comparison', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    // Low interest debt (below threshold) so buffer can take priority
    Debt::factory()->create([
        'name' => 'Car Loan',
        'balance' => 10000,
        'interest_rate' => 5.0,
        'minimum_payment' => 300,
    ]);

    $bufferStatus = createBufferStatus([
        'emergency_buffer' => [
            'amount' => 25000.0,
            'target' => 50000.0,
            'percentage' => 50.0,
        ],
        'status' => 'warning',
    ]);

    $comparison = $this->service->compareScenarios(5000.0, $bufferStatus);

    expect($comparison['recommendation']['target'])->toBe('buffer')
        ->and($comparison['recommendation']['reason'])->toBe('buffer.recommendation_emergency_buffer');
});

it('recommends dedicated category when below target in scenario comparison', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    // Low interest debt (below threshold) so category can take priority
    Debt::factory()->create([
        'name' => 'Car Loan',
        'balance' => 10000,
        'interest_rate' => 5.0,
        'minimum_payment' => 300,
    ]);

    $bufferStatus = createBufferStatus([
        'dedicated_categories' => [
            [
                'name' => 'Bil vedlikehold',
                'balance' => 2000.0,
                'target' => 10000.0,
                'percentage' => 20.0,
            ],
        ],
        'status' => 'warning',
    ]);

    $comparison = $this->service->compareScenarios(5000.0, $bufferStatus);

    expect($comparison['recommendation']['target'])->toBe('category')
        ->and($comparison['recommendation']['reason'])->toBe('buffer.recommendation_dedicated_category');
});

it('recommends paying high interest debt when buffers are full in scenario comparison', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    // High interest debt (above 15% threshold)
    Debt::factory()->create([
        'name' => 'Credit Card',
        'balance' => 50000,
        'interest_rate' => 25.0,
        'minimum_payment' => 1500,
    ]);

    $bufferStatus = createBufferStatus();

    $comparison = $this->service->compareScenarios(5000.0, $bufferStatus);

    expect($comparison['recommendation']['target'])->toBe('debt')
        ->and($comparison['recommendation']['reason'])->toBe('buffer.recommendation_high_interest_debt');
});

it('recommends paying low interest debt when buffers are full in scenario comparison', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    // Low interest debt (below 15% threshold)
    Debt::factory()->create([
        'name' => 'Car Loan',
        'balance' => 50000,
        'interest_rate' => 5.0,
        'minimum_payment' => 1500,
    ]);

    $bufferStatus = createBufferStatus();

    $comparison = $this->service->compareScenarios(5000.0, $bufferStatus);

    expect($comparison['recommendation']['target'])->toBe('debt')
        ->and($comparison['recommendation']['reason'])->toBe('buffer.recommendation_pay_debt');
});

// Helper Method Tests

it('finds highest interest debt correctly', function () {
    Debt::factory()->create([
        'name' => 'Low Interest',
        'balance' => 10000,
        'interest_rate' => 5.0,
    ]);

    Debt::factory()->create([
        'name' => 'High Interest',
        'balance' => 5000,
        'interest_rate' => 20.0,
    ]);

    Debt::factory()->create([
        'name' => 'Medium Interest',
        'balance' => 15000,
        'interest_rate' => 10.0,
    ]);

    $highestDebt = $this->service->getHighestInterestDebt();

    expect($highestDebt)->not->toBeNull()
        ->and($highestDebt->name)->toBe('High Interest')
        ->and($highestDebt->interest_rate)->toBe(20.0);
});

it('returns null when no debts exist', function () {
    $highestDebt = $this->service->getHighestInterestDebt();

    expect($highestDebt)->toBeNull();
});

// Edge Cases

it('returns empty recommendations when ynab is not configured', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(false);

    $bufferStatus = createBufferStatus();

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->toBeEmpty();
});

it('handles zero needed in pay period gracefully', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = createBufferStatus([
        'pay_period' => [
            'funded' => 0.0,
            'needed' => 0.0,
            'is_covered' => true,
        ],
    ]);

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->not->toBeEmpty()
        ->and($recommendations[0]['type'])->toBe('pay_period');
});

it('handles zero target in emergency buffer gracefully', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = createBufferStatus([
        'emergency_buffer' => [
            'amount' => 10000.0,
            'target' => 0.0,
            'percentage' => 0.0,
        ],
    ]);

    $recommendations = $this->service->getRecommendations($bufferStatus);

    // Should still work and return recommendations
    expect($recommendations)->not->toBeEmpty();
});
