<?php

use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCalculationService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Payment Tracking Integration', function () {
    beforeEach(function () {
        $this->paymentService = new PaymentService;
        $this->calculationService = new DebtCalculationService($this->paymentService);
    });

    it('records payment and updates debt balance', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12.0,
        ]);

        $payment = $this->paymentService->recordPayment(
            $debt,
            plannedAmount: 1000.0,
            actualAmount: 1000.0,
            monthNumber: 1,
            paymentMonth: '2025-01'
        );

        $this->paymentService->updateDebtBalances();

        // Interest: 10000 * 12% / 12 = 100.00
        // Principal: 1000 - 100 = 900.00
        // Balance: 10000 - 900 = 9100.00
        expect($payment)->toBeInstanceOf(Payment::class)
            ->and(Payment::count())->toBe(1)
            ->and($debt->fresh()->balance)->toBe(9100.0);
    });

    it('records multiple payments and updates all balances', function () {
        $debt1 = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12.0,
        ]);
        $debt2 = Debt::factory()->create([
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10.0,
        ]);

        $payments = [
            ['debt_id' => $debt1->id, 'planned_amount' => 1000, 'actual_amount' => 1000],
            ['debt_id' => $debt2->id, 'planned_amount' => 500, 'actual_amount' => 500],
        ];

        $this->paymentService->recordMonthPayments($payments, '2025-01', 1);

        // Debt1: 10000 * 12% / 12 = 100.00 interest, 1000 - 100 = 900 principal
        // Debt2: 5000 * 10% / 12 = 41.67 interest, 500 - 41.67 = 458.33 principal
        expect(Payment::count())->toBe(2)
            ->and($debt1->fresh()->balance)->toBe(9100.0)
            ->and($debt2->fresh()->balance)->toBe(4541.67);
    });

    it('calculates overall progress after payments', function () {
        Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);
        Debt::factory()->create([
            'balance' => 5000,
            'original_balance' => 5000,
        ]);

        $progress = $this->paymentService->calculateOverallProgress();
        expect($progress)->toBe(0.0);

        $debts = Debt::all();
        $this->paymentService->recordPayment(
            $debts[0],
            plannedAmount: 5000.0,
            actualAmount: 5000.0,
            monthNumber: 1,
            paymentMonth: '2025-01'
        );
        $this->paymentService->updateDebtBalances();

        $progress = $this->paymentService->calculateOverallProgress();
        expect($progress)->toBeGreaterThan(30.0)->toBeLessThan(35.0);
    });

    it('integrates actual payment into schedule', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'original_balance' => 10000,
            'balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        // Historical payment already recorded
        // Interest: 10000 * 12% / 12 = 100.00
        // Principal: 600 - 100 = 500.00
        $debt->payments()->create([
            'planned_amount' => 500,
            'actual_amount' => 600,
            'interest_paid' => 100,
            'principal_paid' => 500,
            'payment_date' => now()->subMonth(),
            'month_number' => 1,
            'payment_month' => now()->subMonth()->format('Y-m'),
        ]);

        // Update balance to reflect the principal paid
        $this->paymentService->updateDebtBalances();

        $debts = collect([$debt->fresh('payments')]);
        $schedule = $this->calculationService->generatePaymentSchedule($debts, 0);

        // Month 1 uses actual payment amount (600)
        expect($schedule['schedule'])->not->toBeEmpty()
            ->and($schedule['schedule'][0]['payments'][0]['amount'])->toBe(600.0);
        // Remaining: original_balance - principal_paid = 10000 - 500 = 9500
        expect($schedule['schedule'][0]['payments'][0]['remaining'])->toBe(9500.0);
    });
});

