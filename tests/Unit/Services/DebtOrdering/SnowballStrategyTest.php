<?php

use App\Models\Debt;
use App\Services\DebtOrdering\SnowballStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->strategy = new SnowballStrategy;
});

describe('order method', function () {
    it('orders debts by lowest balance first', function () {
        $debt1 = Debt::factory()->create(['name' => 'Large', 'balance' => 10000]);
        $debt2 = Debt::factory()->create(['name' => 'Small', 'balance' => 1000]);
        $debt3 = Debt::factory()->create(['name' => 'Medium', 'balance' => 5000]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->pluck('name')->toArray())->toBe(['Small', 'Medium', 'Large']);
    });

    it('returns empty collection for empty input', function () {
        $result = $this->strategy->order(collect());

        expect($result)->toBeEmpty();
    });

    it('handles single debt correctly', function () {
        $debt = Debt::factory()->create(['name' => 'Only Debt', 'balance' => 5000]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered)->toHaveCount(1)
            ->and($ordered->first()->name)->toBe('Only Debt');
    });

    it('handles multiple debts with same balance', function () {
        $debt1 = Debt::factory()->create(['name' => 'First', 'balance' => 5000]);
        $debt2 = Debt::factory()->create(['name' => 'Second', 'balance' => 5000]);
        $debt3 = Debt::factory()->create(['name' => 'Third', 'balance' => 5000]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered)->toHaveCount(3)
            ->and($ordered->pluck('balance')->unique()->toArray())->toBe([5000.0]);
    });

    it('maintains stable ordering for equal balances', function () {
        $debt1 = Debt::factory()->create(['name' => 'First', 'balance' => 3000]);
        $debt2 = Debt::factory()->create(['name' => 'Second', 'balance' => 3000]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        // Both debts should be present
        expect($ordered)->toHaveCount(2)
            ->and($ordered->pluck('name')->toArray())->toContain('First')
            ->and($ordered->pluck('name')->toArray())->toContain('Second');
    });

    it('resets collection keys after ordering', function () {
        Debt::factory()->create(['balance' => 5000]);
        Debt::factory()->create(['balance' => 1000]);
        Debt::factory()->create(['balance' => 3000]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->keys()->toArray())->toBe([0, 1, 2]);
    });

    it('handles debts with zero balance', function () {
        $debt1 = Debt::factory()->create(['name' => 'Zero', 'balance' => 0]);
        $debt2 = Debt::factory()->create(['name' => 'Positive', 'balance' => 5000]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->first()->name)->toBe('Zero')
            ->and($ordered->last()->name)->toBe('Positive');
    });

    it('handles debts with very small balances', function () {
        $debt1 = Debt::factory()->create(['name' => 'Tiny', 'balance' => 0.01]);
        $debt2 = Debt::factory()->create(['name' => 'Small', 'balance' => 100]);
        $debt3 = Debt::factory()->create(['name' => 'Large', 'balance' => 10000]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->pluck('name')->toArray())->toBe(['Tiny', 'Small', 'Large']);
    });

    it('handles debts with very large balances', function () {
        $debt1 = Debt::factory()->create(['name' => 'Small', 'balance' => 1000]);
        $debt2 = Debt::factory()->create(['name' => 'Large', 'balance' => 999999999.99]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->first()->name)->toBe('Small')
            ->and($ordered->last()->name)->toBe('Large');
    });
});

describe('getKey method', function () {
    it('returns correct strategy key', function () {
        expect($this->strategy->getKey())->toBe('snowball');
    });
});

describe('getName method', function () {
    it('returns localized strategy name', function () {
        $name = $this->strategy->getName();

        expect($name)->toBeString()
            ->and($name)->not->toBeEmpty();
    });
});

describe('getDescription method', function () {
    it('returns localized strategy description', function () {
        $description = $this->strategy->getDescription();

        expect($description)->toBeString()
            ->and($description)->not->toBeEmpty();
    });
});

describe('collection type handling', function () {
    it('works with Eloquent collection', function () {
        Debt::factory()->count(3)->create();

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered)->toHaveCount(3);
    });

    it('works with base collection of Debt models', function () {
        $debts = collect([
            new Debt(['name' => 'A', 'balance' => 5000, 'interest_rate' => 10]),
            new Debt(['name' => 'B', 'balance' => 1000, 'interest_rate' => 15]),
        ]);

        $ordered = $this->strategy->order($debts);

        expect($ordered)->toHaveCount(2)
            ->and($ordered->first()->name)->toBe('B');
    });
});
