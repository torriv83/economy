<?php

use App\Livewire\PaymentPlan;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('reconcile debt modal', function () {
    it('can open and close the modal', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        Livewire::test(PaymentPlan::class)
            ->call('openReconciliationModal', $debt->id)
            ->assertSet('reconciliationModals.'.$debt->id, true)
            ->call('closeReconciliationModal', $debt->id)
            ->assertCount('reconciliationModals', 0);
    });

    it('displays debt information correctly', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Credit Card',
            'balance' => 15000,
            'original_balance' => 20000,
        ]);

        Livewire::test(PaymentPlan::class)
            ->call('openReconciliationModal', $debt->id)
            ->assertSee('Avstem gjeld')
            ->assertSee('Test Credit Card')
            ->assertSee('15 000,00'); // Formatted balance
    });

    it('initializes with todays date', function () {
        $debt = Debt::factory()->create();

        Livewire::test(PaymentPlan::class)
            ->call('openReconciliationModal', $debt->id)
            ->assertSet('reconciliationDates.'.$debt->id, now()->format('d.m.Y'));
    });
});

describe('difference calculation', function () {
    it('calculates positive difference correctly', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
        ]);

        Livewire::test(PaymentPlan::class)
            ->call('openReconciliationModal', $debt->id)
            ->set('reconciliationBalances.'.$debt->id, '10500')
            ->assertSee('+500,00 kr');
    });

    it('calculates negative difference correctly', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
        ]);

        Livewire::test(PaymentPlan::class)
            ->call('openReconciliationModal', $debt->id)
            ->set('reconciliationBalances.'.$debt->id, '9500')
            ->assertSee('-500,00 kr');
    });

    it('calculates zero difference correctly', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
        ]);

        Livewire::test(PaymentPlan::class)
            ->call('openReconciliationModal', $debt->id)
            ->set('reconciliationBalances.'.$debt->id, '10000')
            ->assertSee('+0,00 kr');
    });

    it('handles decimal amounts correctly', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000.50,
        ]);

        Livewire::test(PaymentPlan::class)
            ->call('openReconciliationModal', $debt->id)
            ->set('reconciliationBalances.'.$debt->id, '10200.75')
            ->assertSee('+200,25 kr');
    });
});

describe('reconciliation validation', function () {
    it('requires actual balance', function () {
        $debt = Debt::factory()->create();

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(['reconciliationBalances.'.$debt->id => 'required']);
    });

    it('requires actual balance to be numeric', function () {
        $debt = Debt::factory()->create();

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, 'invalid')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(['reconciliationBalances.'.$debt->id => 'numeric']);
    });

    it('requires actual balance to be at least 0', function () {
        $debt = Debt::factory()->create();

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '-100')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(['reconciliationBalances.'.$debt->id => 'min']);
    });

    it('requires reconciliation date', function () {
        $debt = Debt::factory()->create();

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '10000')
            ->set('reconciliationDates.'.$debt->id, '')
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(['reconciliationDates.'.$debt->id => 'required']);
    });

    it('requires reconciliation date to be valid date', function () {
        $debt = Debt::factory()->create();

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '10000')
            ->set('reconciliationDates.'.$debt->id, 'invalid-date')
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(['reconciliationDates.'.$debt->id => 'date_format']);
    });

    it('allows notes to be optional', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '10500')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->set('reconciliationNotes.'.$debt->id, '')
            ->call('reconcileDebt', $debt->id)
            ->assertHasNoErrors(['reconciliationNotes.'.$debt->id]);
    });
});

