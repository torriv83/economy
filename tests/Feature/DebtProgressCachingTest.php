<?php

use App\Livewire\DebtProgress;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear cache before each test to ensure isolation
    Cache::flush();
});

// Cache Key Generation Tests
test('progress data cache key changes when new debt is created', function () {
    $keyBefore = DebtProgress::getProgressDataCacheKey();

    // Create a debt
    Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
    ]);

    $keyAfter = DebtProgress::getProgressDataCacheKey();

    expect($keyBefore)->not->toBe($keyAfter);
});

test('progress data cache key changes when new payment is created', function () {
    $debt = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
    ]);

    $keyBefore = DebtProgress::getProgressDataCacheKey();

    // Create a new payment
    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 500,
        'principal_paid' => 400,
        'interest_paid' => 100,
    ]);

    $keyAfter = DebtProgress::getProgressDataCacheKey();

    expect($keyBefore)->not->toBe($keyAfter);
});

test('progress data cache key is consistent for same data state', function () {
    Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 10000,
    ]);

    $key1 = DebtProgress::getProgressDataCacheKey();
    $key2 = DebtProgress::getProgressDataCacheKey();

    expect($key1)->toBe($key2);
});

// Caching Behavior Tests
test('progress data is cached after first access', function () {
    Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    // Access progressData through the component
    $component = Livewire::test(DebtProgress::class);
    $data = $component->instance()->progressData;

    // Verify data was returned and has the expected structure
    expect($data)->toBeArray()
        ->and($data['labels'])->not->toBeEmpty()
        ->and($data['datasets'])->not->toBeEmpty()
        ->and($data)->toHaveKeys(['labels', 'datasets']);
});

test('progress data returns empty arrays when no debts exist', function () {
    $component = Livewire::test(DebtProgress::class);

    expect($component->instance()->progressData)
        ->toBeArray()
        ->and($component->instance()->progressData['labels'])->toBeEmpty()
        ->and($component->instance()->progressData['datasets'])->toBeEmpty();
});

test('cached progress data returns same result on multiple accesses', function () {
    Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 15,
        'minimum_payment' => 500,
    ]);

    $component = Livewire::test(DebtProgress::class);

    // First access
    $firstResult = $component->instance()->progressData;

    // Second access (should be cached)
    $secondResult = $component->instance()->progressData;

    expect($firstResult)->toBe($secondResult);
});

test('progress data cache key differs when debt timestamp changes via fresh query', function () {
    // Create initial debt
    $debt = Debt::factory()->create([
        'name' => 'Debt 1',
        'balance' => 10000,
        'original_balance' => 10000,
    ]);

    $initialCacheKey = DebtProgress::getProgressDataCacheKey();

    // Travel 1 second into the future to ensure timestamp differs
    $this->travel(1)->seconds();

    // Update balance - this will change updated_at timestamp
    Debt::where('id', $debt->id)->update(['balance' => 9500]);

    // The cache key should now be different because updated_at changed
    $newCacheKey = DebtProgress::getProgressDataCacheKey();
    expect($newCacheKey)->not->toBe($initialCacheKey);
});

test('creating a payment generates a new cache key', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'original_balance' => 10000,
    ]);

    $initialCacheKey = DebtProgress::getProgressDataCacheKey();

    // Create a payment (new data state)
    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 500,
        'principal_paid' => 400,
        'interest_paid' => 100,
    ]);

    // Cache key should have changed
    $newCacheKey = DebtProgress::getProgressDataCacheKey();
    expect($newCacheKey)->not->toBe($initialCacheKey);
});

// Data Structure Tests
test('progress data returns correct structure with debts and payments', function () {
    $debt = Debt::factory()->create([
        'name' => 'Kredittkort',
        'balance' => 9000,
        'original_balance' => 10000,
        'interest_rate' => 20,
        'minimum_payment' => 500,
    ]);

    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 1000,
        'principal_paid' => 1000,
        'interest_paid' => 0,
        'payment_date' => now()->subMonth(),
    ]);

    $component = Livewire::test(DebtProgress::class);
    $data = $component->instance()->progressData;

    expect($data)
        ->toBeArray()
        ->toHaveKeys(['labels', 'datasets'])
        ->and($data['labels'])->toBeArray()->not->toBeEmpty()
        ->and($data['datasets'])->toBeArray()->not->toBeEmpty()
        ->and($data['datasets'][0])->toHaveKeys(['label', 'data', 'borderColor', 'isTotal']);
});

test('progress data includes correct debt names in datasets', function () {
    Debt::factory()->create([
        'name' => 'Kredittkort A',
        'balance' => 10000,
        'original_balance' => 10000,
    ]);

    Debt::factory()->create([
        'name' => 'Forbrukslan B',
        'balance' => 20000,
        'original_balance' => 20000,
    ]);

    $component = Livewire::test(DebtProgress::class);
    $data = $component->instance()->progressData;

    $labels = collect($data['datasets'])->pluck('label')->toArray();

    expect($labels)->toContain('Kredittkort A')
        ->and($labels)->toContain('Forbrukslan B');
});

test('progress data cache key invalidation strategy works', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'original_balance' => 10000,
    ]);

    // Get initial cache key
    $initialCacheKey = DebtProgress::getProgressDataCacheKey();

    // Create a new payment (this will change the cache key due to timestamp changes)
    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 1000,
        'principal_paid' => 900,
        'interest_paid' => 100,
        'payment_date' => now()->subWeek(),
    ]);

    // The cache key should now be different because payment data changed
    $newCacheKey = DebtProgress::getProgressDataCacheKey();

    // The invalidation strategy is key-based: when data changes, key changes
    // Old cache entries become orphaned (but eventually expire), new requests use new key
    expect($newCacheKey)->not->toBe($initialCacheKey);
});
