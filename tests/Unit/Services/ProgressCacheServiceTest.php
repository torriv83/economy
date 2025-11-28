<?php

use App\Models\Debt;
use App\Models\Payment;
use App\Services\ProgressCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->service = app(ProgressCacheService::class);
});

describe('getCacheKey', function () {
    it('returns a string cache key', function () {
        $key = $this->service->getCacheKey();

        expect($key)->toBeString()
            ->and($key)->toStartWith('progress_data:');
    });

    it('generates same key for same data state', function () {
        Debt::factory()->create();

        $key1 = $this->service->getCacheKey();
        $key2 = $this->service->getCacheKey();

        expect($key1)->toBe($key2);
    });

    it('generates different key when debt is created', function () {
        $keyBefore = $this->service->getCacheKey();

        Debt::factory()->create();

        $keyAfter = $this->service->getCacheKey();

        expect($keyBefore)->not->toBe($keyAfter);
    });

    it('generates different key when payment is created', function () {
        $debt = Debt::factory()->create();

        $keyBefore = $this->service->getCacheKey();

        Payment::factory()->create(['debt_id' => $debt->id]);

        $keyAfter = $this->service->getCacheKey();

        expect($keyBefore)->not->toBe($keyAfter);
    });

    it('generates different key when debt is updated', function () {
        $debt = Debt::factory()->create(['balance' => 10000]);

        $keyBefore = $this->service->getCacheKey();

        $this->travel(1)->seconds();
        $debt->update(['balance' => 9500]);

        $keyAfter = $this->service->getCacheKey();

        expect($keyBefore)->not->toBe($keyAfter);
    });
});

describe('remember', function () {
    it('caches the callback result', function () {
        Debt::factory()->create();
        $callCount = 0;

        $callback = function () use (&$callCount) {
            $callCount++;

            return ['data' => 'test'];
        };

        // First call - executes callback
        $result1 = $this->service->remember($callback);

        // Second call - should use cache
        $result2 = $this->service->remember($callback);

        expect($callCount)->toBe(1)
            ->and($result1)->toBe($result2)
            ->and($result1)->toBe(['data' => 'test']);
    });

    it('stores data in cache', function () {
        Debt::factory()->create();
        $cacheKey = $this->service->getCacheKey();

        $this->service->remember(fn () => ['test' => 'value']);

        expect(Cache::has($cacheKey))->toBeTrue();
    });

    it('returns callback result directly', function () {
        Debt::factory()->create();

        $result = $this->service->remember(fn () => [
            'labels' => ['Jan', 'Feb'],
            'datasets' => [['label' => 'Test']],
        ]);

        expect($result)->toBeArray()
            ->and($result['labels'])->toBe(['Jan', 'Feb'])
            ->and($result['datasets'])->toHaveCount(1);
    });
});

describe('has', function () {
    it('returns false when cache is empty', function () {
        Debt::factory()->create();

        expect($this->service->has())->toBeFalse();
    });

    it('returns true after remember is called', function () {
        Debt::factory()->create();

        $this->service->remember(fn () => ['data' => 'test']);

        expect($this->service->has())->toBeTrue();
    });
});

describe('clear', function () {
    it('clears the cache using getCacheKey method', function () {
        Debt::factory()->create();

        // Use remember to populate cache
        $this->service->remember(fn () => ['data' => 'test']);
        $cacheKey = $this->service->getCacheKey();

        expect(Cache::has($cacheKey))->toBeTrue();

        // Clear using the same key that getCacheKey returns
        Cache::forget($this->service->getCacheKey());

        expect(Cache::has($cacheKey))->toBeFalse();
    });

    it('generates correct cache key for clearing', function () {
        Debt::factory()->create();

        // Verify that repeated calls to getCacheKey return the same key
        $key1 = $this->service->getCacheKey();
        $key2 = $this->service->getCacheKey();

        expect($key1)->toBe($key2);
    });
});

describe('static helpers', function () {
    it('getProgressDataCacheKey static method returns same as instance method', function () {
        Debt::factory()->create();

        $instanceKey = $this->service->getCacheKey();
        $staticKey = ProgressCacheService::getProgressDataCacheKey();

        expect($instanceKey)->toBe($staticKey);
    });
});
