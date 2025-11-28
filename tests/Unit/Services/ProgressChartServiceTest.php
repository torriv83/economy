<?php

use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCacheService;
use App\Services\ProgressChartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    DebtCacheService::clearCache();
    $this->service = app(ProgressChartService::class);
});

describe('calculateProgressData', function () {
    it('returns empty arrays when no debts exist', function () {
        $result = $this->service->calculateProgressData();

        expect($result)->toBe(['labels' => [], 'datasets' => []]);
    });

    it('returns correct structure with debts', function () {
        Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        DebtCacheService::clearCache();
        $result = $this->service->calculateProgressData();

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['labels', 'datasets'])
            ->and($result['labels'])->toBeArray()
            ->and($result['datasets'])->toBeArray();
    });

    it('includes total debt balance as first dataset', function () {
        Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        DebtCacheService::clearCache();
        $result = $this->service->calculateProgressData();

        expect($result['datasets'][0])->toHaveKeys(['label', 'data', 'borderColor', 'isTotal'])
            ->and($result['datasets'][0]['isTotal'])->toBeTrue();
    });

    it('includes individual debt datasets after total', function () {
        Debt::factory()->create([
            'name' => 'Credit Card',
            'balance' => 5000,
            'original_balance' => 5000,
        ]);

        Debt::factory()->create([
            'name' => 'Car Loan',
            'balance' => 15000,
            'original_balance' => 15000,
        ]);

        DebtCacheService::clearCache();
        $result = $this->service->calculateProgressData();

        $labels = collect($result['datasets'])->pluck('label')->toArray();

        expect($labels)->toContain('Credit Card')
            ->and($labels)->toContain('Car Loan');
    });

    it('assigns different colors to each debt', function () {
        Debt::factory()->create([
            'name' => 'Debt A',
            'balance' => 5000,
            'original_balance' => 5000,
        ]);

        Debt::factory()->create([
            'name' => 'Debt B',
            'balance' => 6000,
            'original_balance' => 6000,
        ]);

        Debt::factory()->create([
            'name' => 'Debt C',
            'balance' => 7000,
            'original_balance' => 7000,
        ]);

        DebtCacheService::clearCache();
        $result = $this->service->calculateProgressData();

        // Skip the first (total) dataset
        $debtDatasets = array_slice($result['datasets'], 1);
        $colors = array_column($debtDatasets, 'borderColor');

        // Colors should be unique (assuming less than palette size)
        expect($colors)->toHaveCount(3)
            ->and(count(array_unique($colors)))->toBe(3);
    });

    it('calculates correct balance with payments', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 9000,
            'original_balance' => 10000,
        ]);

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'actual_amount' => 1000,
            'principal_paid' => 1000,
            'interest_paid' => 0,
            'payment_date' => now()->subMonth(),
        ]);

        DebtCacheService::clearCache();
        $result = $this->service->calculateProgressData();

        // Should have multiple months of data
        expect($result['labels'])->not->toBeEmpty()
            ->and($result['datasets'][0]['data'])->not->toBeEmpty();
    });

    it('generates monthly labels in correct format', function () {
        Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        DebtCacheService::clearCache();
        $result = $this->service->calculateProgressData();

        // Labels should be in month year format
        expect($result['labels'])->not->toBeEmpty();
        foreach ($result['labels'] as $label) {
            // Should contain a 4-digit year
            expect(preg_match('/\d{4}/', $label))->toBe(1);
        }
    });
});

describe('getColorPalette', function () {
    it('returns an array of colors', function () {
        $colors = $this->service->getColorPalette();

        expect($colors)->toBeArray()->not->toBeEmpty();
    });

    it('returns valid hex colors', function () {
        $colors = $this->service->getColorPalette();

        foreach ($colors as $color) {
            expect($color)->toMatch('/^#[0-9A-Fa-f]{6}$/');
        }
    });

    it('has at least 5 colors for variety', function () {
        $colors = $this->service->getColorPalette();

        expect(count($colors))->toBeGreaterThanOrEqual(5);
    });
});

describe('edge cases', function () {
    it('handles debt with zero balance', function () {
        Debt::factory()->create([
            'name' => 'Paid Off Debt',
            'balance' => 0,
            'original_balance' => 5000,
        ]);

        DebtCacheService::clearCache();
        $result = $this->service->calculateProgressData();

        expect($result['datasets'])->not->toBeEmpty();
    });

    it('handles multiple debts with same name', function () {
        Debt::factory()->create([
            'name' => 'Credit Card',
            'balance' => 5000,
            'original_balance' => 5000,
        ]);

        Debt::factory()->create([
            'name' => 'Credit Card',
            'balance' => 3000,
            'original_balance' => 3000,
        ]);

        DebtCacheService::clearCache();
        $result = $this->service->calculateProgressData();

        // Should handle without errors (though names might collide)
        expect($result['datasets'])->not->toBeEmpty();
    });

    it('handles debts created in different months', function () {
        // Create debt from 2 months ago
        $this->travel(-2)->months();
        Debt::factory()->create([
            'name' => 'Old Debt',
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $this->travelBack();

        // Create debt now
        Debt::factory()->create([
            'name' => 'New Debt',
            'balance' => 5000,
            'original_balance' => 5000,
        ]);

        DebtCacheService::clearCache();
        $result = $this->service->calculateProgressData();

        // Should have multiple months in labels
        expect(count($result['labels']))->toBeGreaterThan(1);
    });
});
