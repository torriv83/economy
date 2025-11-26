<?php

use App\Models\Debt;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new PaymentService;
});

describe('reconcileDebt', function () {
    it('creates reconciliation payment for positive difference', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $payment = $this->service->reconcileDebt(
            $debt,
            actualBalance: 10500, // 500 kr more than expected
            reconciliationDate: '2024-01-15',
            notes: 'Unexpected fee added'
        );

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->is_reconciliation_adjustment)->toBeTrue()
            ->and($payment->debt_id)->toBe($debt->id)
            ->and($payment->actual_amount)->toBe(-500.0) // Negative = increases balance
            ->and($payment->principal_paid)->toBe(-500.0) // Negative = balance increase
            ->and($payment->interest_paid)->toBe(0.0)
            ->and($payment->month_number)->toBeNull()
            ->and($payment->notes)->toBe('Unexpected fee added')
            ->and($payment->payment_date->format('Y-m-d'))->toBe('2024-01-15');
    });

    it('creates reconciliation payment for negative difference', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $payment = $this->service->reconcileDebt(
            $debt,
            actualBalance: 9500, // 500 kr less than expected
            reconciliationDate: '2024-01-15',
            notes: 'Extra payment found'
        );

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->actual_amount)->toBe(500.0) // Positive = decreases balance
            ->and($payment->principal_paid)->toBe(500.0) // Positive = balance decrease
            ->and($payment->interest_paid)->toBe(0.0)
            ->and($payment->notes)->toBe('Extra payment found');
    });

    it('updates debt balance after reconciliation', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $this->service->reconcileDebt(
            $debt,
            actualBalance: 9500,
            reconciliationDate: '2024-01-15'
        );

        $debt->refresh();
        expect($debt->balance)->toBe(9500.0);
    });

    it('throws exception when no adjustment needed', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000.00,
            'original_balance' => 10000,
        ]);

        expect(fn () => $this->service->reconcileDebt(
            $debt,
            actualBalance: 10000.00, // Same as current
            reconciliationDate: '2024-01-15'
        ))->toThrow(InvalidArgumentException::class, 'No adjustment needed');
    });

    it('generates default notes when none provided', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $payment = $this->service->reconcileDebt(
            $debt,
            actualBalance: 10500,
            reconciliationDate: '2024-01-15'
        );

        expect($payment->notes)->toContain('Avstemming')
            ->and($payment->notes)->toContain('500');
    });

    it('sets payment_month from reconciliation date', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $payment = $this->service->reconcileDebt(
            $debt,
            actualBalance: 9500,
            reconciliationDate: '2024-03-15'
        );

        expect($payment->payment_month)->toBe('2024-03');
    });

    it('handles very small differences near zero threshold', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000.00,
            'original_balance' => 10000,
        ]);

        // 0.005 rounds to 0.01 which is >= 0.01 threshold
        expect(fn () => $this->service->reconcileDebt(
            $debt,
            actualBalance: 10000.005,
            reconciliationDate: '2024-01-15'
        ))->toThrow(InvalidArgumentException::class); // Rounds to 0.00 difference
    });

    it('allows multiple reconciliations for same debt', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $this->service->reconcileDebt($debt, 9500, '2024-01-15', 'First');
        $debt->refresh();

        $this->service->reconcileDebt($debt, 9000, '2024-02-15', 'Second');
        $debt->refresh();

        expect(Payment::where('is_reconciliation_adjustment', true)->count())->toBe(2)
            ->and($debt->balance)->toBe(9000.0);
    });
});

