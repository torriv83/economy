<?php

declare(strict_types=1);

use App\Models\Debt;
use App\Services\YnabDiscrepancyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new YnabDiscrepancyService;
});

describe('findDiscrepancies', function () {
    it('returns all discrepancy categories', function () {
        $result = $this->service->findDiscrepancies(collect([]), collect([]));

        expect($result)->toHaveKeys(['new', 'closed', 'potential_matches', 'balance_mismatch']);
    });

    it('returns empty arrays when no debts exist', function () {
        $result = $this->service->findDiscrepancies(collect([]), collect([]));

        expect($result['new'])->toBeEmpty()
            ->and($result['closed'])->toBeEmpty()
            ->and($result['potential_matches'])->toBeEmpty()
            ->and($result['balance_mismatch'])->toBeEmpty();
    });
});

describe('findNewDebts', function () {
    it('identifies new YNAB debts that do not exist locally', function () {
        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'New Credit Card',
                'balance' => 5000.00,
                'interest_rate' => 18.9,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([]));

        expect($result['new'])->toHaveCount(1)
            ->and($result['new'][0]['name'])->toBe('New Credit Card');
    });

    it('excludes closed YNAB debts from new debts', function () {
        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Closed Credit Card',
                'balance' => 0,
                'interest_rate' => 18.9,
                'minimum_payment' => 0,
                'closed' => true,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([]));

        expect($result['new'])->toBeEmpty();
    });

    it('excludes already linked debts from new debts', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'My Credit Card',
            'ynab_account_id' => 'ynab-123',
            'balance' => 5000.00,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Credit Card in YNAB',
                'balance' => 5000.00,
                'interest_rate' => 18.9,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['new'])->toBeEmpty();
    });
});

describe('findClosedDebts', function () {
    it('identifies local debts that are closed in YNAB by account ID', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'My Credit Card',
            'ynab_account_id' => 'ynab-123',
            'balance' => 5000.00,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Credit Card',
                'balance' => 0,
                'interest_rate' => 18.9,
                'minimum_payment' => 0,
                'closed' => true,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['closed'])->toHaveCount(1)
            ->and($result['closed'][0]['id'])->toBe($localDebt->id)
            ->and($result['closed'][0]['name'])->toBe('My Credit Card');
    });

    it('identifies local debts that are closed in YNAB by name match', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'Credit Card',
            'ynab_account_id' => null,
            'balance' => 5000.00,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Credit Card',
                'balance' => 0,
                'interest_rate' => 18.9,
                'minimum_payment' => 0,
                'closed' => true,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['closed'])->toHaveCount(1)
            ->and($result['closed'][0]['name'])->toBe('Credit Card');
    });

    it('does not flag active debts as closed', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'Active Credit Card',
            'ynab_account_id' => 'ynab-123',
            'balance' => 5000.00,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Active Credit Card',
                'balance' => 5000.00,
                'interest_rate' => 18.9,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['closed'])->toBeEmpty();
    });
});

describe('findPotentialMatches', function () {
    it('finds potential matches when names contain each other', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'Credit Card',
            'ynab_account_id' => null,
            'balance' => 4500.00,
            'interest_rate' => 18.9,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Credit Card - Chase',
                'balance' => 5000.00,
                'interest_rate' => 18.9,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['potential_matches'])->toHaveCount(1)
            ->and($result['potential_matches'][0]['ynab']['name'])->toBe('Credit Card - Chase')
            ->and($result['potential_matches'][0]['local']['id'])->toBe($localDebt->id);
    });

    it('finds potential matches using similar_text for close names', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'Visa Card',
            'ynab_account_id' => null,
            'balance' => 4500.00,
            'interest_rate' => 18.9,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Visa Cards',
                'balance' => 5000.00,
                'interest_rate' => 18.9,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['potential_matches'])->toHaveCount(1);
    });

    it('skips debts already linked to YNAB for potential matches', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'Credit Card',
            'ynab_account_id' => 'ynab-other',
            'balance' => 4500.00,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Credit Card - Chase',
                'balance' => 5000.00,
                'interest_rate' => 18.9,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['potential_matches'])->toBeEmpty()
            ->and($result['new'])->toHaveCount(1);
    });

    it('returns local debt details in potential match', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'Credit Card',
            'ynab_account_id' => null,
            'balance' => 4500.00,
            'interest_rate' => 18.9,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Credit Card - Chase',
                'balance' => 5000.00,
                'interest_rate' => 20.0,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        $match = $result['potential_matches'][0];
        expect($match['local'])->toHaveKeys(['id', 'name', 'balance', 'interest_rate'])
            ->and($match['local']['balance'])->toBe(4500.00)
            ->and($match['local']['interest_rate'])->toBe(18.9);
    });
});

