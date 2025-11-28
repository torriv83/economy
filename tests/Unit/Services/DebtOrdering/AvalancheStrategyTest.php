<?php

use App\Models\Debt;
use App\Services\DebtOrdering\AvalancheStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->strategy = new AvalancheStrategy;
});

describe('order method', function () {
    it('orders debts by highest interest rate first', function () {
        $debt1 = Debt::factory()->create(['name' => 'Low Rate', 'interest_rate' => 5]);
        $debt2 = Debt::factory()->create(['name' => 'High Rate', 'interest_rate' => 20]);
        $debt3 = Debt::factory()->create(['name' => 'Medium Rate', 'interest_rate' => 12]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->pluck('name')->toArray())->toBe(['High Rate', 'Medium Rate', 'Low Rate']);
    });

    it('returns empty collection for empty input', function () {
        $result = $this->strategy->order(collect());

        expect($result)->toBeEmpty();
    });

    it('handles single debt correctly', function () {
        $debt = Debt::factory()->create(['name' => 'Only Debt', 'interest_rate' => 15]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered)->toHaveCount(1)
            ->and($ordered->first()->name)->toBe('Only Debt');
    });

    it('handles multiple debts with same interest rate', function () {
        $debt1 = Debt::factory()->create(['name' => 'First', 'interest_rate' => 10]);
        $debt2 = Debt::factory()->create(['name' => 'Second', 'interest_rate' => 10]);
        $debt3 = Debt::factory()->create(['name' => 'Third', 'interest_rate' => 10]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered)->toHaveCount(3)
            ->and($ordered->pluck('interest_rate')->unique()->toArray())->toBe([10.0]);
    });

    it('maintains stable ordering for equal interest rates', function () {
        $debt1 = Debt::factory()->create(['name' => 'First', 'interest_rate' => 15]);
        $debt2 = Debt::factory()->create(['name' => 'Second', 'interest_rate' => 15]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        // Both debts should be present
        expect($ordered)->toHaveCount(2)
            ->and($ordered->pluck('name')->toArray())->toContain('First')
            ->and($ordered->pluck('name')->toArray())->toContain('Second');
    });

    it('resets collection keys after ordering', function () {
        Debt::factory()->create(['interest_rate' => 5]);
        Debt::factory()->create(['interest_rate' => 20]);
        Debt::factory()->create(['interest_rate' => 12]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->keys()->toArray())->toBe([0, 1, 2]);
    });

    it('handles debts with zero interest rate', function () {
        $debt1 = Debt::factory()->create(['name' => 'Zero Rate', 'interest_rate' => 0]);
        $debt2 = Debt::factory()->create(['name' => 'Positive Rate', 'interest_rate' => 10]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->first()->name)->toBe('Positive Rate')
            ->and($ordered->last()->name)->toBe('Zero Rate');
    });

    it('handles debts with very low interest rates', function () {
        $debt1 = Debt::factory()->create(['name' => 'Tiny', 'interest_rate' => 0.01]);
        $debt2 = Debt::factory()->create(['name' => 'Small', 'interest_rate' => 1]);
        $debt3 = Debt::factory()->create(['name' => 'Large', 'interest_rate' => 25]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->pluck('name')->toArray())->toBe(['Large', 'Small', 'Tiny']);
    });

    it('handles debts with high interest rates', function () {
        $debt1 = Debt::factory()->create(['name' => 'Normal', 'interest_rate' => 15]);
        $debt2 = Debt::factory()->create(['name' => 'Very High', 'interest_rate' => 99.99]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->first()->name)->toBe('Very High')
            ->and($ordered->last()->name)->toBe('Normal');
    });

    it('prioritizes high interest over low balance', function () {
        $debt1 = Debt::factory()->create(['name' => 'High Rate Small Balance', 'balance' => 500, 'interest_rate' => 25]);
        $debt2 = Debt::factory()->create(['name' => 'Low Rate Large Balance', 'balance' => 50000, 'interest_rate' => 5]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        // Avalanche should prioritize high interest rate regardless of balance
        expect($ordered->first()->name)->toBe('High Rate Small Balance');
    });
});

describe('getKey method', function () {
    it('returns correct strategy key', function () {
        expect($this->strategy->getKey())->toBe('avalanche');
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
            new Debt(['name' => 'B', 'balance' => 1000, 'interest_rate' => 20]),
        ]);

        $ordered = $this->strategy->order($debts);

        expect($ordered)->toHaveCount(2)
            ->and($ordered->first()->name)->toBe('B'); // Higher interest rate
    });
});

describe('financial correctness', function () {
    it('orders optimally for maximum interest savings', function () {
        // Real-world scenario: High rate debt should be paid first for savings
        $creditCard = Debt::factory()->create([
            'name' => 'Credit Card',
            'balance' => 5000,
            'interest_rate' => 24.99,
        ]);
        $carLoan = Debt::factory()->create([
            'name' => 'Car Loan',
            'balance' => 15000,
            'interest_rate' => 6.5,
        ]);
        $studentLoan = Debt::factory()->create([
            'name' => 'Student Loan',
            'balance' => 30000,
            'interest_rate' => 4.5,
        ]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->pluck('name')->toArray())->toBe(['Credit Card', 'Car Loan', 'Student Loan']);
    });
});
