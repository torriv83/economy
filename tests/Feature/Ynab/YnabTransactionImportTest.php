<?php

declare(strict_types=1);

use App\Livewire\PayoffCalendar;
use App\Models\Debt;
use App\Models\Payment;
use App\Services\YnabService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'services.ynab.token' => 'test-token',
        'services.ynab.budget_id' => 'test-budget-id',
    ]);

    // Define route needed by the calendar view
    Route::get('/debts/create', fn () => '')->name('debts.create');
});

describe('YNAB modal', function () {
    test('opens modal and shows loading state', function () {
        $debt = Debt::factory()->create([
            'ynab_account_id' => 'ynab-account-123',
        ]);

        Http::fake([
            'api.ynab.com/*' => Http::response([
                'data' => ['transactions' => []],
            ]),
        ]);

        Livewire::test(PayoffCalendar::class)
            ->call('openYnabModal')
            ->assertSet('showYnabModal', true);
    });

    test('closes modal and resets state', function () {
        Livewire::test(PayoffCalendar::class)
            ->set('showYnabModal', true)
            ->set('ynabComparisonResults', [[
                'debt_id' => 1,
                'debt_name' => 'Test Debt',
                'ynab_transactions' => [],
            ]])
            ->set('ynabError', 'Some error')
            ->call('closeYnabModal')
            ->assertSet('showYnabModal', false)
            ->assertSet('ynabComparisonResults', [])
            ->assertSet('ynabError', '');
    });

    test('shows error when YNAB not configured', function () {
        config(['services.ynab.token' => null]);
        config(['services.ynab.budget_id' => null]);

        // Re-bind the service with empty strings to avoid constructor error
        app()->singleton(YnabService::class, function () {
            return new YnabService(
                token: config('services.ynab.token') ?? '',
                budgetId: config('services.ynab.budget_id') ?? ''
            );
        });

        Livewire::test(PayoffCalendar::class)
            ->call('openYnabModal')
            ->assertSet('ynabError', __('app.ynab_not_configured'));
    });

    test('shows error when no debts linked to YNAB', function () {
        // Create debt without YNAB link
        Debt::factory()->create([
            'ynab_account_id' => null,
        ]);

        Livewire::test(PayoffCalendar::class)
            ->call('openYnabModal')
            ->assertSet('ynabError', __('app.no_debts_linked_to_ynab'));
    });
});

describe('YNAB transaction comparison', function () {
    test('identifies missing transactions that are not in local database', function () {
        $debt = Debt::factory()->create([
            'ynab_account_id' => 'ynab-account-123',
        ]);

        Http::fake([
            'api.ynab.com/v1/budgets/test-budget-id/accounts/ynab-account-123/transactions*' => Http::response([
                'data' => [
                    'transactions' => [
                        [
                            'id' => 'tx-1',
                            'date' => '2024-11-15',
                            'amount' => 2500000, // 2500 kr in milliunits
                            'payee_name' => 'Bank Payment',
                            'memo' => 'Monthly payment',
                            'deleted' => false,
                        ],
                    ],
                ],
            ]),
        ]);

        $component = Livewire::test(PayoffCalendar::class)
            ->call('openYnabModal');

        $results = $component->get('ynabComparisonResults');

        expect($results)->toHaveCount(1);
        expect($results[0]['debt_id'])->toBe($debt->id);
        expect($results[0]['ynab_transactions'][0]['status'])->toBe('missing');
        expect($results[0]['ynab_transactions'][0]['amount'])->toEqual(2500.0);
    });

    test('identifies matched transactions when payment exists with same ynab_transaction_id', function () {
        $debt = Debt::factory()->create([
            'ynab_account_id' => 'ynab-account-123',
        ]);

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'actual_amount' => 2500,
            'payment_date' => '2024-11-15',
            'ynab_transaction_id' => 'tx-1',
        ]);

        Http::fake([
            'api.ynab.com/v1/budgets/test-budget-id/accounts/ynab-account-123/transactions*' => Http::response([
                'data' => [
                    'transactions' => [
                        [
                            'id' => 'tx-1',
                            'date' => '2024-11-15',
                            'amount' => 2500000,
                            'payee_name' => 'Bank Payment',
                            'memo' => null,
                            'deleted' => false,
                        ],
                    ],
                ],
            ]),
        ]);

        $component = Livewire::test(PayoffCalendar::class)
            ->call('openYnabModal');

        $results = $component->get('ynabComparisonResults');

        expect($results)->toHaveCount(1);
        expect($results[0]['ynab_transactions'][0]['status'])->toBe('matched');
    });

    test('identifies mismatch when amounts differ', function () {
        $debt = Debt::factory()->create([
            'ynab_account_id' => 'ynab-account-123',
        ]);

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'actual_amount' => 2000, // Different from YNAB amount
            'payment_date' => '2024-11-15',
            'ynab_transaction_id' => 'tx-1',
        ]);

        Http::fake([
            'api.ynab.com/v1/budgets/test-budget-id/accounts/ynab-account-123/transactions*' => Http::response([
                'data' => [
                    'transactions' => [
                        [
                            'id' => 'tx-1',
                            'date' => '2024-11-15',
                            'amount' => 2500000, // 2500 kr
                            'payee_name' => 'Bank Payment',
                            'memo' => null,
                            'deleted' => false,
                        ],
                    ],
                ],
            ]),
        ]);

        $component = Livewire::test(PayoffCalendar::class)
            ->call('openYnabModal');

        $results = $component->get('ynabComparisonResults');

        expect($results[0]['ynab_transactions'][0]['status'])->toBe('mismatch');
        expect($results[0]['ynab_transactions'][0]['local_amount'])->toEqual(2000.0);
    });

    test('filters out negative transactions (purchases that increase debt)', function () {
        $debt = Debt::factory()->create([
            'ynab_account_id' => 'ynab-account-123',
        ]);

        Http::fake([
            'api.ynab.com/v1/budgets/test-budget-id/accounts/ynab-account-123/transactions*' => Http::response([
                'data' => [
                    'transactions' => [
                        [
                            'id' => 'tx-payment',
                            'date' => '2024-11-15',
                            'amount' => 2500000, // Positive = payment
                            'payee_name' => 'Payment',
                            'memo' => null,
                            'deleted' => false,
                        ],
                        [
                            'id' => 'tx-purchase',
                            'date' => '2024-11-10',
                            'amount' => -500000, // Negative = purchase
                            'payee_name' => 'Store Purchase',
                            'memo' => null,
                            'deleted' => false,
                        ],
                    ],
                ],
            ]),
        ]);

        $component = Livewire::test(PayoffCalendar::class)
            ->call('openYnabModal');

        $results = $component->get('ynabComparisonResults');

        // Should only have the payment transaction
        expect($results[0]['ynab_transactions'])->toHaveCount(1);
        expect($results[0]['ynab_transactions'][0]['id'])->toBe('tx-payment');
    });

    test('filters out deleted transactions', function () {
        $debt = Debt::factory()->create([
            'ynab_account_id' => 'ynab-account-123',
        ]);

        Http::fake([
            'api.ynab.com/v1/budgets/test-budget-id/accounts/ynab-account-123/transactions*' => Http::response([
                'data' => [
                    'transactions' => [
                        [
                            'id' => 'tx-active',
                            'date' => '2024-11-15',
                            'amount' => 2500000,
                            'payee_name' => 'Payment',
                            'memo' => null,
                            'deleted' => false,
                        ],
                        [
                            'id' => 'tx-deleted',
                            'date' => '2024-11-10',
                            'amount' => 1000000,
                            'payee_name' => 'Deleted Payment',
                            'memo' => null,
                            'deleted' => true,
                        ],
                    ],
                ],
            ]),
        ]);

        $component = Livewire::test(PayoffCalendar::class)
            ->call('openYnabModal');

        $results = $component->get('ynabComparisonResults');

        expect($results[0]['ynab_transactions'])->toHaveCount(1);
        expect($results[0]['ynab_transactions'][0]['id'])->toBe('tx-active');
    });
});