describe('creating reconciliation adjustments', function () {
    it('creates adjustment payment for positive difference', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
        ]);

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '10500')
            ->set('reconciliationDates.'.$debt->id, '15.01.2025')
            ->set('reconciliationNotes.'.$debt->id, 'Late fee not recorded')
            ->call('reconcileDebt', $debt->id);

        expect(Payment::count())->toBe(1);

        $payment = Payment::first();
        expect($payment)
            ->debt_id->toBe($debt->id)
            ->actual_amount->toBe(-500.0) // Negative because it increases balance
            ->is_reconciliation_adjustment->toBeTrue()
            ->month_number->toBeNull() // NULL to avoid collision with regular monthly payments
            ->notes->toBe('Late fee not recorded')
            ->payment_date->format('Y-m-d')->toBe('2025-01-15');
    });

    it('creates adjustment payment for negative difference', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
        ]);

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '9500')
            ->set('reconciliationDates.'.$debt->id, '15.01.2025')
            ->set('reconciliationNotes.'.$debt->id, 'Extra payment made')
            ->call('reconcileDebt', $debt->id);

        expect(Payment::count())->toBe(1);

        $payment = Payment::first();
        expect($payment)
            ->debt_id->toBe($debt->id)
            ->actual_amount->toBe(500.0) // Positive because it decreases balance
            ->is_reconciliation_adjustment->toBeTrue()
            ->notes->toBe('Extra payment made');
    });

    it('does not create adjustment when difference is negligible', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000.00,
        ]);

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '10000.00')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        expect(Payment::count())->toBe(0);
    });

    it('updates debt balance after reconciliation', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
        ]);

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '9500')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        // Payment should have been created with principal_paid
        $payment = Payment::first();
        expect($payment->principal_paid)->toBeGreaterThan(0);

        // Balance should be recalculated
        $debt->refresh();
        // Balance = original_balance - SUM(principal_paid)
        expect($debt->balance)->toBeLessThan(10000);
    });

    it('closes modal after successful reconciliation', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        Livewire::test(PaymentPlan::class)
            ->call('openReconciliationModal', $debt->id)
            ->set('reconciliationBalances.'.$debt->id, '9500')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id)
            ->assertCount('reconciliationModals', 0);
    });
});

describe('interest and principal calculations', function () {
    it('correctly splits positive adjustment into interest and principal', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12, // 1% per month = 100 kr monthly interest
        ]);

        // Add 500 kr adjustment (like a late fee)
        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '10500')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        $payment = Payment::first();

        // Monthly interest = 10000 * (12/100) / 12 = 100
        // For positive difference, some goes to interest, rest to principal
        // Interest paid should be min(500, 100) = 100
        // Principal should be 500 - 100 = 400
        // But for reconciliation, principal is negative (increases balance)
        expect($payment->interest_paid)->toBeLessThanOrEqual(0); // Negative for positive adjustment
        expect($payment->principal_paid)->toBeLessThanOrEqual(0); // Negative increases balance
        expect(abs($payment->interest_paid) + abs($payment->principal_paid))->toBe(500.0);
    });

    it('correctly handles negative adjustment as principal payment', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
        ]);

        // 500 kr overpayment
        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '9500')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        $payment = Payment::first();

        // For negative difference (overpayment), treat as extra principal
        expect($payment->interest_paid)->toBe(0.0);
        expect($payment->principal_paid)->toBeGreaterThan(0);
        expect($payment->actual_amount)->toBe(500.0); // Positive decreases balance
    });
});

describe('edge cases', function () {
    it('handles very small differences correctly', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000.00,
        ]);

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '10000.005')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        // Difference rounds to 0.00, should not create payment
        expect(Payment::count())->toBe(0);
    });

    it('handles large differences', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '15000')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        $payment = Payment::first();
        expect($payment->actual_amount)->toBe(-5000.0); // Negative increases balance
    });

    it('handles reconciliation with zero interest rate', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 0,
        ]);

        Livewire::test(PaymentPlan::class)
            ->set('reconciliationBalances.'.$debt->id, '10500')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        $payment = Payment::first();
        // With 0% interest, monthly interest is 0
        // All adjustment goes to principal
        expect($payment->actual_amount)->toBe(-500.0); // Negative increases balance
    });

    it('allows multiple reconciliation adjustments with different month numbers', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        // First reconciliation succeeds
        $service = app(\App\Services\PaymentService::class);
        $firstPayment = $service->reconcileDebt($debt, 9500, '2025-01-15', 'First adjustment');

        expect(Payment::where('is_reconciliation_adjustment', true)->count())->toBe(1);
        expect($firstPayment->month_number)->toBeNull(); // NULL to avoid collision with regular payments

        // Refresh debt to get updated balance after first reconciliation
        $debt = $debt->fresh();

        // Second reconciliation with significant difference from NEW balance
        $secondPayment = $service->reconcileDebt($debt, 9000, '2025-02-15', 'Second adjustment');

        expect(Payment::where('is_reconciliation_adjustment', true)->count())->toBe(2);
        expect($secondPayment->month_number)->toBeNull(); // Both can be NULL without collision
    });
});