describe('Payment Tracking Workflows', function () {
    beforeEach(function () {
        $this->paymentService = new PaymentService;
    });

    it('handles complete payment workflow for single month', function () {
        $debt1 = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000, 'interest_rate' => 12.0]);
        $debt2 = Debt::factory()->create(['balance' => 5000, 'original_balance' => 5000, 'interest_rate' => 10.0]);

        $payments = [
            ['debt_id' => $debt1->id, 'planned_amount' => 1000, 'actual_amount' => 1000],
            ['debt_id' => $debt2->id, 'planned_amount' => 500, 'actual_amount' => 500],
        ];

        $recordedPayments = $this->paymentService->recordMonthPayments($payments, '2025-01', 1);

        // Debt1: 10000 * 12% / 12 = 100.00 interest, 900 principal
        // Debt2: 5000 * 10% / 12 = 41.67 interest, 458.33 principal
        expect($recordedPayments)->toHaveCount(2)
            ->and($debt1->fresh()->balance)->toBe(9100.0)
            ->and($debt2->fresh()->balance)->toBe(4541.67)
            ->and($this->paymentService->paymentExists($debt1->id, 1))->toBeTrue()
            ->and($this->paymentService->paymentExists($debt2->id, 1))->toBeTrue()
            ->and($this->paymentService->isMonthFullyPaid(1, [$debt1->id, $debt2->id]))->toBeTrue();
    });

    it('handles progressive multi-month payment tracking', function () {
        $debt = Debt::factory()->create([
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 12.0,
        ]);

        // Month 1: 5000 * 12% / 12 = 50.00 interest, 950.00 principal
        $this->paymentService->recordPayment($debt, 1000, 1000, 1, '2025-01');
        $this->paymentService->updateDebtBalances();
        expect($debt->fresh()->balance)->toBe(4050.0);

        // Refresh debt to get updated balance
        $debt = $debt->fresh();

        // Month 2: 4050 * 12% / 12 = 40.50 interest, 959.50 principal
        // Total principal: 950 + 959.50 = 1909.50
        $this->paymentService->recordPayment($debt, 1000, 1000, 2, '2025-02');
        $this->paymentService->updateDebtBalances();
        expect($debt->fresh()->balance)->toBe(3090.5);

        // Refresh debt again
        $debt = $debt->fresh();

        // Month 3: 3090.5 * 12% / 12 = 30.91 interest, 969.09 principal
        // Total principal: 1909.50 + 969.09 = 2878.59
        $this->paymentService->recordPayment($debt, 1000, 1000, 3, '2025-03');
        $this->paymentService->updateDebtBalances();
        expect($debt->fresh()->balance)->toBe(2121.41);

        // Progress: (5000 - 2121.41) / 5000 * 100 = 57.5718%
        expect(Payment::count())->toBe(3)
            ->and($this->paymentService->calculateOverallProgress())->toBe(57.5718);
    });

    it('handles payment deletion and balance restoration', function () {
        $debt = Debt::factory()->create([
            'balance' => 8000,
            'original_balance' => 10000,
        ]);

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'month_number' => 1,
            'actual_amount' => 2000,
        ]);

        expect($this->paymentService->deletePayment($debt->id, 1))->toBeTrue()
            ->and(Payment::count())->toBe(0)
            ->and($debt->fresh()->balance)->toBe(10000.0);
    });

    it('handles payment amount updates with balance recalculation', function () {
        $debt = Debt::factory()->create([
            'balance' => 8500,
            'original_balance' => 10000,
            'interest_rate' => 12.0,
        ]);

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'month_number' => 1,
            'actual_amount' => 1500,
        ]);

        $this->paymentService->updatePaymentAmount($debt->id, 1, 2500);

        // When updating, interest is recalculated: 8500 * 12% / 12 = 85.00
        // Principal: 2500 - 85 = 2415.00
        // Balance: 10000 - 2415 = 7585.00
        expect(Payment::first()->actual_amount)->toBe(2500.0)
            ->and($debt->fresh()->balance)->toBe(7585.0);
    });

    it('handles bulk month deletion', function () {
        $debt1 = Debt::factory()->create(['balance' => 8000, 'original_balance' => 10000, 'interest_rate' => 12.0]);
        $debt2 = Debt::factory()->create(['balance' => 4000, 'original_balance' => 5000, 'interest_rate' => 10.0]);

        Payment::factory()->create(['debt_id' => $debt1->id, 'month_number' => 1, 'actual_amount' => 2000]);
        Payment::factory()->create(['debt_id' => $debt2->id, 'month_number' => 1, 'actual_amount' => 1000]);
        Payment::factory()->create(['debt_id' => $debt1->id, 'month_number' => 2, 'actual_amount' => 500]);

        $deletedCount = $this->paymentService->deleteMonthPayments(1);

        // After deleting month 1, only month 2 payment remains for debt1
        // Debt1 month 2: 8000 * 12% / 12 = 80.00 interest, 500 - 80 = 420.00 principal
        // Debt1 balance: 10000 - 420 = 9580.00
        // Debt2 has no payments, balance = 5000
        expect($deletedCount)->toBe(2)
            ->and(Payment::count())->toBe(1)
            ->and($debt1->fresh()->balance)->toBe(9580.0)
            ->and($debt2->fresh()->balance)->toBe(5000.0);
    });
});

