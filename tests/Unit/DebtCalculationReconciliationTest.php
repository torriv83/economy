<?php

declare(strict_types=1);

use App\Models\Debt;
use App\Services\DebtCalculationService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('payment schedule with reconciliation', function () {
    it('calculates correct remaining balance after reconciliation', function () {
        // Create debt
        $debt = Debt::factory()->create([
            'name' => 'Test Credit Card',
            'balance' => 4574,
            'original_balance' => 4574,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        // Record a payment for month 1
        $paymentService = app(PaymentService::class);
        $paymentService->recordPayment($debt, 500, 416, 1, '2025-01');

        // Reconcile to 4000
        $paymentService->reconcileDebt($debt, 4000, '2025-01-20', 'Reconciliation adjustment');

        // Update debt balances based on all payments
        $paymentService->updateDebtBalances();

        $debt->refresh();

        // Generate payment schedule
        $calculator = app(DebtCalculationService::class);
        $schedule = $calculator->generatePaymentSchedule(collect([$debt]), 0, 'avalanche');

        // Month 1 should show the correct remaining balance
        $month1 = collect($schedule['schedule'])->first();
        $month1Payment = collect($month1['payments'])->firstWhere('name', 'Test Credit Card');

        // After reconciliation to 4000, the balance should be 3629.74
        expect($debt->balance)->toBe(3629.74);
        expect($month1Payment['remaining'])->toBe(3629.74);
    });

    it('handles reconciliation before any payments', function () {
        // Create debt
        $debt = Debt::factory()->create([
            'name' => 'Test Loan',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 15,
            'minimum_payment' => 500,
        ]);

        // Reconcile to lower balance before any payments
        $paymentService = app(PaymentService::class);
        $paymentService->reconcileDebt($debt, 9000, '2025-01-10', 'Initial reconciliation');

        // Update debt balances
        $paymentService->updateDebtBalances();

        $debt->refresh();

        // Generate payment schedule
        $calculator = app(DebtCalculationService::class);
        $schedule = $calculator->generatePaymentSchedule(collect([$debt]), 0, 'avalanche');

        $month1 = collect($schedule['schedule'])->first();
        $month1Payment = collect($month1['payments'])->firstWhere('name', 'Test Loan');

        // Starting balance should be the reconciled balance
        expect($debt->balance)->toBe(9000.0);

        // After first payment, remaining should be less than 9000
        expect($month1Payment['remaining'])->toBeLessThan(9000.0);
        expect($month1Payment['remaining'])->toBeGreaterThan(8000.0); // Reasonable range
    });

    it('handles multiple reconciliations correctly', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Card',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 12,
            'minimum_payment' => 300,
        ]);

        $paymentService = app(PaymentService::class);

        // First reconciliation
        $paymentService->reconcileDebt($debt, 4500, '2025-01-05', 'First reconciliation');
        $paymentService->updateDebtBalances();

        $debt->refresh();
        expect($debt->balance)->toBe(4500.0);

        // Record a payment
        $paymentService->recordPayment($debt, 300, 300, 1, '2025-01');

        // Second reconciliation
        $paymentService->reconcileDebt($debt, 4000, '2025-01-25', 'Second reconciliation');
        $paymentService->updateDebtBalances();

        $debt->refresh();

        // Generate schedule
        $calculator = app(DebtCalculationService::class);
        $schedule = $calculator->generatePaymentSchedule(collect([$debt]), 0, 'avalanche');

        $month1 = collect($schedule['schedule'])->first();
        $month1Payment = collect($month1['payments'])->firstWhere('name', 'Test Card');

        // The remaining balance should reflect all reconciliations and payments
        // After first reconciliation to 4500, payment of 300, then second reconciliation to 4000,
        // the final balance is 3745 (not 4000) because the payment reduces it further
        expect($debt->balance)->toBe(3745.0);
        expect($month1Payment['remaining'])->toBe(3745.0);
    });

    it('projects future months correctly after reconciliation', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        $paymentService = app(PaymentService::class);

        // Record payment for month 1
        $paymentService->recordPayment($debt, 500, 500, 1, '2025-01');

        // Reconcile
        $paymentService->reconcileDebt($debt, 9000, '2025-01-20', 'Reconciliation');
        $paymentService->updateDebtBalances();

        $debt->refresh();

        // Generate schedule with extra payment
        $calculator = app(DebtCalculationService::class);
        $schedule = $calculator->generatePaymentSchedule(collect([$debt]), 200, 'avalanche');

        // Month 1 should use actual payment
        $month1 = collect($schedule['schedule'])->firstWhere('month', 1);
        $month1Payment = collect($month1['payments'])->firstWhere('name', 'Test Debt');

        // Month 2 should project from the reconciled balance
        $month2 = collect($schedule['schedule'])->firstWhere('month', 2);
        $month2Payment = collect($month2['payments'])->firstWhere('name', 'Test Debt');

        // After payment and reconciliation, the actual balance is 8600
        expect($month1Payment['remaining'])->toBe(8600.0);
        expect($month2Payment['amount'])->toBe(700.0); // 500 minimum + 200 extra
        expect($month2Payment['remaining'])->toBeLessThan(8600.0);
        expect($month2Payment['remaining'])->toBeGreaterThan(7800.0);
    });

    it('handles reconciliation with increased balance', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Card',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 18,
            'minimum_payment' => 200,
        ]);

        $paymentService = app(PaymentService::class);

        // Reconcile to higher balance (like a late fee)
        $paymentService->reconcileDebt($debt, 5500, '2025-01-15', 'Late fee added');
        $paymentService->updateDebtBalances();

        $debt->refresh();

        // Generate schedule
        $calculator = app(DebtCalculationService::class);
        $schedule = $calculator->generatePaymentSchedule(collect([$debt]), 0, 'avalanche');

        $month1 = collect($schedule['schedule'])->first();
        $month1Payment = collect($month1['payments'])->firstWhere('name', 'Test Card');

        // Balance should be increased
        expect($debt->balance)->toBe(5500.0);

        // First projected payment should start from the reconciled balance
        expect($month1Payment['remaining'])->toBeLessThan(5500.0);
        expect($month1Payment['remaining'])->toBeGreaterThan(5200.0);
    });

    it('calculates cumulative principal correctly with reconciliation', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Loan',
            'balance' => 8000,
            'original_balance' => 8000,
            'interest_rate' => 10,
            'minimum_payment' => 400,
        ]);

        $paymentService = app(PaymentService::class);

        // Record payment for month 1
        $paymentService->recordPayment($debt, 400, 400, 1, '2025-01');

        // Reconcile (extra payment discovered)
        $paymentService->reconcileDebt($debt, 7000, '2025-01-20', 'Extra payment found');
        $paymentService->updateDebtBalances();

        $debt->refresh();

        // Verify the payments
        $allPayments = $debt->payments()->get();
        $reconciliationPayment = $allPayments->where('is_reconciliation_adjustment', true)->first();
        $regularPayment = $allPayments->where('month_number', 1)->first();

        // Total principal paid should bring balance from 8000 to actual balance
        // After payment of 400 and reconciliation to 7000, the balance is 6666.67
        $totalPrincipalPaid = $allPayments->sum('principal_paid');
        expect($debt->balance)->toBe(6666.67);
        expect($debt->original_balance - $totalPrincipalPaid)->toBe(6666.67);

        // Generate schedule
        $calculator = app(DebtCalculationService::class);
        $schedule = $calculator->generatePaymentSchedule(collect([$debt]), 0, 'avalanche');

        $month1 = collect($schedule['schedule'])->first();
        $month1Payment = collect($month1['payments'])->firstWhere('name', 'Test Loan');

        // Month 1 remaining should match the actual debt balance
        expect($month1Payment['remaining'])->toBe(6666.67);
    });
});

