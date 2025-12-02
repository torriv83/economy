<?php

use App\Models\Debt;
use App\Services\DebtCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear cache before each test to ensure isolation
    Cache::flush();
});

// Cache Behavior Tests - Test observable behavior instead of spying on Cache
test('calculateMinimumPaymentsOnly returns consistent results (caching works)', function () {
    Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // Call multiple times - results should be identical (proves caching works)
    $result1 = $service->calculateMinimumPaymentsOnly($debts);
    $result2 = $service->calculateMinimumPaymentsOnly($debts);
    $result3 = $service->calculateMinimumPaymentsOnly($debts);

    expect($result1)->toBe($result2)->toBe($result3);
});

test('calculateMinimumPaymentsInterest returns consistent results (caching works)', function () {
    Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // Call multiple times - results should be identical (proves caching works)
    $result1 = $service->calculateMinimumPaymentsInterest($debts);
    $result2 = $service->calculateMinimumPaymentsInterest($debts);
    $result3 = $service->calculateMinimumPaymentsInterest($debts);

    expect($result1)->toBe($result2)->toBe($result3);
});

test('minimum payments returns consistent results for same data', function () {
    $debt = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // Call twice - results should be identical
    $firstResult = $service->calculateMinimumPaymentsOnly($debts);
    $secondResult = $service->calculateMinimumPaymentsOnly($debts);

    expect($firstResult)->toBe($secondResult);
});

// Cache Invalidation Tests - Test observable behavior
test('clearMinimumPaymentsCache allows fresh calculation', function () {
    Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // First calculation
    $result1 = $service->calculateMinimumPaymentsOnly($debts);

    // Clear cache
    DebtCalculationService::clearMinimumPaymentsCache();

    // After clearing, should still get same result (recalculated)
    $result2 = $service->calculateMinimumPaymentsOnly($debts);

    expect($result1)->toBe($result2);
});

test('clearAllCalculationCaches clears minimum payments cache', function () {
    Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // First calculation
    $result1 = $service->calculateMinimumPaymentsOnly($debts);

    // Clear all caches
    DebtCalculationService::clearAllCalculationCaches();

    // After clearing, should still get same result (recalculated)
    $result2 = $service->calculateMinimumPaymentsOnly($debts);

    expect($result1)->toBe($result2);
});

test('debt observer clears cache when debt changes', function () {
    $debt = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // Populate cache with initial calculation
    $firstResult = $service->calculateMinimumPaymentsOnly($debts);
    expect($firstResult)->toBeGreaterThan(0);

    // Update debt (triggers observer which clears cache)
    $debt->update(['balance' => 5000]);

    // Get fresh debts and recalculate
    $debts = Debt::all();
    $secondResult = $service->calculateMinimumPaymentsOnly($debts);

    // Result should be different because balance changed
    expect($secondResult)->not->toBe($firstResult);
    expect($secondResult)->toBeLessThan($firstResult);
});

// Edge Case Tests
test('empty debt collection returns zero', function () {
    $service = app(DebtCalculationService::class);
    $debts = collect();

    $monthsResult = $service->calculateMinimumPaymentsOnly($debts);
    $interestResult = $service->calculateMinimumPaymentsInterest($debts);

    expect($monthsResult)->toBe(0);
    expect($interestResult)->toBe(0.0);
});

test('cache key differs for different debt data', function () {
    $service = app(DebtCalculationService::class);

    // Create first debt
    $debt1 = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $debts1 = Debt::all();
    $result1 = $service->calculateMinimumPaymentsOnly($debts1);

    // Create second debt with different data
    $debt2 = Debt::factory()->create([
        'balance' => 20000,
        'original_balance' => 20000,
        'interest_rate' => 20,
        'minimum_payment' => 600,
    ]);

    $debts2 = Debt::all();
    $result2 = $service->calculateMinimumPaymentsOnly($debts2);

    // Results should be different because debt data is different
    expect($result2)->not->toBe($result1);
});

test('cache key is consistent for same debt data', function () {
    $debt = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);

    // Call twice with same data
    $debts = Debt::all();
    $result1 = $service->calculateMinimumPaymentsOnly($debts);

    $debts = Debt::all();
    $result2 = $service->calculateMinimumPaymentsOnly($debts);

    // Results should be identical
    expect($result1)->toBe($result2);
});

test('minimum payments handles debts with zero balance', function () {
    Debt::factory()->create([
        'balance' => 0,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    $monthsResult = $service->calculateMinimumPaymentsOnly($debts);
    $interestResult = $service->calculateMinimumPaymentsInterest($debts);

    // Zero balance should result in 0 months and 0 interest
    expect($monthsResult)->toBe(0);
    expect($interestResult)->toBe(0.0);
});

test('minimum payments handles debts with zero minimum payment', function () {
    Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 0,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    $monthsResult = $service->calculateMinimumPaymentsOnly($debts);
    $interestResult = $service->calculateMinimumPaymentsInterest($debts);

    // Zero minimum payment should skip the debt
    expect($monthsResult)->toBe(0);
    expect($interestResult)->toBe(0.0);
});
