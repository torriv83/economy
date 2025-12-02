<?php

use App\Models\Debt;
use App\Models\Payment;
use App\Services\ProgressCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Flush all cache to ensure complete isolation between parallel test runs
    Cache::flush();

    // Clear any resolved instances to ensure fresh state
    Cache::clearResolvedInstances();

    // Create a fresh service instance
    $this->service = app(ProgressCacheService::class);
});

afterEach(function () {
    // Clean up after each test to prevent interference with parallel tests
    Cache::flush();
    Mockery::close();
});

describe('getCacheKey', function () {
    it('returns a string cache key', function () {
        $key = $this->service->getCacheKey();

        expect($key)->toBeString()
            ->and($key)->toStartWith('progress_data_');
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

describe('clear', function () {
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