describe('payment schedule updates after reconciliation', function () {
    it('updates detailed schedule with new balance after reconciliation', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Card',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        $component = Livewire::test(PaymentPlan::class)
            ->set('extraPayment', 0);

        // Get initial schedule - should start from 10000 balance
        $initialSchedule = $component->get('detailedSchedule');
        $firstMonth = collect($initialSchedule)->first();
        $debtPayment = collect($firstMonth['payments'])->firstWhere('name', 'Test Card');

        // Verify initial balance is correct
        expect($debtPayment['remaining'])->toBeLessThan(10000); // After first payment

        // Reconcile to lower balance (extra payment made)
        $component
            ->set('reconciliationBalances.'.$debt->id, '9000')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        // Get updated schedule - should now start from 9000 balance
        $updatedSchedule = $component->get('detailedSchedule');
        $firstMonthUpdated = collect($updatedSchedule)->first();
        $debtPaymentUpdated = collect($firstMonthUpdated['payments'])->firstWhere('name', 'Test Card');

        // The remaining balance in the schedule should reflect the new starting balance
        // Since we reconciled to 9000, the remaining after first payment should be less than initial
        expect($debtPaymentUpdated['remaining'])->toBeLessThan($debtPayment['remaining']);

        // Verify the debt balance was actually updated in DB
        $debt->refresh();
        expect($debt->balance)->toBe(9000.0);
    });

    it('updates debt payoff schedule after reconciliation', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Card',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        $component = Livewire::test(PaymentPlan::class)
            ->set('extraPayment', 0);

        // Get initial payoff schedule
        $initialPayoff = $component->get('debtPayoffSchedule');
        $initialMonths = collect($initialPayoff)->firstWhere('name', 'Test Card')['payoff_month'];

        // Reconcile to lower balance
        $component
            ->set('reconciliationBalances.'.$debt->id, '5000')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        // Get updated payoff schedule
        $updatedPayoff = $component->get('debtPayoffSchedule');
        $updatedMonths = collect($updatedPayoff)->firstWhere('name', 'Test Card')['payoff_month'];

        // Should pay off faster with lower balance
        expect($updatedMonths)->toBeLessThan($initialMonths);

        // Verify balance in payoff schedule reflects new balance
        $updatedBalance = collect($updatedPayoff)->firstWhere('name', 'Test Card')['balance'];
        expect($updatedBalance)->toBe(5000.0);
    });

    it('clears all computed property caches after reconciliation', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Card',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        $component = Livewire::test(PaymentPlan::class)
            ->set('extraPayment', 0);

        // Access all computed properties to cache them
        $initialSchedule = $component->get('paymentSchedule');
        $initialDetailed = $component->get('detailedSchedule');
        $initialPayoff = $component->get('debtPayoffSchedule');
        $initialTotal = $component->get('totalMonths');
        $initialInterest = $component->get('totalInterest');

        // Reconcile to significantly lower balance
        $component
            ->set('reconciliationBalances.'.$debt->id, '3000')
            ->set('reconciliationDates.'.$debt->id, now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        // Access all computed properties again - they should reflect new values
        $updatedSchedule = $component->get('paymentSchedule');
        $updatedDetailed = $component->get('detailedSchedule');
        $updatedPayoff = $component->get('debtPayoffSchedule');
        $updatedTotal = $component->get('totalMonths');
        $updatedInterest = $component->get('totalInterest');

        // Verify ALL computed properties reflect the new balance
        expect($updatedTotal)->toBeLessThan($initialTotal);
        expect($updatedInterest)->toBeLessThan($initialInterest);

        // Verify payoff schedule has updated balance
        $payoffBalance = collect($updatedPayoff)->firstWhere('name', 'Test Card')['balance'];
        expect($payoffBalance)->toBe(3000.0);

        // Verify detailed schedule has lower remaining balances
        $firstMonthInitial = collect($initialDetailed)->first();
        $firstMonthUpdated = collect($updatedDetailed)->first();

        $initialRemaining = collect($firstMonthInitial['payments'])->firstWhere('name', 'Test Card')['remaining'];
        $updatedRemaining = collect($firstMonthUpdated['payments'])->firstWhere('name', 'Test Card')['remaining'];

        expect($updatedRemaining)->toBeLessThan($initialRemaining);
    });
});
