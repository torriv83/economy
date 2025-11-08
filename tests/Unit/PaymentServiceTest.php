<?php

use App\Models\Debt;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new PaymentService;
});

describe('recordPayment', function () {
    it('creates a payment record', function () {
        $debt = Debt::factory()->create();

        $payment = $this->service->recordPayment(
            $debt,
            plannedAmount: 500.0,
            actualAmount: 550.0,
            monthNumber: 1,
            paymentMonth: '2025-01'
        );

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->debt_id)->toBe($debt->id)
            ->and($payment->planned_amount)->toBe(500.0)
            ->and($payment->actual_amount)->toBe(550.0)
            ->and($payment->month_number)->toBe(1)
            ->and($payment->payment_month)->toBe('2025-01');
    });

    it('saves payment to database', function () {
        $debt = Debt::factory()->create();

        $this->service->recordPayment(
            $debt,
            plannedAmount: 1000.0,
            actualAmount: 1000.0,
            monthNumber: 2,
            paymentMonth: '2025-02'
        );

        $this->assertDatabaseHas('payments', [
            'debt_id' => $debt->id,
            'planned_amount' => 1000.0,
            'actual_amount' => 1000.0,
            'month_number' => 2,
            'payment_month' => '2025-02',
        ]);
    });

    it('sets payment_date to current date', function () {
        $debt = Debt::factory()->create();

        $payment = $this->service->recordPayment(
            $debt,
            plannedAmount: 500.0,
            actualAmount: 500.0,
            monthNumber: 1,
            paymentMonth: '2025-01'
        );

        expect($payment->payment_date)->toBeInstanceOf(DateTime::class);
    });

    it('handles decimal amounts correctly', function () {
        $debt = Debt::factory()->create();

        $payment = $this->service->recordPayment(
            $debt,
            plannedAmount: 123.45,
            actualAmount: 678.90,
            monthNumber: 1,
            paymentMonth: '2025-01'
        );

        expect($payment->planned_amount)->toBe(123.45)
            ->and($payment->actual_amount)->toBe(678.90);
    });
});

describe('recordMonthPayments', function () {
    it('records multiple payments in a transaction', function () {
        $debt1 = Debt::factory()->create(['balance' => 10000]);
        $debt2 = Debt::factory()->create(['balance' => 5000]);

        $payments = [
            ['debt_id' => $debt1->id, 'planned_amount' => 500, 'actual_amount' => 500],
            ['debt_id' => $debt2->id, 'planned_amount' => 300, 'actual_amount' => 350],
        ];

        $result = $this->service->recordMonthPayments($payments, '2025-01', 1);

        expect($result)->toHaveCount(2)
            ->and($result->first()->debt_id)->toBe($debt1->id)
            ->and($result->last()->debt_id)->toBe($debt2->id);
    });

    it('updates debt balances after recording payments', function () {
        $debt1 = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000, 'interest_rate' => 10.0]);
        $debt2 = Debt::factory()->create(['balance' => 5000, 'original_balance' => 5000, 'interest_rate' => 12.0]);

        $payments = [
            ['debt_id' => $debt1->id, 'planned_amount' => 500, 'actual_amount' => 500],
            ['debt_id' => $debt2->id, 'planned_amount' => 300, 'actual_amount' => 300],
        ];

        $this->service->recordMonthPayments($payments, '2025-01', 1);

        // Debt1: 10000 * 10% / 12 = 83.33 interest, so 500 - 83.33 = 416.67 principal
        // Debt2: 5000 * 12% / 12 = 50.00 interest, so 300 - 50.00 = 250.00 principal
        expect($debt1->fresh()->balance)->toBe(9583.33)
            ->and($debt2->fresh()->balance)->toBe(4750.0);
    });

    it('rolls back all payments if one fails', function () {
        $debt1 = Debt::factory()->create();

        $payments = [
            ['debt_id' => $debt1->id, 'planned_amount' => 500, 'actual_amount' => 500],
            ['debt_id' => 99999, 'planned_amount' => 300, 'actual_amount' => 300], // Invalid debt_id
        ];

        try {
            $this->service->recordMonthPayments($payments, '2025-01', 1);
        } catch (\Exception $e) {
            // Expected to fail
        }

        // No payments should be recorded due to transaction rollback
        expect(Payment::count())->toBe(0);
    });

    it('returns collection of created payments', function () {
        $debt1 = Debt::factory()->create();
        $debt2 = Debt::factory()->create();

        $payments = [
            ['debt_id' => $debt1->id, 'planned_amount' => 500, 'actual_amount' => 500],
            ['debt_id' => $debt2->id, 'planned_amount' => 300, 'actual_amount' => 300],
        ];

        $result = $this->service->recordMonthPayments($payments, '2025-01', 1);

        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($result->every(fn ($payment) => $payment instanceof Payment))->toBeTrue();
    });
});

