<?php

declare(strict_types=1);

use App\Models\Debt;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\YnabTransactionService;

test('can import a YNAB transaction as a payment', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 5000,
        'ynab_account_id' => 'ynab-123',
    ]);

    $transaction = [
        'id' => 'ynab-tx-001',
        'date' => '2024-06-15',
        'amount' => 500.00,
        'payee_name' => 'Bank Payment',
        'memo' => 'Monthly payment',
    ];

    $service = new YnabTransactionService;
    $paymentService = app(PaymentService::class);

    $payment = $service->importTransaction($debt, $transaction, $paymentService);

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->debt_id)->toBe($debt->id)
        ->and($payment->actual_amount)->toBe(500.00)
        ->and($payment->ynab_transaction_id)->toBe('ynab-tx-001')
        ->and($payment->notes)->toBe('Monthly payment');
});

test('import transaction uses default note when memo is null', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 5000,
        'ynab_account_id' => 'ynab-123',
    ]);

    $transaction = [
        'id' => 'ynab-tx-002',
        'date' => '2024-06-15',
        'amount' => 300.00,
        'payee_name' => null,
        'memo' => null,
    ];

    $service = new YnabTransactionService;
    $paymentService = app(PaymentService::class);

    $payment = $service->importTransaction($debt, $transaction, $paymentService);

    expect($payment->notes)->toBe(__('app.imported_from_ynab'));
});

test('can update payment with YNAB transaction data', function () {
    $debt = Debt::factory()->create();
    $payment = Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 400.00,
        'ynab_transaction_id' => null,
    ]);

    $service = new YnabTransactionService;

    $service->updatePaymentFromTransaction($payment, 'ynab-tx-003', 450.00);

    $payment->refresh();

    expect($payment->actual_amount)->toBe(450.00)
        ->and($payment->ynab_transaction_id)->toBe('ynab-tx-003');
});

test('can update payment with YNAB transaction data including date', function () {
    $debt = Debt::factory()->create();
    $payment = Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 400.00,
        'payment_date' => '2024-06-10',
        'ynab_transaction_id' => null,
    ]);

    $service = new YnabTransactionService;

    $service->updatePaymentFromTransaction($payment, 'ynab-tx-004', 450.00, '2024-06-15');

    $payment->refresh();

    expect($payment->actual_amount)->toBe(450.00)
        ->and($payment->ynab_transaction_id)->toBe('ynab-tx-004')
        ->and($payment->payment_date->format('Y-m-d'))->toBe('2024-06-15');
});

test('compare transactions identifies matched transactions', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'ynab_account_id' => 'ynab-123',
    ]);

    // Create a local payment linked to YNAB with matching date
    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 500.00,
        'payment_date' => '2024-06-15',
        'ynab_transaction_id' => 'ynab-tx-matched',
        'is_reconciliation_adjustment' => false,
    ]);

    $ynabTransactions = collect([
        [
            'id' => 'ynab-tx-matched',
            'date' => '2024-06-15',
            'amount' => 500.00,
            'payee_name' => 'Bank',
            'memo' => null,
        ],
    ]);

    $service = new YnabTransactionService;
    $results = $service->compareTransactionsForDebt($debt, $ynabTransactions);

    expect($results)->toHaveCount(1)
        ->and($results[0]['status'])->toBe('matched')
        ->and($results[0]['id'])->toBe('ynab-tx-matched');
});

test('compare transactions identifies missing transactions', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'ynab_account_id' => 'ynab-123',
    ]);

    $ynabTransactions = collect([
        [
            'id' => 'ynab-tx-new',
            'date' => '2024-06-15',
            'amount' => 600.00,
            'payee_name' => 'Bank',
            'memo' => null,
        ],
    ]);

    $service = new YnabTransactionService;
    $results = $service->compareTransactionsForDebt($debt, $ynabTransactions);

    expect($results)->toHaveCount(1)
        ->and($results[0]['status'])->toBe('missing')
        ->and($results[0]['local_payment_id'])->toBeNull();
});

test('compare transactions identifies mismatched amounts', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'ynab_account_id' => 'ynab-123',
    ]);

    // Create a local payment with different amount
    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 500.00,
        'ynab_transaction_id' => 'ynab-tx-mismatch',
        'is_reconciliation_adjustment' => false,
    ]);

    $ynabTransactions = collect([
        [
            'id' => 'ynab-tx-mismatch',
            'date' => '2024-06-15',
            'amount' => 550.00, // Different amount
            'payee_name' => 'Bank',
            'memo' => null,
        ],
    ]);

    $service = new YnabTransactionService;
    $results = $service->compareTransactionsForDebt($debt, $ynabTransactions);

    expect($results)->toHaveCount(1)
        ->and($results[0]['status'])->toBe('mismatch')
        ->and($results[0]['local_amount'])->toBe(500.00)
        ->and($results[0]['amount'])->toBe(550.00);
});