describe('getReconciliationsForDebt', function () {
    it('returns only reconciliations for specified debt', function () {
        $debt1 = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000]);
        $debt2 = Debt::factory()->create(['balance' => 5000, 'original_balance' => 5000]);

        Payment::factory()->reconciliation()->create(['debt_id' => $debt1->id]);
        Payment::factory()->reconciliation()->create(['debt_id' => $debt1->id]);
        Payment::factory()->reconciliation()->create(['debt_id' => $debt2->id]);

        $result = $this->service->getReconciliationsForDebt($debt1);

        expect($result)->toHaveCount(2)
            ->and($result->every(fn ($p) => $p->debt_id === $debt1->id))->toBeTrue();
    });

    it('does not include regular payments', function () {
        $debt = Debt::factory()->create();

        // Create regular payment
        Payment::factory()->create([
            'debt_id' => $debt->id,
            'is_reconciliation_adjustment' => false,
        ]);

        // Create reconciliation
        Payment::factory()->reconciliation()->create(['debt_id' => $debt->id]);

        $result = $this->service->getReconciliationsForDebt($debt);

        expect($result)->toHaveCount(1)
            ->and($result->first()->is_reconciliation_adjustment)->toBeTrue();
    });

    it('returns empty collection when debt has no reconciliations', function () {
        $debt = Debt::factory()->create();

        $result = $this->service->getReconciliationsForDebt($debt);

        expect($result)->toBeEmpty();
    });

    it('orders reconciliations by date descending then created_at descending', function () {
        $debt = Debt::factory()->create();

        $older = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-01-15',
        ]);

        $newer = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-03-15',
        ]);

        $middle = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-02-15',
        ]);

        $result = $this->service->getReconciliationsForDebt($debt);

        expect($result->first()->id)->toBe($newer->id)
            ->and($result->get(1)->id)->toBe($middle->id)
            ->and($result->last()->id)->toBe($older->id);
    });
});

describe('getAllReconciliations', function () {
    it('returns all reconciliations across all debts', function () {
        $debt1 = Debt::factory()->create();
        $debt2 = Debt::factory()->create();
        $debt3 = Debt::factory()->create();

        Payment::factory()->reconciliation()->create(['debt_id' => $debt1->id]);
        Payment::factory()->reconciliation()->create(['debt_id' => $debt2->id]);
        Payment::factory()->reconciliation()->create(['debt_id' => $debt3->id]);

        $result = $this->service->getAllReconciliations();

        expect($result)->toHaveCount(3);
    });

    it('does not include regular payments', function () {
        $debt = Debt::factory()->create();

        Payment::factory()->count(3)->create([
            'debt_id' => $debt->id,
            'is_reconciliation_adjustment' => false,
        ]);

        Payment::factory()->reconciliation()->create(['debt_id' => $debt->id]);

        $result = $this->service->getAllReconciliations();

        expect($result)->toHaveCount(1)
            ->and($result->first()->is_reconciliation_adjustment)->toBeTrue();
    });

    it('returns empty collection when no reconciliations exist', function () {
        $debt = Debt::factory()->create();

        // Create only regular payments
        Payment::factory()->count(3)->create([
            'debt_id' => $debt->id,
            'is_reconciliation_adjustment' => false,
        ]);

        $result = $this->service->getAllReconciliations();

        expect($result)->toBeEmpty();
    });

    it('eager loads debt relationship', function () {
        $debt = Debt::factory()->create(['name' => 'Test Debt']);

        Payment::factory()->reconciliation()->create(['debt_id' => $debt->id]);

        $result = $this->service->getAllReconciliations();

        expect($result->first()->relationLoaded('debt'))->toBeTrue()
            ->and($result->first()->debt->name)->toBe('Test Debt');
    });

    it('orders by date descending then created_at descending', function () {
        $debt = Debt::factory()->create();

        $older = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-01-15',
        ]);

        $newer = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-03-15',
        ]);

        $result = $this->service->getAllReconciliations();

        expect($result->first()->id)->toBe($newer->id)
            ->and($result->last()->id)->toBe($older->id);
    });
});