describe('updateDebtBalances', function () {
    it('updates all debt balances based on payments', function () {
        $debt1 = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000, 'interest_rate' => 10.0]);
        $debt2 = Debt::factory()->create(['balance' => 5000, 'original_balance' => 5000, 'interest_rate' => 12.0]);

        Payment::factory()->create(['debt_id' => $debt1->id, 'actual_amount' => 1000]);
        Payment::factory()->create(['debt_id' => $debt1->id, 'actual_amount' => 500]);
        Payment::factory()->create(['debt_id' => $debt2->id, 'actual_amount' => 2000]);

        $this->service->updateDebtBalances();

        // Debt1: 10000 * 10% / 12 = 83.33 interest per payment
        //   Payment 1: 1000 - 83.33 = 916.67 principal
        //   Payment 2: 500 - 83.33 = 416.67 principal
        //   Balance: 10000 - 916.67 - 416.67 = 8666.66
        // Debt2: 5000 * 12% / 12 = 50.00 interest
        //   Payment 1: 2000 - 50.00 = 1950.00 principal
        //   Balance: 5000 - 1950.00 = 3050.00
        expect($debt1->fresh()->balance)->toBe(8666.66)
            ->and($debt2->fresh()->balance)->toBe(3050.0);
    });

    it('prevents negative balances', function () {
        $debt = Debt::factory()->create(['balance' => 1000, 'original_balance' => 1000]);

        Payment::factory()->create(['debt_id' => $debt->id, 'actual_amount' => 1500]);

        $this->service->updateDebtBalances();

        expect($debt->fresh()->balance)->toBe(0.0);
    });

    it('handles debts with no payments', function () {
        $debt = Debt::factory()->create(['balance' => 5000, 'original_balance' => 5000]);

        $this->service->updateDebtBalances();

        expect($debt->fresh()->balance)->toBe(5000.0);
    });

    it('uses original_balance for calculation', function () {
        $debt = Debt::factory()->create(['balance' => 3000, 'original_balance' => 10000, 'interest_rate' => 12.0]);

        Payment::factory()->create(['debt_id' => $debt->id, 'actual_amount' => 5000]);

        $this->service->updateDebtBalances();

        // Interest: 3000 * 12% / 12 = 30.00 (calculated on current balance)
        // Principal: 5000 - 30.00 = 4970.00
        // Should be original_balance - principal = 10000 - 4970.00 = 5030.00
        expect($debt->fresh()->balance)->toBe(5030.0);
    });
});

describe('calculateOverallProgress', function () {
    it('calculates progress percentage correctly', function () {
        Debt::factory()->create(['balance' => 5000, 'original_balance' => 10000]);
        Debt::factory()->create(['balance' => 3000, 'original_balance' => 6000]);

        $progress = $this->service->calculateOverallProgress();

        // Total original: 16000, Total current: 8000, Paid: 8000
        // Progress: (8000 / 16000) * 100 = 50%
        expect($progress)->toBe(50.0);
    });

    it('returns 0 when no debts exist', function () {
        $progress = $this->service->calculateOverallProgress();

        expect($progress)->toBe(0.0);
    });

    it('returns 100 when all debts are paid', function () {
        Debt::factory()->create(['balance' => 0, 'original_balance' => 10000]);
        Debt::factory()->create(['balance' => 0, 'original_balance' => 5000]);

        $progress = $this->service->calculateOverallProgress();

        expect($progress)->toBe(100.0);
    });

    it('handles partial payments accurately', function () {
        Debt::factory()->create(['balance' => 7500, 'original_balance' => 10000]);

        $progress = $this->service->calculateOverallProgress();

        // (2500 / 10000) * 100 = 25%
        expect($progress)->toBe(25.0);
    });
});

