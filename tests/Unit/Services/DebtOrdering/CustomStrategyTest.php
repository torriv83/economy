<?php

use App\Models\Debt;
use App\Services\DebtOrdering\CustomStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->strategy = new CustomStrategy;
});

describe('order method', function () {
    it('orders debts by custom_priority_order ascending', function () {
        $debt1 = Debt::factory()->create(['name' => 'Third', 'custom_priority_order' => 3]);
        $debt2 = Debt::factory()->create(['name' => 'First', 'custom_priority_order' => 1]);
        $debt3 = Debt::factory()->create(['name' => 'Second', 'custom_priority_order' => 2]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->pluck('name')->toArray())->toBe(['First', 'Second', 'Third']);
    });

    it('returns empty collection for empty input', function () {
        $result = $this->strategy->order(collect());

        expect($result)->toBeEmpty();
    });

    it('handles single debt correctly', function () {
        $debt = Debt::factory()->create(['name' => 'Only Debt', 'custom_priority_order' => 1]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered)->toHaveCount(1)
            ->and($ordered->first()->name)->toBe('Only Debt');
    });

    it('handles null custom_priority_order values', function () {
        $debt1 = Debt::factory()->create(['name' => 'With Priority', 'custom_priority_order' => 1]);
        $debt2 = Debt::factory()->create(['name' => 'No Priority', 'custom_priority_order' => null]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        // null values should sort to the end (or beginning depending on PHP's sortBy behavior)
        // In PHP, null < any integer, so null comes first with sortBy
        expect($ordered)->toHaveCount(2);
    });

    it('handles all null custom_priority_order values', function () {
        $debt1 = Debt::factory()->create(['name' => 'First', 'custom_priority_order' => null]);
        $debt2 = Debt::factory()->create(['name' => 'Second', 'custom_priority_order' => null]);
        $debt3 = Debt::factory()->create(['name' => 'Third', 'custom_priority_order' => null]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        // All debts should be present
        expect($ordered)->toHaveCount(3);
    });

    it('handles multiple debts with same priority', function () {
        $debt1 = Debt::factory()->create(['name' => 'First', 'custom_priority_order' => 1]);
        $debt2 = Debt::factory()->create(['name' => 'Second', 'custom_priority_order' => 1]);
        $debt3 = Debt::factory()->create(['name' => 'Third', 'custom_priority_order' => 1]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered)->toHaveCount(3)
            ->and($ordered->pluck('custom_priority_order')->unique()->toArray())->toBe([1]);
    });

    it('resets collection keys after ordering', function () {
        Debt::factory()->create(['custom_priority_order' => 3]);
        Debt::factory()->create(['custom_priority_order' => 1]);
        Debt::factory()->create(['custom_priority_order' => 2]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->keys()->toArray())->toBe([0, 1, 2]);
    });

    it('handles zero as custom_priority_order', function () {
        $debt1 = Debt::factory()->create(['name' => 'Zero Priority', 'custom_priority_order' => 0]);
        $debt2 = Debt::factory()->create(['name' => 'First Priority', 'custom_priority_order' => 1]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->first()->name)->toBe('Zero Priority');
    });

    it('handles negative custom_priority_order', function () {
        $debt1 = Debt::factory()->create(['name' => 'Negative', 'custom_priority_order' => -1]);
        $debt2 = Debt::factory()->create(['name' => 'Positive', 'custom_priority_order' => 1]);
        $debt3 = Debt::factory()->create(['name' => 'Zero', 'custom_priority_order' => 0]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->pluck('name')->toArray())->toBe(['Negative', 'Zero', 'Positive']);
    });

    it('handles large priority numbers', function () {
        $debt1 = Debt::factory()->create(['name' => 'Low', 'custom_priority_order' => 1]);
        $debt2 = Debt::factory()->create(['name' => 'High', 'custom_priority_order' => 999999]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->first()->name)->toBe('Low')
            ->and($ordered->last()->name)->toBe('High');
    });

    it('orders independently of balance', function () {
        $debt1 = Debt::factory()->create([
            'name' => 'Small Balance First',
            'balance' => 100,
            'custom_priority_order' => 1,
        ]);
        $debt2 = Debt::factory()->create([
            'name' => 'Large Balance Second',
            'balance' => 100000,
            'custom_priority_order' => 2,
        ]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->first()->name)->toBe('Small Balance First');
    });

    it('orders independently of interest rate', function () {
        $debt1 = Debt::factory()->create([
            'name' => 'Low Rate First',
            'interest_rate' => 5,
            'custom_priority_order' => 1,
        ]);
        $debt2 = Debt::factory()->create([
            'name' => 'High Rate Second',
            'interest_rate' => 25,
            'custom_priority_order' => 2,
        ]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->first()->name)->toBe('Low Rate First');
    });

    it('places null priorities before numbered priorities due to PHP sort behavior', function () {
        $debt1 = Debt::factory()->create(['name' => 'Priority 5', 'custom_priority_order' => 5]);
        $debt2 = Debt::factory()->create(['name' => 'No Priority', 'custom_priority_order' => null]);
        $debt3 = Debt::factory()->create(['name' => 'Priority 1', 'custom_priority_order' => 1]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        // In PHP sortBy, null < integers, so null comes first
        expect($ordered->first()->name)->toBe('No Priority');
    });
});

describe('getKey method', function () {
    it('returns correct strategy key', function () {
        expect($this->strategy->getKey())->toBe('custom');
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
        Debt::factory()->count(3)->sequence(
            ['custom_priority_order' => 1],
            ['custom_priority_order' => 2],
            ['custom_priority_order' => 3],
        )->create();

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered)->toHaveCount(3);
    });

    it('works with base collection of Debt models', function () {
        $debts = collect([
            new Debt(['name' => 'A', 'balance' => 5000, 'interest_rate' => 10, 'custom_priority_order' => 2]),
            new Debt(['name' => 'B', 'balance' => 1000, 'interest_rate' => 15, 'custom_priority_order' => 1]),
        ]);

        $ordered = $this->strategy->order($debts);

        expect($ordered)->toHaveCount(2)
            ->and($ordered->first()->name)->toBe('B'); // Lower custom_priority_order
    });
});

describe('use case scenarios', function () {
    it('allows user to prioritize emotional debt over financial optimization', function () {
        // User wants to pay off family loan first even though it has low interest
        $familyLoan = Debt::factory()->create([
            'name' => 'Family Loan',
            'balance' => 5000,
            'interest_rate' => 0,
            'custom_priority_order' => 1, // User priority: first
        ]);
        $creditCard = Debt::factory()->create([
            'name' => 'Credit Card',
            'balance' => 3000,
            'interest_rate' => 24.99,
            'custom_priority_order' => 2, // User priority: second
        ]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        // Family loan comes first despite 0% interest
        expect($ordered->first()->name)->toBe('Family Loan');
    });

    it('respects user-defined order for debts with similar characteristics', function () {
        $debt1 = Debt::factory()->create([
            'name' => 'Store Card A',
            'balance' => 1000,
            'interest_rate' => 20,
            'custom_priority_order' => 3,
        ]);
        $debt2 = Debt::factory()->create([
            'name' => 'Store Card B',
            'balance' => 1000,
            'interest_rate' => 20,
            'custom_priority_order' => 1,
        ]);
        $debt3 = Debt::factory()->create([
            'name' => 'Store Card C',
            'balance' => 1000,
            'interest_rate' => 20,
            'custom_priority_order' => 2,
        ]);

        $debts = Debt::all();
        $ordered = $this->strategy->order($debts);

        expect($ordered->pluck('name')->toArray())->toBe(['Store Card B', 'Store Card C', 'Store Card A']);
    });
});