test('compare transactions identifies mismatched dates', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'ynab_account_id' => 'ynab-123',
    ]);

    // Create a local payment with same amount but different date
    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 500.00,
        'payment_date' => '2024-06-13',
        'ynab_transaction_id' => 'ynab-tx-date-mismatch',
        'is_reconciliation_adjustment' => false,
    ]);

    $ynabTransactions = collect([
        [
            'id' => 'ynab-tx-date-mismatch',
            'date' => '2024-06-15', // Different date, same amount
            'amount' => 500.00,
            'payee_name' => 'Bank',
            'memo' => null,
        ],
    ]);

    $service = new YnabTransactionService;
    $results = $service->compareTransactionsForDebt($debt, $ynabTransactions);

    expect($results)->toHaveCount(1)
        ->and($results[0]['status'])->toBe('mismatch')
        ->and($results[0]['local_amount'])->toBe(500.00)
        ->and($results[0]['local_date'])->toBe('2024-06-13');
});

test('import transaction calculates month_number as integer', function () {
    // Create debt on a specific date with time component
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 5000,
        'ynab_account_id' => 'ynab-123',
        'created_at' => '2025-11-19 16:18:27', // Middle of the month with time
    ]);

    // Transaction later in same month (should still be month 1)
    $transaction = [
        'id' => 'ynab-tx-month-test',
        'date' => '2025-11-28', // 9 days later in same month
        'amount' => 500.00,
        'payee_name' => 'Bank Payment',
        'memo' => 'Test payment',
    ];

    $service = new YnabTransactionService;
    $paymentService = app(PaymentService::class);

    $payment = $service->importTransaction($debt, $transaction, $paymentService);

    // month_number should be exactly 1 (integer), not a float like 1.277...
    expect($payment->month_number)->toBe(1)
        ->and($payment->month_number)->toBeInt();
});

test('import transaction calculates correct month_number across months', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 5000,
        'ynab_account_id' => 'ynab-123',
        'created_at' => '2025-10-15 10:00:00',
    ]);

    // Transaction in the next month
    $transaction = [
        'id' => 'ynab-tx-month-2',
        'date' => '2025-11-05', // Next month
        'amount' => 500.00,
        'payee_name' => 'Bank Payment',
        'memo' => 'Second month payment',
    ];

    $service = new YnabTransactionService;
    $paymentService = app(PaymentService::class);

    $payment = $service->importTransaction($debt, $transaction, $paymentService);

    expect($payment->month_number)->toBe(2)
        ->and($payment->month_number)->toBeInt();
});

test('fuzzy matching finds unlinked payments in same month', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'ynab_account_id' => 'ynab-123',
    ]);

    // Create an unlinked local payment
    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 500.00,
        'payment_date' => '2024-06-10',
        'ynab_transaction_id' => null,
        'is_reconciliation_adjustment' => false,
    ]);

    $ynabTransactions = collect([
        [
            'id' => 'ynab-tx-fuzzy',
            'date' => '2024-06-15',
            'amount' => 500.00, // Exact match amount
            'payee_name' => 'Bank',
            'memo' => null,
        ],
    ]);

    $service = new YnabTransactionService;
    $results = $service->compareTransactionsForDebt($debt, $ynabTransactions);

    expect($results)->toHaveCount(1)
        ->and($results[0]['status'])->toBe('mismatch') // Different dates: 2024-06-10 vs 2024-06-15
        ->and($results[0]['local_payment_id'])->not->toBeNull()
        ->and($results[0]['local_date'])->toBe('2024-06-10');
});

it('fuzzy matching returns matched when amount and date both match', function () {
    $debt = Debt::factory()->create([
        'ynab_account_id' => 'test-account',
    ]);

    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 500.00,
        'payment_date' => '2024-06-15',
        'ynab_transaction_id' => null,
        'is_reconciliation_adjustment' => false,
    ]);

    $ynabTransactions = collect([
        [
            'id' => 'ynab-tx-exact',
            'date' => '2024-06-15',
            'amount' => 500.00,
            'payee_name' => 'Bank',
            'memo' => null,
        ],
    ]);

    $service = new YnabTransactionService;
    $results = $service->compareTransactionsForDebt($debt, $ynabTransactions);

    expect($results)->toHaveCount(1)
        ->and($results[0]['status'])->toBe('matched')
        ->and($results[0]['local_payment_id'])->not->toBeNull()
        ->and($results[0]['local_date'])->toBe('2024-06-15');
});
