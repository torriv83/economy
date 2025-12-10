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

    // Mock threshold and target methods used by BufferRecommendationService
    $this->settingsService->shouldReceive('getHighInterestThreshold')
        ->andReturn(20.0);
    $this->settingsService->shouldReceive('getLowInterestThreshold')
        ->andReturn(5.0);
    $this->settingsService->shouldReceive('getBufferTargetMonths')
        ->andReturn(2);
    $this->settingsService->shouldReceive('getMinInterestSavings')
        ->andReturn(100.0);

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

// Layer 1: Operational Buffer Tests

it('returns success recommendation when one month ahead', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = [
        'layer1' => [
            'amount' => 18000.0,
            'percentage' => 100,
            'is_month_ahead' => true,
        ],
        'layer2' => [
            'amount' => 27000.0,
            'months' => 1.5,
            'target_months' => 2,
        ],
        'total_buffer' => 45000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 2.5,
        'status' => 'healthy',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    // Should have Layer 1 success + Layer 2 recommendation (buffer or debt based on situation)
    expect($recommendations)->toBeGreaterThanOrEqual(1)
        ->and($recommendations[0])->toMatchArray([
            'priority' => 1,
            'type' => 'layer1',
            'icon' => 'check-circle',
            'status' => 'success',
        ]);
});

it('returns action recommendation when not one month ahead', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = [
        'layer1' => [
            'amount' => 9000.0,
            'percentage' => 50,
            'is_month_ahead' => false,
        ],
        'layer2' => [
            'amount' => 27000.0,
            'months' => 1.5,
            'target_months' => 2,
        ],
        'total_buffer' => 36000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 2.0,
        'status' => 'healthy',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->toHaveCount(2) // Layer 1 + Layer 2 (no hard rules)
        ->and($recommendations[0])->toMatchArray([
            'priority' => 1,
            'type' => 'layer1',
            'icon' => 'arrow-right',
            'status' => 'action',
        ])
        ->and($recommendations[0]['params'])->toHaveKeys(['shortfall', 'transfer_amount', 'remaining_buffer', 'remaining_months', 'still_needed'])
        ->and($recommendations[0]['params']['shortfall'])->toBe(9000.0)
        ->and($recommendations[0]['params']['transfer_amount'])->toBe(9000.0) // Can cover full shortfall
        ->and($recommendations[0]['action']['amount'])->toBe(9000.0)
        ->and($recommendations[0]['action']['target'])->toBe('checking');
});

it('caps transfer amount at available savings when savings is less than shortfall', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = [
        'layer1' => [
            'amount' => 0.0,
            'percentage' => 0,
            'is_month_ahead' => false,
        ],
        'layer2' => [
            'amount' => 5000.0, // Only 5000 kr in savings
            'months' => 0.28,
            'target_months' => 2,
        ],
        'total_buffer' => 5000.0,
        'monthly_essential' => 18000.0, // Need 18000 to be one month ahead
        'months_of_security' => 0.28,
        'status' => 'critical',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations[0]['params']['shortfall'])->toBe(18000.0) // Total needed
        ->and($recommendations[0]['params']['transfer_amount'])->toBe(5000.0) // Only what's available
        ->and($recommendations[0]['params']['still_needed'])->toBe(13000.0) // What's still needed
        ->and($recommendations[0]['action']['amount'])->toBe(5000.0) // Action capped at available
        ->and($recommendations[0]['description'])->toBe('buffer.layer1_partial_description');
});

it('shows no savings description when savings is zero', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = [
        'layer1' => [
            'amount' => 0.0,
            'percentage' => 0,
            'is_month_ahead' => false,
        ],
        'layer2' => [
            'amount' => 0.0, // No savings
            'months' => 0,
            'target_months' => 2,
        ],
        'total_buffer' => 0.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 0,
        'status' => 'critical',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations[0]['params']['shortfall'])->toBe(18000.0)
        ->and($recommendations[0]['params']['transfer_amount'])->toBe(0.0)
        ->and($recommendations[0]['action']['amount'])->toBe(0.0)
        ->and($recommendations[0]['description'])->toBe('buffer.layer1_no_savings_description');
});

it('still shows layer 2 recommendations even when not one month ahead', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    Debt::factory()->create([
        'name' => 'Credit Card',
        'balance' => 10000,
        'interest_rate' => 20.0,
        'minimum_payment' => 300,
    ]);

    $bufferStatus = [
        'layer1' => [
            'amount' => 9000.0,
            'percentage' => 50,
            'is_month_ahead' => false,
        ],
        'layer2' => [
            'amount' => 10000.0,
            'months' => 0.5,
            'target_months' => 2,
        ],
        'total_buffer' => 19000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 1.0,
        'status' => 'warning',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    // Should have Layer 1 + Layer 2/debt recommendations (no hard rules)
    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[0]['type'])->toBe('layer1')
        ->and($recommendations[1]['type'])->toBeIn(['buffer', 'debt', 'balanced']);
});

// Layer 2 & Debt: No Debts

