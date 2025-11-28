<?php

use App\Contracts\DebtOrderingStrategy;
use App\Models\Debt;
use App\Services\DebtOrdering\AvalancheStrategy;
use App\Services\DebtOrdering\CustomStrategy;
use App\Services\DebtOrdering\SnowballStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

describe('DebtOrderingStrategy interface contract', function () {
    it('all strategies implement the interface', function () {
        $strategies = [
            new SnowballStrategy,
            new AvalancheStrategy,
            new CustomStrategy,
        ];

        foreach ($strategies as $strategy) {
            expect($strategy)->toBeInstanceOf(DebtOrderingStrategy::class);
        }
    });

    it('all strategies have unique keys', function () {
        $strategies = [
            new SnowballStrategy,
            new AvalancheStrategy,
            new CustomStrategy,
        ];

        $keys = array_map(fn ($s) => $s->getKey(), $strategies);

        expect($keys)->toHaveCount(count(array_unique($keys)));
    });

    it('all strategies return Collection from order method', function () {
        $strategies = [
            new SnowballStrategy,
            new AvalancheStrategy,
            new CustomStrategy,
        ];

        Debt::factory()->count(3)->create();
        $debts = Debt::all();

        foreach ($strategies as $strategy) {
            $result = $strategy->order($debts);
            expect($result)->toBeInstanceOf(Collection::class);
        }
    });

    it('all strategies preserve debt count after ordering', function () {
        $strategies = [
            new SnowballStrategy,
            new AvalancheStrategy,
            new CustomStrategy,
        ];

        Debt::factory()->count(5)->create();
        $debts = Debt::all();
        $originalCount = $debts->count();

        foreach ($strategies as $strategy) {
            $result = $strategy->order($debts);
            expect($result)->toHaveCount($originalCount);
        }
    });

    it('all strategies handle empty collections', function () {
        $strategies = [
            new SnowballStrategy,
            new AvalancheStrategy,
            new CustomStrategy,
        ];

        $emptyCollection = collect();

        foreach ($strategies as $strategy) {
            $result = $strategy->order($emptyCollection);
            expect($result)->toBeInstanceOf(Collection::class)
                ->and($result)->toBeEmpty();
        }
    });

    it('all strategies return string keys', function () {
        $strategies = [
            new SnowballStrategy,
            new AvalancheStrategy,
            new CustomStrategy,
        ];

        foreach ($strategies as $strategy) {
            expect($strategy->getKey())->toBeString()
                ->and($strategy->getKey())->not->toBeEmpty();
        }
    });

    it('all strategies return string names', function () {
        $strategies = [
            new SnowballStrategy,
            new AvalancheStrategy,
            new CustomStrategy,
        ];

        foreach ($strategies as $strategy) {
            expect($strategy->getName())->toBeString()
                ->and($strategy->getName())->not->toBeEmpty();
        }
    });

    it('all strategies return string descriptions', function () {
        $strategies = [
            new SnowballStrategy,
            new AvalancheStrategy,
            new CustomStrategy,
        ];

        foreach ($strategies as $strategy) {
            expect($strategy->getDescription())->toBeString()
                ->and($strategy->getDescription())->not->toBeEmpty();
        }
    });

    it('all strategies reset collection keys', function () {
        $strategies = [
            new SnowballStrategy,
            new AvalancheStrategy,
            new CustomStrategy,
        ];

        Debt::factory()->count(3)->create();
        $debts = Debt::all();

        foreach ($strategies as $strategy) {
            $result = $strategy->order($debts);
            expect($result->keys()->toArray())->toBe([0, 1, 2]);
        }
    });

    it('all strategies contain same debts after ordering', function () {
        $strategies = [
            new SnowballStrategy,
            new AvalancheStrategy,
            new CustomStrategy,
        ];

        Debt::factory()->count(3)->create();
        $debts = Debt::all();
        $originalIds = $debts->pluck('id')->sort()->values()->toArray();

        foreach ($strategies as $strategy) {
            $result = $strategy->order($debts);
            $resultIds = $result->pluck('id')->sort()->values()->toArray();
            expect($resultIds)->toBe($originalIds);
        }
    });
});