describe('getPaymentsForMonth', function () {
    it('retrieves all payments for a specific month', function () {
        $debt1 = Debt::factory()->create();
        $debt2 = Debt::factory()->create();

        Payment::factory()->create(['debt_id' => $debt1->id, 'payment_month' => '2025-01']);
        Payment::factory()->create(['debt_id' => $debt2->id, 'payment_month' => '2025-01']);
        Payment::factory()->create(['debt_id' => $debt1->id, 'payment_month' => '2025-02']);

        $payments = $this->service->getPaymentsForMonth('2025-01');

        expect($payments)->toHaveCount(2)
            ->and($payments->every(fn ($p) => $p->payment_month === '2025-01'))->toBeTrue();
    });

    it('returns empty collection when no payments exist', function () {
        $payments = $this->service->getPaymentsForMonth('2025-01');

        expect($payments)->toHaveCount(0);
    });

    it('orders payments by month_number and debt_id', function () {
        $debt1 = Debt::factory()->create();
        $debt2 = Debt::factory()->create();

        Payment::factory()->create(['debt_id' => $debt2->id, 'payment_month' => '2025-01', 'month_number' => 2]);
        Payment::factory()->create(['debt_id' => $debt1->id, 'payment_month' => '2025-01', 'month_number' => 1]);

        $payments = $this->service->getPaymentsForMonth('2025-01');

        expect($payments->first()->month_number)->toBe(1)
            ->and($payments->last()->month_number)->toBe(2);
    });

    it('eager loads debt relationship', function () {
        $debt = Debt::factory()->create();
        Payment::factory()->create(['debt_id' => $debt->id, 'payment_month' => '2025-01']);

        $payments = $this->service->getPaymentsForMonth('2025-01');

        expect($payments->first()->relationLoaded('debt'))->toBeTrue();
    });
});

describe('paymentExists', function () {
    it('returns true when payment exists', function () {
        $debt = Debt::factory()->create();
        Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1]);

        $exists = $this->service->paymentExists($debt->id, 1);

        expect($exists)->toBeTrue();
    });

    it('returns false when payment does not exist', function () {
        $debt = Debt::factory()->create();

        $exists = $this->service->paymentExists($debt->id, 1);

        expect($exists)->toBeFalse();
    });

    it('distinguishes between different month numbers', function () {
        $debt = Debt::factory()->create();
        Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1]);

        expect($this->service->paymentExists($debt->id, 1))->toBeTrue()
            ->and($this->service->paymentExists($debt->id, 2))->toBeFalse();
    });

    it('distinguishes between different debts', function () {
        $debt1 = Debt::factory()->create();
        $debt2 = Debt::factory()->create();
        Payment::factory()->create(['debt_id' => $debt1->id, 'month_number' => 1]);

        expect($this->service->paymentExists($debt1->id, 1))->toBeTrue()
            ->and($this->service->paymentExists($debt2->id, 1))->toBeFalse();
    });
});

describe('getPayment', function () {
    it('retrieves payment by debt and month number', function () {
        $debt = Debt::factory()->create();
        $payment = Payment::factory()->create([
            'debt_id' => $debt->id,
            'month_number' => 1,
            'actual_amount' => 500,
        ]);

        $result = $this->service->getPayment($debt->id, 1);

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($payment->id)
            ->and($result->actual_amount)->toBe(500.0);
    });

    it('returns null when payment does not exist', function () {
        $debt = Debt::factory()->create();

        $result = $this->service->getPayment($debt->id, 1);

        expect($result)->toBeNull();
    });

    it('retrieves correct payment when multiple exist', function () {
        $debt = Debt::factory()->create();
        Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1, 'actual_amount' => 100]);
        $payment2 = Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 2, 'actual_amount' => 200]);

        $result = $this->service->getPayment($debt->id, 2);

        expect($result->id)->toBe($payment2->id)
            ->and($result->actual_amount)->toBe(200.0);
    });
});