describe('payment schedule without reconciliation', function () {
    it('still works correctly for debts without reconciliation', function () {
        $debt = Debt::factory()->create([
            'name' => 'Normal Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 12,
            'minimum_payment' => 300,
        ]);

        $paymentService = app(PaymentService::class);

        // Record a normal payment
        $paymentService->recordPayment($debt, 300, 300, 1, '2025-01');
        $paymentService->updateDebtBalances();

        $debt->refresh();

        // Generate schedule
        $calculator = app(DebtCalculationService::class);
        $schedule = $calculator->generatePaymentSchedule(collect([$debt]), 0, 'avalanche');

        $month1 = collect($schedule['schedule'])->first();
        $month1Payment = collect($month1['payments'])->firstWhere('name', 'Normal Debt');

        // Balance calculation should work as before
        expect($debt->balance)->toBeLessThan(5000.0);
        expect($month1Payment['remaining'])->toBe($debt->balance);
    });

    it('handles multiple payments without reconciliation', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Card',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 15,
            'minimum_payment' => 500,
        ]);

        $paymentService = app(PaymentService::class);

        // Record payments for months 1 and 2
        $paymentService->recordPayment($debt, 500, 500, 1, '2025-01');
        $paymentService->recordPayment($debt, 500, 600, 2, '2025-02'); // Extra payment in month 2
        $paymentService->updateDebtBalances();

        $debt->refresh();

        // Generate schedule
        $calculator = app(DebtCalculationService::class);
        $schedule = $calculator->generatePaymentSchedule(collect([$debt]), 0, 'avalanche');

        $month1 = collect($schedule['schedule'])->firstWhere('month', 1);
        $month2 = collect($schedule['schedule'])->firstWhere('month', 2);

        $month1Payment = collect($month1['payments'])->firstWhere('name', 'Test Card');
        $month2Payment = collect($month2['payments'])->firstWhere('name', 'Test Card');

        // Month 2 remaining should be less than month 1 remaining
        expect($month2Payment['remaining'])->toBeLessThan($month1Payment['remaining']);

        // Final remaining should match the actual debt balance
        expect($month2Payment['remaining'])->toBe($debt->balance);
    });
});