describe('updateReconciliation', function () {
    it('updates reconciliation balance correctly', function () {
        $debt = Debt::factory()->create([
            'balance' => 9500,
            'original_balance' => 10000,
        ]);

        $payment = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500, // Initial: balance went from 10000 to 9500
        ]);

        $result = $this->service->updateReconciliation(
            $payment,
            newActualBalance: 9000, // Change to 9000 instead of 9500
            reconciliationDate: '2024-02-20',
            notes: 'Corrected balance'
        );

        expect($result->notes)->toBe('Corrected balance')
            ->and($result->payment_date->format('Y-m-d'))->toBe('2024-02-20');

        $debt->refresh();
        expect($debt->balance)->toBe(9000.0);
    });

    it('updates payment date', function () {
        $debt = Debt::factory()->create([
            'balance' => 9500,
            'original_balance' => 10000,
        ]);

        $payment = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-01-15',
            'principal_paid' => 500,
        ]);

        $result = $this->service->updateReconciliation(
            $payment,
            newActualBalance: 9500,
            reconciliationDate: '2024-03-20'
        );

        expect($result->payment_date->format('Y-m-d'))->toBe('2024-03-20')
            ->and($result->payment_month)->toBe('2024-03');
    });

    it('updates notes', function () {
        $debt = Debt::factory()->create([
            'balance' => 9500,
            'original_balance' => 10000,
        ]);

        $payment = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'notes' => 'Original notes',
            'principal_paid' => 500,
        ]);

        $result = $this->service->updateReconciliation(
            $payment,
            newActualBalance: 9500,
            reconciliationDate: '2024-01-15',
            notes: 'Updated notes'
        );

        expect($result->notes)->toBe('Updated notes');
    });

    it('throws exception for non-reconciliation payments', function () {
        $debt = Debt::factory()->create();

        $regularPayment = Payment::factory()->create([
            'debt_id' => $debt->id,
            'is_reconciliation_adjustment' => false,
        ]);

        expect(fn () => $this->service->updateReconciliation(
            $regularPayment,
            newActualBalance: 9500,
            reconciliationDate: '2024-01-15'
        ))->toThrow(InvalidArgumentException::class, 'not a reconciliation adjustment');
    });

    it('generates default notes when none provided', function () {
        $debt = Debt::factory()->create([
            'balance' => 9500,
            'original_balance' => 10000,
        ]);

        $payment = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500,
        ]);

        $result = $this->service->updateReconciliation(
            $payment,
            newActualBalance: 9000,
            reconciliationDate: '2024-01-15'
        );

        expect($result->notes)->toContain('Avstemming');
    });

    it('recalculates principal_paid and actual_amount', function () {
        $debt = Debt::factory()->create([
            'balance' => 9500,
            'original_balance' => 10000,
        ]);

        $payment = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500, // Original: 10000 - 500 = 9500
            'actual_amount' => 500,
        ]);

        // Update to make balance 9000 instead of 9500
        // Balance without reconciliation = 9500 + 500 = 10000
        // New difference = 9000 - 10000 = -1000
        // New principal_paid = -(-1000) = 1000
        $result = $this->service->updateReconciliation(
            $payment,
            newActualBalance: 9000,
            reconciliationDate: '2024-01-15'
        );

        expect($result->principal_paid)->toBe(1000.0)
            ->and($result->actual_amount)->toBe(1000.0);

        $debt->refresh();
        expect($debt->balance)->toBe(9000.0);
    });

    it('returns fresh payment model', function () {
        $debt = Debt::factory()->create([
            'balance' => 9500,
            'original_balance' => 10000,
        ]);

        $payment = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500,
        ]);

        $result = $this->service->updateReconciliation(
            $payment,
            newActualBalance: 9500,
            reconciliationDate: '2024-02-20'
        );

        expect($result)->toBeInstanceOf(Payment::class)
            ->and($result->payment_date->format('Y-m-d'))->toBe('2024-02-20');
    });
});

