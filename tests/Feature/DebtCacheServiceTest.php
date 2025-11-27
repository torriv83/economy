<?php

use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCacheService;
use Illuminate\Support\Facades\Cache;

describe('DebtCacheService', function () {
    beforeEach(function () {
        // Clear cache before each test
        Cache::flush();
    });

    describe('getAll', function () {
        it('returns all debts', function () {
            $debts = Debt::factory()->count(3)->create();

            $service = app(DebtCacheService::class);
            $result = $service->getAll();

            expect($result)->toHaveCount(3);
            expect($result->pluck('id')->toArray())->toBe($debts->pluck('id')->toArray());
        });

        it('caches the result', function () {
            Debt::factory()->count(2)->create();

            $service = app(DebtCacheService::class);

            // First call should populate cache
            $service->getAll();

            expect(Cache::has(DebtCacheService::CACHE_KEY_ALL))->toBeTrue();
        });

        it('returns cached data on subsequent calls', function () {
            Debt::factory()->count(2)->create();

            $service = app(DebtCacheService::class);

            // First call populates cache
            $result1 = $service->getAll();

            // Simulate database change without triggering observer (direct DB query)
            \Illuminate\Support\Facades\DB::table('debts')->insert([
                'name' => 'Direct Insert Debt',
                'balance' => 10000,
                'original_balance' => 10000,
                'interest_rate' => 5.0,
                'minimum_payment' => 500,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Second call should return cached data (still 2 debts, not 3)
            $result2 = $service->getAll();

            expect($result2)->toHaveCount(2);
            expect($result1->pluck('id')->toArray())->toBe($result2->pluck('id')->toArray());
        });
    });

    describe('getAllWithPayments', function () {
        it('returns all debts with payments loaded', function () {
            $debt = Debt::factory()->create();
            Payment::factory()->count(3)->for($debt)->create();

            $service = app(DebtCacheService::class);
            $result = $service->getAllWithPayments();

            expect($result)->toHaveCount(1);
            expect($result->first()->relationLoaded('payments'))->toBeTrue();
            expect($result->first()->payments)->toHaveCount(3);
        });

        it('caches the result with payments', function () {
            $debt = Debt::factory()->create();
            Payment::factory()->count(2)->for($debt)->create();

            $service = app(DebtCacheService::class);

            // First call should populate cache
            $service->getAllWithPayments();

            expect(Cache::has(DebtCacheService::CACHE_KEY_WITH_PAYMENTS))->toBeTrue();
        });
    });

    describe('clearCache', function () {
        it('clears all debt caches', function () {
            Debt::factory()->create();

            $service = app(DebtCacheService::class);

            // Populate both caches
            $service->getAll();
            $service->getAllWithPayments();

            expect(Cache::has(DebtCacheService::CACHE_KEY_ALL))->toBeTrue();
            expect(Cache::has(DebtCacheService::CACHE_KEY_WITH_PAYMENTS))->toBeTrue();

            // Clear caches
            DebtCacheService::clearCache();

            expect(Cache::has(DebtCacheService::CACHE_KEY_ALL))->toBeFalse();
            expect(Cache::has(DebtCacheService::CACHE_KEY_WITH_PAYMENTS))->toBeFalse();
        });
    });

    describe('hasCachedData', function () {
        it('returns false when no cache exists', function () {
            $service = app(DebtCacheService::class);

            expect($service->hasCachedData())->toBeFalse();
        });

        it('returns true when cache exists', function () {
            Debt::factory()->create();

            $service = app(DebtCacheService::class);
            $service->getAll();

            expect($service->hasCachedData())->toBeTrue();
        });
    });
});

describe('DebtObserver Cache Invalidation', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('clears cache when debt is created', function () {
        $service = app(DebtCacheService::class);

        // Create initial debt and populate cache
        $debt1 = Debt::factory()->create();
        $service->getAll();

        expect(Cache::has(DebtCacheService::CACHE_KEY_ALL))->toBeTrue();

        // Create another debt - should clear cache via observer
        Debt::factory()->create();

        expect(Cache::has(DebtCacheService::CACHE_KEY_ALL))->toBeFalse();
    });

    it('clears cache when debt is updated', function () {
        $service = app(DebtCacheService::class);

        $debt = Debt::factory()->create();
        $service->getAll();

        expect(Cache::has(DebtCacheService::CACHE_KEY_ALL))->toBeTrue();

        // Update debt - should clear cache via observer
        $debt->update(['balance' => 50000]);

        expect(Cache::has(DebtCacheService::CACHE_KEY_ALL))->toBeFalse();
    });

    it('clears cache when debt is deleted', function () {
        $service = app(DebtCacheService::class);

        $debt = Debt::factory()->create();
        $service->getAll();

        expect(Cache::has(DebtCacheService::CACHE_KEY_ALL))->toBeTrue();

        // Delete debt - should clear cache via observer
        $debt->delete();

        expect(Cache::has(DebtCacheService::CACHE_KEY_ALL))->toBeFalse();
    });
});

describe('PaymentObserver Cache Invalidation', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('clears cache when payment is created', function () {
        $service = app(DebtCacheService::class);

        $debt = Debt::factory()->create();
        $service->getAllWithPayments();

        expect(Cache::has(DebtCacheService::CACHE_KEY_WITH_PAYMENTS))->toBeTrue();

        // Create payment - should clear cache via observer
        Payment::factory()->for($debt)->create();

        expect(Cache::has(DebtCacheService::CACHE_KEY_WITH_PAYMENTS))->toBeFalse();
    });

    it('clears cache when payment is updated', function () {
        $service = app(DebtCacheService::class);

        $debt = Debt::factory()->create();
        $payment = Payment::factory()->for($debt)->create();

        // Clear cache that was invalidated by payment creation, then repopulate
        Cache::flush();
        $service->getAllWithPayments();

        expect(Cache::has(DebtCacheService::CACHE_KEY_WITH_PAYMENTS))->toBeTrue();

        // Update payment - should clear cache via observer
        $payment->update(['actual_amount' => 5000]);

        expect(Cache::has(DebtCacheService::CACHE_KEY_WITH_PAYMENTS))->toBeFalse();
    });

    it('clears cache when payment is deleted', function () {
        $service = app(DebtCacheService::class);

        $debt = Debt::factory()->create();
        $payment = Payment::factory()->for($debt)->create();

        // Clear cache that was invalidated by payment creation, then repopulate
        Cache::flush();
        $service->getAllWithPayments();

        expect(Cache::has(DebtCacheService::CACHE_KEY_WITH_PAYMENTS))->toBeTrue();

        // Delete payment - should clear cache via observer
        $payment->delete();

        expect(Cache::has(DebtCacheService::CACHE_KEY_WITH_PAYMENTS))->toBeFalse();
    });
});
