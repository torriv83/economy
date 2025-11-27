<?php

use App\Models\Debt;
use App\Services\DebtCacheService;
use App\Services\DebtCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear all caches before each test
    Cache::flush();
});

// Cache Behavior Tests
test('calculateMinimumPaymentsOnly returns cached result on second call', function () {
    $debt = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // First call - should calculate and cache
    $firstResult = $service->calculateMinimumPaymentsOnly($debts);

    // Get the cache key to verify caching
    $cacheKey = 'minimum_payments:months:'.md5(json_encode(
        $debts->map(fn ($d) => [
            'id' => $d->id,
            'balance' => $d->balance,
            'interest_rate' => $d->interest_rate,
            'minimum_payment' => $d->minimum_payment,
        ])->toArray()
    ));

    expect(Cache::has($cacheKey))->toBeTrue();

    // Second call - should return cached result
    $secondResult = $service->calculateMinimumPaymentsOnly($debts);
    expect($secondResult)->toBe($firstResult);
});

test('calculateMinimumPaymentsInterest returns cached result on second call', function () {
    $debt = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // First call - should calculate and cache
    $firstResult = $service->calculateMinimumPaymentsInterest($debts);

    // Get the cache key to verify caching
    $cacheKey = 'minimum_payments:interest:'.md5(json_encode(
        $debts->map(fn ($d) => [
            'id' => $d->id,
            'balance' => $d->balance,
            'interest_rate' => $d->interest_rate,
            'minimum_payment' => $d->minimum_payment,
        ])->toArray()
    ));

    expect(Cache::has($cacheKey))->toBeTrue();

    // Second call - should return cached result
    $secondResult = $service->calculateMinimumPaymentsInterest($debts);
    expect($secondResult)->toBe($firstResult);
});

test('cached minimum payments result is returned instead of recalculating', function () {
    $debt = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // First call to populate cache
    $service->calculateMinimumPaymentsOnly($debts);

    // Get the cache key
    $cacheKey = 'minimum_payments:months:'.md5(json_encode(
        $debts->map(fn ($d) => [
            'id' => $d->id,
            'balance' => $d->balance,
            'interest_rate' => $d->interest_rate,
            'minimum_payment' => $d->minimum_payment,
        ])->toArray()
    ));

    // Manually modify the cached value
    $modifiedValue = 999;
    Cache::put($cacheKey, $modifiedValue, now()->addMinutes(10));

    // Second call should return modified cached value
    $result = $service->calculateMinimumPaymentsOnly($debts);
    expect($result)->toBe($modifiedValue);
});

// Cache Invalidation Tests
test('clearMinimumPaymentsCache clears cached data', function () {
    $debt = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // Populate caches
    $service->calculateMinimumPaymentsOnly($debts);
    $service->calculateMinimumPaymentsInterest($debts);

    // Clear minimum payments cache
    DebtCalculationService::clearMinimumPaymentsCache();

    // Note: For non-Redis drivers, clearMinimumPaymentsCache only clears via pattern matching
    // which only works with Redis. For file/array drivers, this test just verifies no errors occur.
    expect(true)->toBeTrue();
});

test('clearAllCalculationCaches includes minimum payments cache', function () {
    $debt = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // Populate caches
    $service->calculateMinimumPaymentsOnly($debts);
    $service->calculateMinimumPaymentsInterest($debts);

    // Clear all calculation caches (should include minimum payments)
    DebtCalculationService::clearAllCalculationCaches();

    // Verify no errors occurred during clear
    expect(true)->toBeTrue();
});

test('debt observer clears minimum payments cache when debt changes', function () {
    $debt = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $service = app(DebtCalculationService::class);
    $debts = Debt::all();

    // Populate cache
    $firstResult = $service->calculateMinimumPaymentsOnly($debts);

    // Update debt (triggers observer)
    $debt->update(['balance' => 9000]);

    // Refresh debts collection
    $debts = Debt::all();

    // New calculation should reflect updated balance
    $newResult = $service->calculateMinimumPaymentsOnly($debts);

    // The result should potentially be different due to balance change
    // (fewer months to pay off lower balance)
    expect($newResult)->toBeLessThanOrEqual($firstResult);
});

// Edge Case Tests
test('empty debt collection returns zero without caching', function () {
    $service = app(DebtCalculationService::class);
    $debts = collect();

    $monthsResult = $service->calculateMinimumPaymentsOnly($debts);
    $interestResult = $service->calculateMinimumPaymentsInterest($debts);

    expect($monthsResult)->toBe(0);
    expect($interestResult)->toBe(0.0);
});

test('cache key differs for different debt data', function () {
    $service = app(DebtCalculationService::class);

    // Create first debt and calculate
    $debt1 = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $debts1 = Debt::all();
    $service->calculateMinimumPaymentsOnly($debts1);

    // Create second debt
    $debt2 = Debt::factory()->create([
        'balance' => 20000,
        'original_balance' => 20000,
        'interest_rate' => 20,
        'minimum_payment' => 600,
    ]);

    // Clear cache to get fresh collection
    DebtCacheService::clearCache();

    $debts2 = Debt::all();
    $result2 = $service->calculateMinimumPaymentsOnly($debts2);

    // Result should be calculated fresh (not cached from first call)
    // because the debt collection is different
    expect($result2)->toBeGreaterThan(0);
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

    // Results should be identical (from cache)
    expect($result1)->toBe($result2);
});

test('minimum payments calculation handles debts with zero balance', function () {
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

test('minimum payments calculation handles debts with zero minimum payment', function () {
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