describe('deletePayment', function () {
    it('deletes payment and updates balances', function () {
        $debt = Debt::factory()->create(['balance' => 8000, 'original_balance' => 10000]);
        Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1, 'actual_amount' => 2000]);

        $result = $this->service->deletePayment($debt->id, 1);

        expect($result)->toBeTrue()
            ->and(Payment::count())->toBe(0)
            ->and($debt->fresh()->balance)->toBe(10000.0); // Balance restored
    });

    it('returns false when payment does not exist', function () {
        $debt = Debt::factory()->create();

        $result = $this->service->deletePayment($debt->id, 1);

        expect($result)->toBeFalse();
    });

    it('uses transaction for deletion', function () {
        $debt = Debt::factory()->create(['balance' => 8000, 'original_balance' => 10000]);
        Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1, 'actual_amount' => 2000]);

        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });
        DB::shouldReceive('update')->andReturn(1);

        $this->service->deletePayment($debt->id, 1);
    });

    it('only deletes specified payment', function () {
        $debt = Debt::factory()->create();
        $payment1 = Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1]);
        $payment2 = Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 2]);

        $this->service->deletePayment($debt->id, 1);

        expect(Payment::count())->toBe(1)
            ->and(Payment::find($payment2->id))->not->toBeNull();
    });
});

describe('deleteMonthPayments', function () {
    it('deletes all payments for a specific month', function () {
        $debt1 = Debt::factory()->create();
        $debt2 = Debt::factory()->create();

        Payment::factory()->create(['debt_id' => $debt1->id, 'month_number' => 1]);
        Payment::factory()->create(['debt_id' => $debt2->id, 'month_number' => 1]);
        Payment::factory()->create(['debt_id' => $debt1->id, 'month_number' => 2]);

        $deletedCount = $this->service->deleteMonthPayments(1);

        expect($deletedCount)->toBe(2)
            ->and(Payment::count())->toBe(1);
    });

    it('returns 0 when no payments exist for month', function () {
        $deletedCount = $this->service->deleteMonthPayments(1);

        expect($deletedCount)->toBe(0);
    });

    it('updates debt balances after deletion', function () {
        $debt = Debt::factory()->create(['balance' => 8000, 'original_balance' => 10000]);
        Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1, 'actual_amount' => 2000]);

        $this->service->deleteMonthPayments(1);

        expect($debt->fresh()->balance)->toBe(10000.0);
    });

    it('uses transaction for deletion', function () {
        $debt = Debt::factory()->create();
        Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1]);

        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });
        DB::shouldReceive('update')->andReturn(1);

        $this->service->deleteMonthPayments(1);
    });
});

describe('isMonthFullyPaid', function () {
    it('returns true when all expected debts have payments', function () {
        $debt1 = Debt::factory()->create();
        $debt2 = Debt::factory()->create();

        Payment::factory()->create(['debt_id' => $debt1->id, 'month_number' => 1]);
        Payment::factory()->create(['debt_id' => $debt2->id, 'month_number' => 1]);

        $result = $this->service->isMonthFullyPaid(1, [$debt1->id, $debt2->id]);

        expect($result)->toBeTrue();
    });

    it('returns false when some payments are missing', function () {
        $debt1 = Debt::factory()->create();
        $debt2 = Debt::factory()->create();

        Payment::factory()->create(['debt_id' => $debt1->id, 'month_number' => 1]);

        $result = $this->service->isMonthFullyPaid(1, [$debt1->id, $debt2->id]);

        expect($result)->toBeFalse();
    });

    it('returns false when no expected debts provided', function () {
        $result = $this->service->isMonthFullyPaid(1, []);

        expect($result)->toBeFalse();
    });

    it('handles single debt correctly', function () {
        $debt = Debt::factory()->create();
        Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1]);

        $result = $this->service->isMonthFullyPaid(1, [$debt->id]);

        expect($result)->toBeTrue();
    });
});