describe('strategy differentiation', function () {
    it('snowball and avalanche produce different orders for typical debt set', function () {
        // Create debts where snowball and avalanche would order differently
        Debt::factory()->create([
            'name' => 'High Rate Large Balance',
            'balance' => 10000,
            'interest_rate' => 20,
        ]);
        Debt::factory()->create([
            'name' => 'Low Rate Small Balance',
            'balance' => 1000,
            'interest_rate' => 5,
        ]);

        $debts = Debt::all();

        $snowball = new SnowballStrategy;
        $avalanche = new AvalancheStrategy;

        $snowballOrder = $snowball->order($debts)->pluck('name')->toArray();
        $avalancheOrder = $avalanche->order($debts)->pluck('name')->toArray();

        // Snowball: Small balance first
        expect($snowballOrder[0])->toBe('Low Rate Small Balance');

        // Avalanche: High rate first
        expect($avalancheOrder[0])->toBe('High Rate Large Balance');
    });

    it('custom strategy ignores balance and interest rate', function () {
        // Create debts where custom order differs from both snowball and avalanche
        Debt::factory()->create([
            'name' => 'Lowest Everything but Last Priority',
            'balance' => 100,
            'interest_rate' => 1,
            'custom_priority_order' => 3,
        ]);
        Debt::factory()->create([
            'name' => 'Highest Everything but First Priority',
            'balance' => 100000,
            'interest_rate' => 30,
            'custom_priority_order' => 1,
        ]);

        $debts = Debt::all();

        $snowball = new SnowballStrategy;
        $avalanche = new AvalancheStrategy;
        $custom = new CustomStrategy;

        $snowballFirst = $snowball->order($debts)->first()->name;
        $avalancheFirst = $avalanche->order($debts)->first()->name;
        $customFirst = $custom->order($debts)->first()->name;

        // Snowball would pick lowest balance
        expect($snowballFirst)->toBe('Lowest Everything but Last Priority');

        // Avalanche would pick highest rate
        expect($avalancheFirst)->toBe('Highest Everything but First Priority');

        // Custom follows user priority
        expect($customFirst)->toBe('Highest Everything but First Priority');
    });

    it('all strategies can produce same order for specific debt sets', function () {
        // Create a debt set where all strategies would produce same order
        Debt::factory()->create([
            'name' => 'First',
            'balance' => 1000,  // Lowest balance
            'interest_rate' => 20, // Highest rate
            'custom_priority_order' => 1, // First priority
        ]);
        Debt::factory()->create([
            'name' => 'Second',
            'balance' => 5000,  // Higher balance
            'interest_rate' => 10, // Lower rate
            'custom_priority_order' => 2, // Second priority
        ]);

        $debts = Debt::all();

        $snowball = new SnowballStrategy;
        $avalanche = new AvalancheStrategy;
        $custom = new CustomStrategy;

        $snowballOrder = $snowball->order($debts)->pluck('name')->toArray();
        $avalancheOrder = $avalanche->order($debts)->pluck('name')->toArray();
        $customOrder = $custom->order($debts)->pluck('name')->toArray();

        expect($snowballOrder)->toBe(['First', 'Second'])
            ->and($avalancheOrder)->toBe(['First', 'Second'])
            ->and($customOrder)->toBe(['First', 'Second']);
    });
});

describe('interface method signatures', function () {
    it('order method accepts Collection and returns Collection', function () {
        $strategy = new SnowballStrategy;
        $reflection = new ReflectionMethod($strategy, 'order');

        $parameters = $reflection->getParameters();
        expect($parameters)->toHaveCount(1);
        expect($parameters[0]->getType()->getName())->toBe(Collection::class);

        $returnType = $reflection->getReturnType();
        expect($returnType->getName())->toBe(Collection::class);
    });

    it('getKey method returns string', function () {
        $strategy = new SnowballStrategy;
        $reflection = new ReflectionMethod($strategy, 'getKey');

        $returnType = $reflection->getReturnType();
        expect($returnType->getName())->toBe('string');
    });

    it('getName method returns string', function () {
        $strategy = new SnowballStrategy;
        $reflection = new ReflectionMethod($strategy, 'getName');

        $returnType = $reflection->getReturnType();
        expect($returnType->getName())->toBe('string');
    });

    it('getDescription method returns string', function () {
        $strategy = new SnowballStrategy;
        $reflection = new ReflectionMethod($strategy, 'getDescription');

        $returnType = $reflection->getReturnType();
        expect($returnType->getName())->toBe('string');
    });
});