it('recommends building buffer when no debts and buffer is low', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = [
        'layer1' => [
            'amount' => 18000.0,
            'percentage' => 100,
            'is_month_ahead' => true,
        ],
        'layer2' => [
            'amount' => 18000.0,
            'months' => 1.0,
            'target_months' => 2,
        ],
        'total_buffer' => 36000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 2.0,
        'status' => 'warning',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1])->toMatchArray([
            'priority' => 2,
            'type' => 'buffer',
            'icon' => 'shield',
            'status' => 'action',
        ])
        ->and($recommendations[1]['params'])->toHaveKeys(['current_months', 'target_months', 'shortfall'])
        ->and($recommendations[1]['params']['shortfall'])->toBe(18000.0);
});

it('shows success when buffer is good and no debts', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = [
        'layer1' => [
            'amount' => 18000.0,
            'percentage' => 100,
            'is_month_ahead' => true,
        ],
        'layer2' => [
            'amount' => 45000.0,
            'months' => 2.5,
            'target_months' => 2,
        ],
        'total_buffer' => 63000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 3.5,
        'status' => 'healthy',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1])->toMatchArray([
            'priority' => 2,
            'type' => 'buffer',
            'icon' => 'shield-check',
            'status' => 'success',
        ]);
});

// Scenario A: High Interest Debt (20%+)

it('recommends paying high interest debt even with low buffer', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $debt = Debt::factory()->create([
        'name' => 'Credit Card',
        'balance' => 10000,
        'interest_rate' => 22.0,
        'minimum_payment' => 300,
    ]);

    $bufferStatus = [
        'layer1' => [
            'amount' => 18000.0,
            'percentage' => 100,
            'is_month_ahead' => true,
        ],
        'layer2' => [
            'amount' => 10000.0,
            'months' => 0.5,
            'target_months' => 2,
        ],
        'total_buffer' => 28000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 1.5,
        'status' => 'warning',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1])->toMatchArray([
            'priority' => 2,
            'type' => 'debt',
            'icon' => 'banknotes',
            'status' => 'action',
        ])
        ->and($recommendations[1]['params'])->toHaveKeys(['debt_name', 'interest_rate', 'interest_saved', 'suggested_amount', 'available_savings'])
        ->and($recommendations[1]['params']['debt_name'])->toBe('Credit Card')
        ->and($recommendations[1]['params']['available_savings'])->toBe(10000.0)
        ->and($recommendations[1]['action'])->toHaveKeys(['type', 'amount', 'target', 'impact'])
        ->and($recommendations[1]['action']['type'])->toBe('pay_debt');
});

// Scenario B: Low Interest Debt (< 5%)

it('recommends building buffer first when debt interest is low', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    Debt::factory()->create([
        'name' => 'Car Loan',
        'balance' => 50000,
        'interest_rate' => 4.5,
        'minimum_payment' => 1000,
    ]);

    $bufferStatus = [
        'layer1' => [
            'amount' => 18000.0,
            'percentage' => 100,
            'is_month_ahead' => true,
        ],
        'layer2' => [
            'amount' => 18000.0,
            'months' => 1.0,
            'target_months' => 2,
        ],
        'total_buffer' => 36000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 2.0,
        'status' => 'warning',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1])->toMatchArray([
            'priority' => 2,
            'type' => 'buffer',
            'icon' => 'shield',
            'status' => 'action',
        ])
        ->and($recommendations[1]['params'])->toHaveKeys(['debt_name', 'interest_rate', 'current_buffer_months', 'target_buffer_months'])
        ->and($recommendations[1]['params']['debt_name'])->toBe('Car Loan')
        ->and($recommendations[1]['params']['interest_rate'])->toBe(4.5);
});

// Scenario C: Good Buffer + Debt

it('recommends paying debt when buffer is solid', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    Debt::factory()->create([
        'name' => 'Personal Loan',
        'balance' => 15000,
        'interest_rate' => 8.5,
        'minimum_payment' => 500,
    ]);

    $bufferStatus = [
        'layer1' => [
            'amount' => 18000.0,
            'percentage' => 100,
            'is_month_ahead' => true,
        ],
        'layer2' => [
            'amount' => 45000.0,
            'months' => 2.5,
            'target_months' => 2,
        ],
        'total_buffer' => 63000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 3.5,
        'status' => 'healthy',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1])->toMatchArray([
            'priority' => 2,
            'type' => 'debt',
            'icon' => 'banknotes',
            'status' => 'action',
        ])
        ->and($recommendations[1]['params'])->toHaveKeys(['debt_name', 'suggested_amount', 'interest_saved', 'months_saved'])
        ->and($recommendations[1]['action']['type'])->toBe('pay_debt');
});

// Scenario D: Balanced Situation

it('provides balanced recommendation for medium interest and medium buffer', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    Debt::factory()->create([
        'name' => 'Consumer Loan',
        'balance' => 20000,
        'interest_rate' => 10.0,
        'minimum_payment' => 600,
    ]);

    $bufferStatus = [
        'layer1' => [
            'amount' => 18000.0,
            'percentage' => 100,
            'is_month_ahead' => true,
        ],
        'layer2' => [
            'amount' => 27000.0,
            'months' => 1.5,
            'target_months' => 2,
        ],
        'total_buffer' => 45000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 2.5,
        'status' => 'healthy',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->toHaveCount(2)
        ->and($recommendations[1])->toMatchArray([
            'priority' => 2,
            'type' => 'balanced',
            'icon' => 'scale',
            'status' => 'info',
        ])
        ->and($recommendations[1]['params'])->toHaveKeys(['debt_name', 'interest_rate', 'current_buffer_months', 'buffer_shortfall']);
});