describe('Payment Tracking Edge Cases', function () {
    beforeEach(function () {
        $this->paymentService = new PaymentService;
    });

    it('handles overpayment preventing negative balance', function () {
        $debt = Debt::factory()->create([
            'balance' => 1000,
            'original_balance' => 1000,
        ]);

        $this->paymentService->recordPayment($debt, 500, 1500, 1, '2025-01');
        $this->paymentService->updateDebtBalances();

        expect($debt->fresh()->balance)->toBe(0.0);
    });

    it('handles final payment with rounding', function () {
        $debt = Debt::factory()->create([
            'balance' => 100.03,
            'original_balance' => 100.03,
            'interest_rate' => 12.0,
        ]);

        $this->paymentService->recordPayment($debt, 100.03, 100.03, 10, '2025-10');
        $this->paymentService->updateDebtBalances();

        // Interest: 100.03 * 12% / 12 = 1.00 (rounded)
        // Principal: 100.03 - 1.00 = 99.03
        // Balance: 100.03 - 99.03 = 1.00 (but minimum is 0)
        // Actually: 100.03 * 0.01 = 1.0003, rounded to 1.00
        // Principal: 99.03, Balance: 1.00
        expect($debt->fresh()->balance)->toBe(1.0);
    });

    it('tracks payments with different planned and actual amounts', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12.0,
        ]);

        $payment = $this->paymentService->recordPayment($debt, 1000, 1200, 1, '2025-01');
        $this->paymentService->updateDebtBalances();

        // Interest: 10000 * 12% / 12 = 100.00
        // Principal: 1200 - 100 = 1100.00
        // Balance: 10000 - 1100 = 8900.00
        expect($payment->planned_amount)->toBe(1000.0)
            ->and($payment->actual_amount)->toBe(1200.0)
            ->and($debt->fresh()->balance)->toBe(8900.0);
    });

    it('handles historical payment retrieval', function () {
        $debt = Debt::factory()->create();
        $pastMonth = now()->subMonth()->format('Y-m');
        $currentMonth = now()->format('Y-m');

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'payment_month' => $pastMonth,
            'actual_amount' => 500,
        ]);
        Payment::factory()->create([
            'debt_id' => $debt->id,
            'payment_month' => $currentMonth,
            'actual_amount' => 600,
        ]);

        $historical = $this->paymentService->getHistoricalPayments();

        expect($historical)->toHaveCount(1)
            ->and($historical[0]['payments'][0]['amount'])->toBe(500.0);
    });

    it('handles concurrent payments to multiple debts in same month', function () {
        $debt1 = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000, 'interest_rate' => 12.0]);
        $debt2 = Debt::factory()->create(['balance' => 5000, 'original_balance' => 5000, 'interest_rate' => 10.0]);
        $debt3 = Debt::factory()->create(['balance' => 3000, 'original_balance' => 3000, 'interest_rate' => 15.0]);

        $payments = [
            ['debt_id' => $debt1->id, 'planned_amount' => 1000, 'actual_amount' => 1000],
            ['debt_id' => $debt2->id, 'planned_amount' => 500, 'actual_amount' => 500],
            ['debt_id' => $debt3->id, 'planned_amount' => 300, 'actual_amount' => 300],
        ];

        $this->paymentService->recordMonthPayments($payments, '2025-01', 1);

        // Debt1: 10000 * 12% / 12 = 100.00 interest, 900 principal
        // Debt2: 5000 * 10% / 12 = 41.67 interest, 458.33 principal
        // Debt3: 3000 * 15% / 12 = 37.50 interest, 262.50 principal
        expect(Payment::count())->toBe(3)
            ->and($debt1->fresh()->balance)->toBe(9100.0)
            ->and($debt2->fresh()->balance)->toBe(4541.67)
            ->and($debt3->fresh()->balance)->toBe(2737.5)
            ->and($this->paymentService->isMonthFullyPaid(1, [$debt1->id, $debt2->id, $debt3->id]))->toBeTrue();
    });

    it('verifies payment existence across months', function () {
        $debt = Debt::factory()->create();

        $this->paymentService->recordPayment($debt, 500, 500, 1, '2025-01');
        $this->paymentService->recordPayment($debt, 500, 500, 3, '2025-03');

        expect($this->paymentService->paymentExists($debt->id, 1))->toBeTrue()
            ->and($this->paymentService->paymentExists($debt->id, 2))->toBeFalse()
            ->and($this->paymentService->paymentExists($debt->id, 3))->toBeTrue();
    });

    it('retrieves specific payment data', function () {
        $debt = Debt::factory()->create();

        $this->paymentService->recordPayment($debt, 500, 600, 1, '2025-01');
        $this->paymentService->recordPayment($debt, 700, 800, 2, '2025-02');

        $payment1 = $this->paymentService->getPayment($debt->id, 1);
        $payment2 = $this->paymentService->getPayment($debt->id, 2);

        expect($payment1->actual_amount)->toBe(600.0)
            ->and($payment2->actual_amount)->toBe(800.0)
            ->and($this->paymentService->getPayment($debt->id, 3))->toBeNull();
    });

    it('calculates progress accurately with partial payments', function () {
        $debt1 = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000, 'interest_rate' => 12.0]);
        $debt2 = Debt::factory()->create(['balance' => 5000, 'original_balance' => 5000, 'interest_rate' => 10.0]);

        $this->paymentService->recordPayment($debt1, 2500, 2500, 1, '2025-01');
        $this->paymentService->updateDebtBalances();

        // Interest: 10000 * 12% / 12 = 100.00
        // Principal: 2500 - 100 = 2400.00
        // Progress: 2400 / 15000 * 100 = 16.0%
        $progress = $this->paymentService->calculateOverallProgress();

        expect($progress)->toBe(16.0);
    });
});

