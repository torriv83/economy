<?php

use App\Cache\DebtCacheVersion;
use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCacheService;
use App\Services\DebtCalculationService;
use App\Services\ProgressCacheService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

/**
 * @param  array<int, mixed>  $arguments
 */
function invokeProtected(object $target, string $method, array $arguments): mixed
{
    $reflection = new ReflectionMethod($target, $method);

    return $reflection->invoke($target, ...$arguments);
}

describe('deferred flush', function () {
    it('does not flush while a transaction is still open', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $service = app(DebtCacheService::class);
        $service->getAll();

        $versionBefore = DebtCacheVersion::current();
        $versionInside = null;
        $balanceInside = null;

        DB::transaction(function () use ($debt, $service, &$versionInside, &$balanceInside) {
            $debt->update(['balance' => 5000]);

            $versionInside = DebtCacheVersion::current();
            $balanceInside = $service->getAll()->first()->balance;
        });

        expect($versionInside)->toBe($versionBefore)
            ->and($balanceInside)->toBe(10000.0);
    });

    it('flushes once the transaction commits', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $service = app(DebtCacheService::class);
        $service->getAll();

        $versionBefore = DebtCacheVersion::current();

        DB::transaction(function () use ($debt) {
            $debt->update(['balance' => 5000]);
        });

        expect(DebtCacheVersion::current())->toBeGreaterThan($versionBefore)
            ->and($service->getAll()->first()->balance)->toBe(5000.0);
    });

    it('flushes immediately when no transaction is active', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $versionBefore = DebtCacheVersion::current();

        $debt->update(['balance' => 5000]);

        expect(DebtCacheVersion::current())->toBe($versionBefore + 1);
    });

    it('flushes only once for many model events inside one transaction', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $versionBefore = DebtCacheVersion::current();

        DB::transaction(function () use ($debt) {
            $debt->update(['balance' => 9000]);
            $debt->update(['balance' => 8000]);
            Payment::factory()->create(['debt_id' => $debt->id]);
        });

        expect(DebtCacheVersion::current())->toBe($versionBefore + 1);
    });

    it('does not leave the pending guard stuck after a rollback', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        try {
            DB::transaction(function () use ($debt) {
                $debt->update(['balance' => 7000]);

                throw new RuntimeException('rull tilbake');
            });
        } catch (RuntimeException) {
            // Expected: the transaction is rolled back
        }

        $versionBefore = DebtCacheVersion::current();

        $debt->update(['balance' => 6000]);

        expect(DebtCacheVersion::current())->toBe($versionBefore + 1);
    });
});

describe('version based invalidation', function () {
    it('seeds the version from the current timestamp instead of one', function () {
        Cache::flush();

        expect(DebtCacheVersion::current())->toBe(now()->timestamp);
    });

    it('stores the version without a TTL', function () {
        Cache::flush();

        $version = DebtCacheVersion::current();

        $this->travel(3)->days();

        expect(DebtCacheVersion::current())->toBe($version);
    });

    it('stops serving debt collections cached under an older version', function () {
        Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $service = app(DebtCacheService::class);

        expect($service->getAll())->toHaveCount(1);

        // Bypass the model events so only the explicit bump invalidates
        Debt::withoutEvents(fn () => Debt::factory()->create([
            'balance' => 5000,
            'original_balance' => 5000,
        ]));

        expect($service->getAll())->toHaveCount(1);

        DebtCacheVersion::bump();

        expect($service->getAll())->toHaveCount(2);
    });

    it('stops serving payment schedules cached under an older version', function () {
        Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $service = app(DebtCalculationService::class);
        $debts = Debt::all();

        $service->generatePaymentSchedule($debts, 1000, 'avalanche');

        $keyBefore = invokeProtected($service, 'getPaymentScheduleCacheKey', [$debts, 1000.0, 'avalanche']).':0';
        expect(Cache::has($keyBefore))->toBeTrue();

        DebtCacheVersion::bump();

        // The bump makes the entry unreachable: lookups now use a new key
        $keyAfter = invokeProtected($service, 'getPaymentScheduleCacheKey', [$debts, 1000.0, 'avalanche']).':0';

        expect($keyAfter)->not->toBe($keyBefore)
            ->and(Cache::has($keyAfter))->toBeFalse();
    });

    it('stops serving progress data cached under an older version', function () {
        Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $service = app(ProgressCacheService::class);
        $service->remember(fn () => ['labels' => ['Jan'], 'datasets' => []]);

        expect($service->has())->toBeTrue();

        $service->clear();

        expect($service->has())->toBeFalse();
    });

    it('invalidates every layer from a single clearCache call', function () {
        Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $cacheService = app(DebtCacheService::class);
        $progressService = app(ProgressCacheService::class);

        $cacheService->getAll();
        $progressService->remember(fn () => ['labels' => ['Jan'], 'datasets' => []]);

        expect($cacheService->hasCachedData())->toBeTrue()
            ->and($progressService->has())->toBeTrue();

        DebtCacheService::clearCache();

        expect($cacheService->hasCachedData())->toBeFalse()
            ->and($progressService->has())->toBeFalse();
    });

    it('embeds the version in every debt related cache key', function () {
        Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'minimum_payment' => 500,
        ]);

        $calculationService = app(DebtCalculationService::class);
        $debts = Debt::all();
        $prefix = 'v'.DebtCacheVersion::current().':';

        expect(invokeProtected($calculationService, 'getPaymentScheduleCacheKey', [$debts, 1000.0, 'avalanche']))->toStartWith($prefix)
            ->and(invokeProtected($calculationService, 'getStrategyComparisonCacheKey', [$debts, 1000.0]))->toStartWith($prefix)
            ->and(invokeProtected($calculationService, 'getMinimumPaymentsCacheKey', [$debts, 'months']))->toStartWith($prefix)
            ->and(app(ProgressCacheService::class)->getCacheKey())->toStartWith($prefix);
    });
});

