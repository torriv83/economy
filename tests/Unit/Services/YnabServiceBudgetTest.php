<?php

declare(strict_types=1);

use App\Services\YnabService;
use Illuminate\Support\Facades\Http;

describe('fetchBudgetSummary', function () {
    it('returns ready to assign amount from YNAB budget', function () {
        $currentMonth = date('Y-m').'-01';

        Http::fake([
            "api.ynab.com/v1/budgets/test-budget/months/{$currentMonth}" => Http::response([
                'data' => [
                    'month' => [
                        'to_be_budgeted' => 2500000, // 2500 kr in milliunits
                    ],
                ],
            ], 200),
            'api.ynab.com/v1/budgets/test-budget/settings' => Http::response([
                'data' => [
                    'settings' => [
                        'currency_format' => [
                            'iso_code' => 'NOK',
                            'decimal_digits' => 2,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YnabService(
            token: 'test-token',
            budgetId: 'test-budget'
        );

        $result = $service->fetchBudgetSummary();

        expect($result['ready_to_assign'])->toEqual(2500.0)
            ->and($result['currency_format']['iso_code'])->toBe('NOK');
    });

    it('returns zero when no money to be budgeted', function () {
        $currentMonth = date('Y-m').'-01';

        Http::fake([
            "api.ynab.com/v1/budgets/test-budget/months/{$currentMonth}" => Http::response([
                'data' => [
                    'month' => [
                        'to_be_budgeted' => 0,
                    ],
                ],
            ], 200),
            'api.ynab.com/v1/budgets/test-budget/settings' => Http::response([
                'data' => [
                    'settings' => [],
                ],
            ], 200),
        ]);

        $service = new YnabService(
            token: 'test-token',
            budgetId: 'test-budget'
        );

        $result = $service->fetchBudgetSummary();

        expect($result['ready_to_assign'])->toEqual(0.0);
    });

    it('handles negative ready to assign (overbudgeted)', function () {
        $currentMonth = date('Y-m').'-01';

        Http::fake([
            "api.ynab.com/v1/budgets/test-budget/months/{$currentMonth}" => Http::response([
                'data' => [
                    'month' => [
                        'to_be_budgeted' => -500000, // -500 kr overbudgeted
                    ],
                ],
            ], 200),
            'api.ynab.com/v1/budgets/test-budget/settings' => Http::response([
                'data' => [
                    'settings' => [],
                ],
            ], 200),
        ]);

        $service = new YnabService(
            token: 'test-token',
            budgetId: 'test-budget'
        );

        $result = $service->fetchBudgetSummary();

        expect($result['ready_to_assign'])->toEqual(-500.0);
    });

    it('throws exception when API fails', function () {
        $currentMonth = date('Y-m').'-01';

        Http::fake([
            "api.ynab.com/v1/budgets/test-budget/months/{$currentMonth}" => Http::response(null, 500),
        ]);

        $service = new YnabService(
            token: 'test-token',
            budgetId: 'test-budget'
        );

        $service->fetchBudgetSummary();
    })->throws(\Illuminate\Http\Client\RequestException::class);
});

describe('fetchCategories', function () {
    it('returns categories with balances and goal info', function () {
        Http::fake([
            'api.ynab.com/v1/budgets/test-budget/categories' => Http::response([
                'data' => [
                    'category_groups' => [
                        [
                            'name' => 'Bills',
                            'hidden' => false,
                            'categories' => [
                                [
                                    'id' => 'cat-1',
                                    'name' => 'Rent',
                                    'hidden' => false,
                                    'deleted' => false,
                                    'balance' => 8000000, // 8000 kr
                                    'budgeted' => 8000000,
                                    'activity' => -8000000,
                                    'goal_type' => 'NEED',
                                    'goal_target' => 8000000,
                                    'goal_under_funded' => 0,
                                    'goal_percentage_complete' => 100,
                                ],
                            ],
                        ],
                        [
                            'name' => 'Fun Money',
                            'hidden' => false,
                            'categories' => [
                                [
                                    'id' => 'cat-2',
                                    'name' => 'Dining Out',
                                    'hidden' => false,
                                    'deleted' => false,
                                    'balance' => 800000, // 800 kr
                                    'budgeted' => 1000000,
                                    'activity' => -200000,
                                    'goal_type' => null,
                                    'goal_target' => 0,
                                    'goal_under_funded' => 0,
                                    'goal_percentage_complete' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YnabService(
            token: 'test-token',
            budgetId: 'test-budget'
        );

        $categories = $service->fetchCategories();

        expect($categories)->toHaveCount(2)
            ->and($categories[0]['name'])->toBe('Rent')
            ->and($categories[0]['group_name'])->toBe('Bills')
            ->and($categories[0]['balance'])->toEqual(8000.0)
            ->and($categories[0]['has_goal'])->toBeTrue()
            ->and($categories[1]['name'])->toBe('Dining Out')
            ->and($categories[1]['group_name'])->toBe('Fun Money')
            ->and($categories[1]['balance'])->toEqual(800.0)
            ->and($categories[1]['has_goal'])->toBeFalse();
    });

    it('skips hidden category groups', function () {
        Http::fake([
            'api.ynab.com/v1/budgets/test-budget/categories' => Http::response([
                'data' => [
                    'category_groups' => [
                        [
                            'name' => 'Hidden Group',
                            'hidden' => true,
                            'categories' => [
                                [
                                    'id' => 'cat-hidden',
                                    'name' => 'Should Not Appear',
                                    'hidden' => false,
                                    'deleted' => false,
                                    'balance' => 1000000,
                                ],
                            ],
                        ],
                        [
                            'name' => 'Visible Group',
                            'hidden' => false,
                            'categories' => [
                                [
                                    'id' => 'cat-visible',
                                    'name' => 'Visible Category',
                                    'hidden' => false,
                                    'deleted' => false,
                                    'balance' => 500000,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YnabService(
            token: 'test-token',
            budgetId: 'test-budget'
        );

        $categories = $service->fetchCategories();

        expect($categories)->toHaveCount(1)
            ->and($categories[0]['name'])->toBe('Visible Category');
    });

    it('skips internal YNAB category groups', function () {
        Http::fake([
            'api.ynab.com/v1/budgets/test-budget/categories' => Http::response([
                'data' => [
                    'category_groups' => [
                        [
                            'name' => 'Internal Master Category',
                            'hidden' => false,
                            'categories' => [
                                [
                                    'id' => 'internal-cat',
                                    'name' => 'Inflow: Ready to Assign',
                                    'hidden' => false,
                                    'deleted' => false,
                                    'balance' => 0,
                                ],
                            ],
                        ],
                        [
                            'name' => 'Credit Card Payments',
                            'hidden' => false,
                            'categories' => [
                                [
                                    'id' => 'cc-cat',
                                    'name' => 'My Credit Card',
                                    'hidden' => false,
                                    'deleted' => false,
                                    'balance' => 5000000,
                                ],
                            ],
                        ],
                        [
                            'name' => 'Regular Category',
                            'hidden' => false,
                            'categories' => [
                                [
                                    'id' => 'regular-cat',
                                    'name' => 'Groceries',
                                    'hidden' => false,
                                    'deleted' => false,
                                    'balance' => 2000000,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YnabService(
            token: 'test-token',
            budgetId: 'test-budget'
        );

        $categories = $service->fetchCategories();

        expect($categories)->toHaveCount(1)
            ->and($categories[0]['name'])->toBe('Groceries');
    });

    it('skips hidden and deleted categories', function () {
        Http::fake([
            'api.ynab.com/v1/budgets/test-budget/categories' => Http::response([
                'data' => [
                    'category_groups' => [
                        [
                            'name' => 'Test Group',
                            'hidden' => false,
                            'categories' => [
                                [
                                    'id' => 'cat-hidden',
                                    'name' => 'Hidden Category',
                                    'hidden' => true,
                                    'deleted' => false,
                                    'balance' => 1000000,
                                ],
                                [
                                    'id' => 'cat-deleted',
                                    'name' => 'Deleted Category',
                                    'hidden' => false,
                                    'deleted' => true,
                                    'balance' => 2000000,
                                ],
                                [
                                    'id' => 'cat-visible',
                                    'name' => 'Active Category',
                                    'hidden' => false,
                                    'deleted' => false,
                                    'balance' => 3000000,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YnabService(
            token: 'test-token',
            budgetId: 'test-budget'
        );

        $categories = $service->fetchCategories();

        expect($categories)->toHaveCount(1)
            ->and($categories[0]['name'])->toBe('Active Category');
    });

    it('identifies overfunded categories', function () {
        Http::fake([
            'api.ynab.com/v1/budgets/test-budget/categories' => Http::response([
                'data' => [
                    'category_groups' => [
                        [
                            'name' => 'Savings',
                            'hidden' => false,
                            'categories' => [
                                [
                                    'id' => 'cat-overfunded',
                                    'name' => 'Emergency Fund',
                                    'hidden' => false,
                                    'deleted' => false,
                                    'balance' => 15000000, // 15000 kr balance
                                    'budgeted' => 15000000,
                                    'activity' => 0,
                                    'goal_type' => 'TB',
                                    'goal_target' => 10000000, // 10000 kr target
                                    'goal_under_funded' => 0,
                                    'goal_percentage_complete' => 100,
                                ],
                                [
                                    'id' => 'cat-underfunded',
                                    'name' => 'Vacation',
                                    'hidden' => false,
                                    'deleted' => false,
                                    'balance' => 5000000, // 5000 kr balance
                                    'budgeted' => 5000000,
                                    'activity' => 0,
                                    'goal_type' => 'TB',
                                    'goal_target' => 20000000, // 20000 kr target
                                    'goal_under_funded' => 15000000,
                                    'goal_percentage_complete' => 25,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YnabService(
            token: 'test-token',
            budgetId: 'test-budget'
        );

        $categories = $service->fetchCategories();

        expect($categories)->toHaveCount(2)
            ->and($categories[0]['name'])->toBe('Emergency Fund')
            ->and($categories[0]['is_overfunded'])->toBeTrue()
            ->and($categories[1]['name'])->toBe('Vacation')
            ->and($categories[1]['is_overfunded'])->toBeFalse();
    });
});