describe('Payment Schedule Integration', function () {
    beforeEach(function () {
        $this->paymentService = new PaymentService;
        $this->calculationService = new DebtCalculationService($this->paymentService);
    });

    it('uses actual payment in schedule then projects from remaining', function () {
        $debt = Debt::factory()->create([
            'name' => 'Klarna',
            'original_balance' => 7404,
            'balance' => 750, // Already paid 6654, balance reflects this
            'interest_rate' => 12,
            'minimum_payment' => 800,
        ]);

        // Historical payment already recorded and reflected in balance
        // principal_paid = 7404 - 750 = 6654
        $debt->payments()->create([
            'planned_amount' => 750,
            'actual_amount' => 6654,
            'interest_paid' => 0,
            'principal_paid' => 6654,
            'payment_date' => now()->subMonth(),
            'month_number' => 1,
            'payment_month' => now()->subMonth()->format('Y-m'),
        ]);

        $debts = collect([$debt->fresh('payments')]);
        $result = $this->calculationService->generatePaymentSchedule($debts, 0);

        expect($result['schedule'])->not->toBeEmpty();

        // Month 1 uses actual payment amount
        $month1Payment = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Klarna');
        expect($month1Payment['amount'])->toBe(6654.0);
        // Remaining: original_balance - principal_paid = 7404 - 6654 = 750
        expect($month1Payment['remaining'])->toBe(750.0);
    });

    it('uses actual payment then projects future correctly', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test',
            'original_balance' => 1000,
            'balance' => 500, // Already paid 500, balance reflects this
            'interest_rate' => 12,
            'minimum_payment' => 100,
        ]);

        // Historical payment already recorded
        // principal_paid = 1000 - 500 = 500
        $debt->payments()->create([
            'planned_amount' => 100,
            'actual_amount' => 500,
            'interest_paid' => 0,
            'principal_paid' => 500,
            'payment_date' => now()->subMonth(),
            'month_number' => 1,
            'payment_month' => now()->subMonth()->format('Y-m'),
        ]);

        $debts = collect([$debt->fresh('payments')]);
        $result = $this->calculationService->generatePaymentSchedule($debts, 0);

        // Month 1 uses actual payment
        $month1 = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Test');
        expect($month1['amount'])->toBe(500.0);
        expect($month1['remaining'])->toBe(500.0); // original_balance - principal_paid

        if (isset($result['schedule'][1])) {
            $month2 = collect($result['schedule'][1]['payments'])->firstWhere('name', 'Test');
            $month1Remaining = $month1['remaining'];
            $expectedMonth2Payment = (float) min(100, $month1Remaining);

            expect($month2['amount'])->toBe($expectedMonth2Payment);
        }
    });

    it('calculates remaining balance correctly after multiple payments', function () {
        $debt = Debt::factory()->create([
            'original_balance' => 10000,
            'balance' => 10000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
        ]);

        // Payment 1: 10000 * 10% / 12 = 83.33 interest, 2000 - 83.33 = 1916.67 principal
        $debt->payments()->create([
            'planned_amount' => 500,
            'actual_amount' => 2000,
            'interest_paid' => 83.33,
            'principal_paid' => 1916.67,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        // Payment 2: (10000 - 1916.67) * 10% / 12 = 67.36 interest, 1500 - 67.36 = 1432.64 principal
        $debt->payments()->create([
            'planned_amount' => 500,
            'actual_amount' => 1500,
            'interest_paid' => 67.36,
            'principal_paid' => 1432.64,
            'payment_date' => now(),
            'month_number' => 2,
            'payment_month' => now()->addMonth()->format('Y-m'),
        ]);

        $this->paymentService->updateDebtBalances();

        // Total principal: 1916.67 + 1432.64 = 3349.31
        // Balance: 10000 - 3349.31 = 6650.69
        expect($debt->fresh()->balance)->toBe(6650.69);
    });

    it('handles debt payoff in schedule when balance reaches zero', function () {
        $debt = Debt::factory()->create([
            'original_balance' => 1000,
            'balance' => 100,
            'interest_rate' => 0,
            'minimum_payment' => 100,
        ]);

        // principal_paid = 1000 - 100 = 900
        $debt->payments()->create([
            'planned_amount' => 900,
            'actual_amount' => 900,
            'interest_paid' => 0,
            'principal_paid' => 900,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debts = collect([$debt->fresh('payments')]);
        $result = $this->calculationService->generatePaymentSchedule($debts, 0);

        expect($result['months'])->toBeLessThanOrEqual(3);
    });
});

describe('Payment Data Consistency', function () {
    beforeEach(function () {
        $this->paymentService = new PaymentService;
    });

    it('maintains data consistency across service methods', function () {
        $debt = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000, 'interest_rate' => 12.0]);

        $payment = $this->paymentService->recordPayment($debt, 1000, 1000, 1, '2025-01');
        $this->paymentService->updateDebtBalances();

        // Interest: 10000 * 12% / 12 = 100.00, Principal: 900.00
        expect($this->paymentService->paymentExists($debt->id, 1))->toBeTrue()
            ->and($this->paymentService->getPayment($debt->id, 1)->id)->toBe($payment->id)
            ->and($this->paymentService->getPaymentsForMonth('2025-01')->count())->toBe(1)
            ->and($debt->fresh()->balance)->toBe(9100.0);
    });

    it('ensures transaction atomicity for batch operations', function () {
        $debt1 = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000]);

        $payments = [
            ['debt_id' => $debt1->id, 'planned_amount' => 500, 'actual_amount' => 500],
            ['debt_id' => 99999, 'planned_amount' => 300, 'actual_amount' => 300],
        ];

        try {
            $this->paymentService->recordMonthPayments($payments, '2025-01', 1);
        } catch (\Exception $e) {
            // Expected
        }

        expect(Payment::count())->toBe(0)
            ->and($debt1->fresh()->balance)->toBe(10000.0);
    });

    it('maintains payment history integrity', function () {
        $debt = Debt::factory()->create();

        $this->paymentService->recordPayment($debt, 500, 500, 1, '2025-01');
        $this->paymentService->recordPayment($debt, 600, 600, 2, '2025-02');
        $this->paymentService->recordPayment($debt, 700, 700, 3, '2025-03');

        $this->paymentService->deletePayment($debt->id, 2);

        expect(Payment::count())->toBe(2)
            ->and($this->paymentService->paymentExists($debt->id, 1))->toBeTrue()
            ->and($this->paymentService->paymentExists($debt->id, 2))->toBeFalse()
            ->and($this->paymentService->paymentExists($debt->id, 3))->toBeTrue();
    });
});