describe('date sensitive cache keys', function () {
    afterEach(function () {
        Carbon::setTestNow();
    });

    it('changes the payment schedule cache key across a day boundary', function () {
        Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'minimum_payment' => 500,
        ]);

        $service = app(DebtCalculationService::class);
        $debts = Debt::all();

        Carbon::setTestNow('2026-07-31 12:00:00');
        $keyBefore = invokeProtected($service, 'getPaymentScheduleCacheKey', [$debts, 1000.0, 'avalanche']);

        Carbon::setTestNow('2026-08-01 09:00:00');
        $keyAfter = invokeProtected($service, 'getPaymentScheduleCacheKey', [$debts, 1000.0, 'avalanche']);

        expect($keyAfter)->not->toBe($keyBefore);
    });

    it('changes the strategy comparison cache key across a day boundary', function () {
        Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'minimum_payment' => 500,
        ]);

        $service = app(DebtCalculationService::class);
        $debts = Debt::all();

        Carbon::setTestNow('2026-07-31 12:00:00');
        $keyBefore = invokeProtected($service, 'getStrategyComparisonCacheKey', [$debts, 1000.0]);

        Carbon::setTestNow('2026-08-01 09:00:00');
        $keyAfter = invokeProtected($service, 'getStrategyComparisonCacheKey', [$debts, 1000.0]);

        expect($keyAfter)->not->toBe($keyBefore);
    });

    it('keeps the minimum payments cache key stable across a day boundary', function () {
        Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'minimum_payment' => 500,
        ]);

        $service = app(DebtCalculationService::class);
        $debts = Debt::all();

        Carbon::setTestNow('2026-07-31 12:00:00');
        $keyBefore = invokeProtected($service, 'getMinimumPaymentsCacheKey', [$debts, 'months']);

        Carbon::setTestNow('2026-08-01 09:00:00');
        $keyAfter = invokeProtected($service, 'getMinimumPaymentsCacheKey', [$debts, 'months']);

        expect($keyAfter)->toBe($keyBefore);
    });

    it('does not serve yesterdays payment schedule today', function () {
        Carbon::setTestNow('2026-07-31 12:00:00');

        Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $service = app(DebtCalculationService::class);
        $debts = Debt::all();

        $yesterday = $service->generatePaymentSchedule($debts, 1000, 'avalanche');

        // Still inside the 24 hour TTL, so only the date in the key can force a recalculation
        Carbon::setTestNow('2026-08-01 09:00:00');
        $today = $service->generatePaymentSchedule($debts, 1000, 'avalanche');

        expect($yesterday['schedule'][0]['date'])->toBe('2026-07-15')
            ->and($today['schedule'][0]['date'])->toBe('2026-08-15');
    });
});
