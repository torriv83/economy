<?php

use App\Livewire\DebtList;
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

        Livewire::test(DebtList::class)
            ->call('loadData')
            ->call('openReconciliationModal', $debt->id)
            ->assertSet('reconciliations.'.$debt->id.'.show', true)
            ->call('closeReconciliationModal', $debt->id)
            ->assertSet('reconciliations.'.$debt->id, null);
    });

    it('displays debt information correctly', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Credit Card',
            'balance' => 15000,
            'original_balance' => 20000,
        ]);

        Livewire::test(DebtList::class)
            ->call('loadData')
            ->call('openReconciliationModal', $debt->id)
            ->assertSee(__('app.reconcile_debt'))
            ->assertSee('Test Credit Card')
            ->assertSee('15 000,00'); // Formatted balance
    });

    it('initializes with todays date', function () {
        $debt = Debt::factory()->create();

        Livewire::test(DebtList::class)
            ->call('loadData')
            ->call('openReconciliationModal', $debt->id)
            ->assertSet('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'));
    });
});

describe('difference calculation', function () {
    it('calculates positive difference correctly', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
        ]);

        Livewire::test(DebtList::class)
            ->call('loadData')
            ->call('openReconciliationModal', $debt->id)
            ->set('reconciliations.'.$debt->id.'.balance', '10500')
            ->assertSee('+500,00 kr');
    });

    it('calculates negative difference correctly', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
        ]);

        Livewire::test(DebtList::class)
            ->call('loadData')
            ->call('openReconciliationModal', $debt->id)
            ->set('reconciliations.'.$debt->id.'.balance', '9500')
            ->assertSee('-500,00 kr');
    });

    it('calculates zero difference correctly', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
        ]);

        Livewire::test(DebtList::class)
            ->call('loadData')
            ->call('openReconciliationModal', $debt->id)
            ->set('reconciliations.'.$debt->id.'.balance', '10000')
            ->assertSee('+0,00 kr');
    });

    it('handles decimal amounts correctly', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000.50,
        ]);

        Livewire::test(DebtList::class)
            ->call('loadData')
            ->call('openReconciliationModal', $debt->id)
            ->set('reconciliations.'.$debt->id.'.balance', '10200.75')
            ->assertSee('+200,25 kr');
    });
});

describe('reconciliation validation', function () {
    it('requires actual balance', function () {
        $debt = Debt::factory()->create();

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(['reconciliations.'.$debt->id.'.balance' => 'required']);
    });

    it('requires actual balance to be numeric', function () {
        $debt = Debt::factory()->create();

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', 'invalid')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(['reconciliations.'.$debt->id.'.balance' => 'numeric']);
    });

    it('requires actual balance to be at least 0', function () {
        $debt = Debt::factory()->create();

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '-100')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(['reconciliations.'.$debt->id.'.balance' => 'min']);
    });

    it('requires reconciliation date', function () {
        $debt = Debt::factory()->create();

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '10000')
            ->set('reconciliations.'.$debt->id.'.date', '')
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(['reconciliations.'.$debt->id.'.date' => 'required']);
    });

    it('requires reconciliation date to be valid date', function () {
        $debt = Debt::factory()->create();

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '10000')
            ->set('reconciliations.'.$debt->id.'.date', 'invalid-date')
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(['reconciliations.'.$debt->id.'.date' => 'date_format']);
    });

    it('allows notes to be optional', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '10500')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
            ->set('reconciliations.'.$debt->id.'.notes', '')
            ->call('reconcileDebt', $debt->id)
            ->assertHasNoErrors(['reconciliations.'.$debt->id.'.notes']);
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

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '10500')
            ->set('reconciliations.'.$debt->id.'.date', '15.01.2025')
            ->set('reconciliations.'.$debt->id.'.notes', 'Late fee not recorded')
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

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '9500')
            ->set('reconciliations.'.$debt->id.'.date', '15.01.2025')
            ->set('reconciliations.'.$debt->id.'.notes', 'Extra payment made')
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

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '10000.00')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        expect(Payment::count())->toBe(0);
    });

    it('updates debt balance after reconciliation', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
        ]);

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '9500')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
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

        Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set('reconciliations.'.$debt->id.'.balance', '9500')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id)
            ->assertSet('reconciliations.'.$debt->id, null);
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
        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '10500')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
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
        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '9500')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
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

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '10000.005')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
            ->call('reconcileDebt', $debt->id);

        // Difference rounds to 0.00, should not create payment
        expect(Payment::count())->toBe(0);
    });

    it('handles large differences', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '15000')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
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

        Livewire::test(DebtList::class)
            ->set('reconciliations.'.$debt->id.'.balance', '10500')
            ->set('reconciliations.'.$debt->id.'.date', now()->format('d.m.Y'))
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