describe('importing YNAB transactions', function () {
    test('imports missing transaction as new payment', function () {
        $debt = Debt::factory()->create([
            'ynab_account_id' => 'ynab-account-123',
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        Http::fake([
            'api.ynab.com/*' => Http::response([
                'data' => [
                    'transactions' => [
                        [
                            'id' => 'tx-to-import',
                            'date' => '2024-11-15',
                            'amount' => 2500000,
                            'payee_name' => 'Bank Payment',
                            'memo' => 'Monthly payment',
                            'deleted' => false,
                        ],
                    ],
                ],
            ]),
        ]);

        Livewire::test(PayoffCalendar::class)
            ->call('openYnabModal')
            ->call('importYnabTransaction', 'tx-to-import', $debt->id);

        $payment = Payment::where('ynab_transaction_id', 'tx-to-import')->first();

        expect($payment)->not->toBeNull();
        expect($payment->debt_id)->toBe($debt->id);
        expect($payment->actual_amount)->toEqual(2500.0);
        expect($payment->payment_date->format('Y-m-d'))->toBe('2024-11-15');
        expect($payment->notes)->toBe('Monthly payment');
    });

    test('updates payment amount from YNAB when mismatch', function () {
        $debt = Debt::factory()->create([
            'ynab_account_id' => 'ynab-account-123',
        ]);

        $payment = Payment::factory()->create([
            'debt_id' => $debt->id,
            'actual_amount' => 2000,
            'ynab_transaction_id' => null,
        ]);

        Http::fake([
            'api.ynab.com/*' => Http::response([
                'data' => ['transactions' => []],
            ]),
        ]);

        Livewire::test(PayoffCalendar::class)
            ->call('updatePaymentFromYnab', 'tx-123', $payment->id, 2500.0);

        $payment->refresh();

        expect($payment->actual_amount)->toEqual(2500.0);
        expect($payment->ynab_transaction_id)->toBe('tx-123');
    });
});

describe('YnabService transaction fetching', function () {
    test('fetches payment transactions for account', function () {
        Http::fake([
            'api.ynab.com/v1/budgets/test-budget/accounts/account-123/transactions*' => Http::response([
                'data' => [
                    'transactions' => [
                        [
                            'id' => 'tx-1',
                            'date' => '2024-11-15',
                            'amount' => 2500000,
                            'payee_name' => 'Payment',
                            'memo' => 'Test memo',
                            'deleted' => false,
                        ],
                    ],
                ],
            ]),
        ]);

        $service = new YnabService('test-token', 'test-budget');
        $transactions = $service->fetchPaymentTransactions('account-123');

        expect($transactions)->toHaveCount(1);
        expect($transactions[0]['id'])->toBe('tx-1');
        expect($transactions[0]['amount'])->toEqual(2500.0);
        expect($transactions[0]['date'])->toBe('2024-11-15');
    });

    test('uses since_date parameter when provided', function () {
        Http::fake([
            'api.ynab.com/v1/budgets/test-budget/accounts/account-123/transactions*' => Http::response([
                'data' => ['transactions' => []],
            ]),
        ]);

        $service = new YnabService('test-token', 'test-budget');
        $service->fetchPaymentTransactions('account-123', new DateTime('2024-11-01'));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'since_date=2024-11-01');
        });
    });
});