describe('updatePaymentAmount', function () {
    it('updates payment amount and recalculates balance', function () {
        $debt = Debt::factory()->create(['balance' => 8500, 'original_balance' => 10000, 'interest_rate' => 12.0]);
        Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1, 'actual_amount' => 1500]);

        $result = $this->service->updatePaymentAmount($debt->id, 1, 2000);

        // When updating payment amount, interest is recalculated based on current balance (8500)
        // Interest: 8500 * 12% / 12 = 85.00
        // Principal: 2000 - 85.00 = 1915.00
        // Balance: 10000 - 1915.00 = 8085.00
        expect($result)->toBeTrue()
            ->and(Payment::first()->actual_amount)->toBe(2000.0)
            ->and($debt->fresh()->balance)->toBe(8085.0);
    });

    it('returns false when payment does not exist', function () {
        $debt = Debt::factory()->create();

        $result = $this->service->updatePaymentAmount($debt->id, 1, 500);

        expect($result)->toBeFalse();
    });

    it('uses transaction for update', function () {
        $debt = Debt::factory()->create(['balance' => 8000, 'original_balance' => 10000]);
        Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1, 'actual_amount' => 2000]);

        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });
        DB::shouldReceive('update')->andReturn(1);

        $this->service->updatePaymentAmount($debt->id, 1, 2500);
    });

    it('handles decimal amounts correctly', function () {
        $debt = Debt::factory()->create(['balance' => 8000, 'original_balance' => 10000]);
        Payment::factory()->create(['debt_id' => $debt->id, 'month_number' => 1, 'actual_amount' => 2000]);

        $this->service->updatePaymentAmount($debt->id, 1, 1234.56);

        expect(Payment::first()->actual_amount)->toBe(1234.56);
    });
});

describe('getHistoricalPayments', function () {
    it('retrieves payments before current month', function () {
        $debt = Debt::factory()->create();
        $pastMonth = now()->subMonth()->format('Y-m');
        $currentMonth = now()->format('Y-m');

        Payment::factory()->create(['debt_id' => $debt->id, 'payment_month' => $pastMonth]);
        Payment::factory()->create(['debt_id' => $debt->id, 'payment_month' => $currentMonth]);

        $historical = $this->service->getHistoricalPayments();

        expect($historical)->toHaveCount(1)
            ->and($historical[0]['date'])->toContain($pastMonth);
    });

    it('groups payments by month', function () {
        $debt1 = Debt::factory()->create(['name' => 'Debt 1']);
        $debt2 = Debt::factory()->create(['name' => 'Debt 2']);
        $pastMonth = now()->subMonth()->format('Y-m');

        Payment::factory()->create(['debt_id' => $debt1->id, 'payment_month' => $pastMonth, 'actual_amount' => 500]);
        Payment::factory()->create(['debt_id' => $debt2->id, 'payment_month' => $pastMonth, 'actual_amount' => 300]);

        $historical = $this->service->getHistoricalPayments();

        expect($historical)->toHaveCount(1)
            ->and($historical[0]['payments'])->toHaveCount(2);
    });

    it('marks payments as historical', function () {
        $debt = Debt::factory()->create();
        $pastMonth = now()->subMonth()->format('Y-m');

        Payment::factory()->create(['debt_id' => $debt->id, 'payment_month' => $pastMonth]);

        $historical = $this->service->getHistoricalPayments();

        expect($historical[0]['isHistorical'])->toBeTrue();
    });

    it('returns empty array when no historical payments exist', function () {
        $historical = $this->service->getHistoricalPayments();

        expect($historical)->toBe([]);
    });

    it('assigns sequential month numbers', function () {
        $debt = Debt::factory()->create();
        $twoMonthsAgo = now()->subMonths(2)->format('Y-m');
        $oneMonthAgo = now()->subMonth()->format('Y-m');

        Payment::factory()->create(['debt_id' => $debt->id, 'payment_month' => $twoMonthsAgo]);
        Payment::factory()->create(['debt_id' => $debt->id, 'payment_month' => $oneMonthAgo]);

        $historical = $this->service->getHistoricalPayments();

        expect($historical[0]['month'])->toBe(1)
            ->and($historical[1]['month'])->toBe(2);
    });

    it('includes debt name in payment data', function () {
        $debt = Debt::factory()->create(['name' => 'Test Debt']);
        $pastMonth = now()->subMonth()->format('Y-m');

        Payment::factory()->create(['debt_id' => $debt->id, 'payment_month' => $pastMonth, 'actual_amount' => 500]);

        $historical = $this->service->getHistoricalPayments();

        expect($historical[0]['payments'][0]['name'])->toBe('Test Debt')
            ->and($historical[0]['payments'][0]['amount'])->toBe(500.0);
    });
});