describe('deleteReconciliation', function () {
    it('deletes the reconciliation payment', function () {
        $debt = Debt::factory()->create([
            'balance' => 9500,
            'original_balance' => 10000,
        ]);

        $payment = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500,
        ]);

        $paymentId = $payment->id;

        $result = $this->service->deleteReconciliation($payment);

        expect($result)->toBeTrue()
            ->and(Payment::find($paymentId))->toBeNull();
    });

    it('updates debt balance after deletion', function () {
        $debt = Debt::factory()->create([
            'balance' => 9500,
            'original_balance' => 10000,
        ]);

        $payment = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500, // This reduced balance from 10000 to 9500
        ]);

        $this->service->deleteReconciliation($payment);

        $debt->refresh();
        expect($debt->balance)->toBe(10000.0); // Balance restored
    });

    it('throws exception for non-reconciliation payments', function () {
        $debt = Debt::factory()->create();

        $regularPayment = Payment::factory()->create([
            'debt_id' => $debt->id,
            'is_reconciliation_adjustment' => false,
        ]);

        expect(fn () => $this->service->deleteReconciliation($regularPayment))
            ->toThrow(InvalidArgumentException::class, 'not a reconciliation adjustment');
    });

    it('returns true on successful deletion', function () {
        $debt = Debt::factory()->create([
            'balance' => 9500,
            'original_balance' => 10000,
        ]);

        $payment = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500,
        ]);

        $result = $this->service->deleteReconciliation($payment);

        expect($result)->toBeTrue();
    });

    it('handles deletion of balance-increasing reconciliation', function () {
        $debt = Debt::factory()->create([
            'balance' => 10500,
            'original_balance' => 10000,
        ]);

        $payment = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => -500, // This increased balance from 10000 to 10500
        ]);

        $this->service->deleteReconciliation($payment);

        $debt->refresh();
        expect($debt->balance)->toBe(10000.0); // Balance restored to original
    });

    it('correctly handles debt with multiple reconciliations', function () {
        $debt = Debt::factory()->create([
            'balance' => 9000,
            'original_balance' => 10000,
        ]);

        $firstReconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500, // 10000 - 500 = 9500
        ]);

        $secondReconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500, // 9500 - 500 = 9000
        ]);

        // Delete only the second reconciliation
        $this->service->deleteReconciliation($secondReconciliation);

        $debt->refresh();
        expect($debt->balance)->toBe(9500.0); // Only first reconciliation remains

        // Verify first reconciliation still exists
        expect(Payment::find($firstReconciliation->id))->not->toBeNull();
    });
});

describe('edge cases', function () {
    it('handles zero interest rate debts', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 0,
        ]);

        $payment = $this->service->reconcileDebt(
            $debt,
            actualBalance: 9500,
            reconciliationDate: '2024-01-15'
        );

        expect($payment->interest_paid)->toBe(0.0)
            ->and($payment->principal_paid)->toBe(500.0);
    });

    it('handles large balance adjustments', function () {
        $debt = Debt::factory()->create([
            'balance' => 100000,
            'original_balance' => 100000,
        ]);

        $payment = $this->service->reconcileDebt(
            $debt,
            actualBalance: 50000,
            reconciliationDate: '2024-01-15'
        );

        expect($payment->principal_paid)->toBe(50000.0);

        $debt->refresh();
        expect($debt->balance)->toBe(50000.0);
    });

    it('handles decimal balance values', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000.50,
            'original_balance' => 10000.50,
        ]);

        $payment = $this->service->reconcileDebt(
            $debt,
            actualBalance: 9500.75,
            reconciliationDate: '2024-01-15'
        );

        expect(abs($payment->principal_paid - 499.75))->toBeLessThan(0.01);
    });

    it('reconciliation does not affect other debts', function () {
        $debt1 = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $debt2 = Debt::factory()->create([
            'balance' => 5000,
            'original_balance' => 5000,
        ]);

        $this->service->reconcileDebt($debt1, 9500, '2024-01-15');

        $debt1->refresh();
        $debt2->refresh();

        expect($debt1->balance)->toBe(9500.0)
            ->and($debt2->balance)->toBe(5000.0); // Unchanged
    });

    it('planned_amount is always zero for reconciliations', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $payment = $this->service->reconcileDebt(
            $debt,
            actualBalance: 9500,
            reconciliationDate: '2024-01-15'
        );

        expect($payment->planned_amount)->toBe(0.0);
    });
});