describe('findBalanceMismatches', function () {
    it('finds balance mismatches for linked debts', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'Credit Card',
            'ynab_account_id' => 'ynab-123',
            'balance' => 5000.00,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Credit Card',
                'balance' => 5500.00,
                'interest_rate' => 18.9,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['balance_mismatch'])->toHaveCount(1)
            ->and($result['balance_mismatch'][0]['local_balance'])->toBe(5000.00)
            ->and($result['balance_mismatch'][0]['ynab_balance'])->toBe(5500.00)
            ->and($result['balance_mismatch'][0]['difference'])->toBe(500.00);
    });

    it('calculates negative difference when local is higher', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'Credit Card',
            'ynab_account_id' => 'ynab-123',
            'balance' => 6000.00,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Credit Card',
                'balance' => 5500.00,
                'interest_rate' => 18.9,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['balance_mismatch'][0]['difference'])->toBe(-500.00);
    });

    it('ignores tiny floating point differences within tolerance', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'Credit Card',
            'ynab_account_id' => 'ynab-123',
            'balance' => 5000.00,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Credit Card',
                'balance' => 5000.0005,
                'interest_rate' => 18.9,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['balance_mismatch'])->toBeEmpty();
    });

    it('does not report mismatch for matching balances', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'Credit Card',
            'ynab_account_id' => 'ynab-123',
            'balance' => 5000.00,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Credit Card',
                'balance' => 5000.00,
                'interest_rate' => 18.9,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['balance_mismatch'])->toBeEmpty();
    });

    it('includes debt objects in balance mismatch result', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'Credit Card',
            'ynab_account_id' => 'ynab-123',
            'balance' => 5000.00,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'Credit Card',
                'balance' => 5500.00,
                'interest_rate' => 18.9,
                'minimum_payment' => 100,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['balance_mismatch'][0]['local_debt'])->toBeInstanceOf(Debt::class)
            ->and($result['balance_mismatch'][0]['ynab_debt'])->toBeArray();
    });
});

describe('complex scenarios', function () {
    it('handles multiple debts with various discrepancies', function () {
        // Create local debts
        $linkedDebtWithMismatch = Debt::factory()->create([
            'name' => 'Linked Card',
            'ynab_account_id' => 'ynab-1',
            'balance' => 1000.00,
        ]);

        $unlinkedDebtWithMatch = Debt::factory()->create([
            'name' => 'Store Card',
            'ynab_account_id' => null,
            'balance' => 2000.00,
            'interest_rate' => 25.0,
        ]);

        $debtForClosed = Debt::factory()->create([
            'name' => 'Old Loan',
            'ynab_account_id' => 'ynab-3',
            'balance' => 3000.00,
        ]);

        $localDebts = collect([$linkedDebtWithMismatch, $unlinkedDebtWithMatch, $debtForClosed]);

        $ynabDebts = collect([
            // Balance mismatch
            [
                'ynab_id' => 'ynab-1',
                'name' => 'Linked Card',
                'balance' => 1500.00,
                'interest_rate' => 18.9,
                'minimum_payment' => 50,
                'closed' => false,
            ],
            // Potential match
            [
                'ynab_id' => 'ynab-2',
                'name' => 'Store Card - Walmart',
                'balance' => 2100.00,
                'interest_rate' => 25.0,
                'minimum_payment' => 50,
                'closed' => false,
            ],
            // Closed in YNAB
            [
                'ynab_id' => 'ynab-3',
                'name' => 'Old Loan',
                'balance' => 0,
                'interest_rate' => 5.0,
                'minimum_payment' => 0,
                'closed' => true,
            ],
            // New debt
            [
                'ynab_id' => 'ynab-4',
                'name' => 'New Car Loan',
                'balance' => 15000.00,
                'interest_rate' => 7.5,
                'minimum_payment' => 300,
                'closed' => false,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, $localDebts);

        // Balance mismatch includes both:
        // - ynab-1 (local: 1000, YNAB: 1500)
        // - ynab-3 (local: 3000, YNAB: 0 - also closed)
        expect($result['balance_mismatch'])->toHaveCount(2)
            ->and($result['potential_matches'])->toHaveCount(1)
            ->and($result['closed'])->toHaveCount(1)
            ->and($result['new'])->toHaveCount(1)
            ->and($result['new'][0]['name'])->toBe('New Car Loan');
    });

    it('handles case-insensitive name matching for closed debts', function () {
        $localDebt = Debt::factory()->create([
            'name' => 'CREDIT CARD',
            'ynab_account_id' => null,
            'balance' => 5000.00,
        ]);

        $ynabDebts = collect([
            [
                'ynab_id' => 'ynab-123',
                'name' => 'credit card',
                'balance' => 0,
                'interest_rate' => 18.9,
                'minimum_payment' => 0,
                'closed' => true,
            ],
        ]);

        $result = $this->service->findDiscrepancies($ynabDebts, collect([$localDebt]));

        expect($result['closed'])->toHaveCount(1);
    });
});