// compareScenarios Tests

it('compares buffer vs debt scenarios correctly', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $debt1 = Debt::factory()->create([
        'name' => 'Credit Card',
        'balance' => 10000,
        'interest_rate' => 20.0,
        'minimum_payment' => 300,
    ]);

    $debt2 = Debt::factory()->create([
        'name' => 'Car Loan',
        'balance' => 50000,
        'interest_rate' => 5.0,
        'minimum_payment' => 1000,
    ]);

    $bufferStatus = [
        'layer1' => [
            'amount' => 18000.0,
            'percentage' => 100,
            'is_month_ahead' => true,
        ],
        'layer2' => [
            'amount' => 27000.0,
            'months' => 1.5,
            'target_months' => 2,
        ],
        'total_buffer' => 45000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 2.5,
        'status' => 'healthy',
    ];

    $comparison = $this->service->compareScenarios(5000.0, $bufferStatus);

    expect($comparison)->toHaveKeys(['amount', 'options', 'recommendation'])
        ->and($comparison['amount'])->toBe(5000.0)
        ->and($comparison['options'])->toHaveCount(3) // Buffer + 2 debts
        ->and($comparison['options'][0]['target'])->toBe('buffer')
        ->and($comparison['options'][0]['impact'])->toHaveKeys(['days_of_security_added', 'new_buffer_months'])
        ->and($comparison['options'][1]['target'])->toBe('debt')
        ->and($comparison['options'][1]['debt_name'])->toBe('Credit Card')
        ->and($comparison['options'][2]['target'])->toBe('debt')
        ->and($comparison['options'][2]['debt_name'])->toBe('Car Loan')
        ->and($comparison['recommendation'])->toHaveKeys(['target', 'reason']);
});

it('recommends buffer when buffer is critically low in scenario comparison', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    Debt::factory()->create([
        'name' => 'Credit Card',
        'balance' => 10000,
        'interest_rate' => 20.0,
        'minimum_payment' => 300,
    ]);

    $bufferStatus = [
        'layer1' => [
            'amount' => 18000.0,
            'percentage' => 100,
            'is_month_ahead' => true,
        ],
        'layer2' => [
            'amount' => 10000.0,
            'months' => 0.5,
            'target_months' => 2,
        ],
        'total_buffer' => 28000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 1.5,
        'status' => 'warning',
    ];

    $comparison = $this->service->compareScenarios(5000.0, $bufferStatus);

    expect($comparison['recommendation']['target'])->toBe('buffer')
        ->and($comparison['recommendation']['reason'])->toBe('buffer.recommendation_critical_buffer');
});

it('recommends high interest debt when savings are significant', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    Debt::factory()->create([
        'name' => 'Credit Card',
        'balance' => 50000,
        'interest_rate' => 25.0,
        'minimum_payment' => 1500,
    ]);

    $bufferStatus = [
        'layer1' => [
            'amount' => 18000.0,
            'percentage' => 100,
            'is_month_ahead' => true,
        ],
        'layer2' => [
            'amount' => 36000.0,
            'months' => 2.0,
            'target_months' => 2,
        ],
        'total_buffer' => 54000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 3.0,
        'status' => 'healthy',
    ];

    $comparison = $this->service->compareScenarios(5000.0, $bufferStatus);

    expect($comparison['recommendation']['target'])->toBe('debt')
        ->and($comparison['recommendation']['reason'])->toBe('buffer.recommendation_high_interest');
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

    $bufferStatus = [
        'layer1' => ['amount' => 18000.0, 'percentage' => 100, 'is_month_ahead' => true],
        'layer2' => ['amount' => 27000.0, 'months' => 1.5, 'target_months' => 2],
        'total_buffer' => 45000.0,
        'monthly_essential' => 18000.0,
        'months_of_security' => 2.5,
        'status' => 'healthy',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->toBeEmpty();
});

it('handles zero monthly essential gracefully', function () {
    $this->settingsService->shouldReceive('isYnabConfigured')->andReturn(true);

    $bufferStatus = [
        'layer1' => [
            'amount' => 0.0,
            'percentage' => 0,
            'is_month_ahead' => false,
        ],
        'layer2' => [
            'amount' => 10000.0,
            'months' => 0,
            'target_months' => 2,
        ],
        'total_buffer' => 10000.0,
        'monthly_essential' => 0.0,
        'months_of_security' => 0,
        'status' => 'critical',
    ];

    $recommendations = $this->service->getRecommendations($bufferStatus);

    expect($recommendations)->toHaveCount(2) // Layer 1 + Layer 2 (no hard rules)
        ->and($recommendations[0]['type'])->toBe('layer1');
});
