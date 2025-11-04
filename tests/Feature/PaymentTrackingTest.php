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
        ]);

        $payment = $this->paymentService->recordPayment(
            $debt,
            plannedAmount: 1000.0,
            actualAmount: 1000.0,
            monthNumber: 1,
            paymentMonth: '2025-01'
        );

        $this->paymentService->updateDebtBalances();

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and(Payment::count())->toBe(1)
            ->and($debt->fresh()->balance)->toBe(9000.0);
    });

    it('records multiple payments and updates all balances', function () {
        $debt1 = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);
        $debt2 = Debt::factory()->create([
            'balance' => 5000,
            'original_balance' => 5000,
        ]);

        $payments = [
            ['debt_id' => $debt1->id, 'planned_amount' => 1000, 'actual_amount' => 1000],
            ['debt_id' => $debt2->id, 'planned_amount' => 500, 'actual_amount' => 500],
        ];

        $this->paymentService->recordMonthPayments($payments, '2025-01', 1);

        expect(Payment::count())->toBe(2)
            ->and($debt1->fresh()->balance)->toBe(9000.0)
            ->and($debt2->fresh()->balance)->toBe(4500.0);
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

    it('integrates payments into payment schedule', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'original_balance' => 10000,
            'balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        $debt->payments()->create([
            'planned_amount' => 500,
            'actual_amount' => 600,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debts = collect([$debt->fresh('payments')]);
        $schedule = $this->calculationService->generatePaymentSchedule($debts, 0);

        expect($schedule['schedule'])->not->toBeEmpty()
            ->and($schedule['schedule'][0]['payments'][0]['amount'])->toBe(600.0);
    });
});

describe('Payment Tracking Workflows', function () {
    beforeEach(function () {
        $this->paymentService = new PaymentService;
    });

    it('handles complete payment workflow for single month', function () {
        $debt1 = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000]);
        $debt2 = Debt::factory()->create(['balance' => 5000, 'original_balance' => 5000]);

        $payments = [
            ['debt_id' => $debt1->id, 'planned_amount' => 1000, 'actual_amount' => 1000],
            ['debt_id' => $debt2->id, 'planned_amount' => 500, 'actual_amount' => 500],
        ];

        $recordedPayments = $this->paymentService->recordMonthPayments($payments, '2025-01', 1);

        expect($recordedPayments)->toHaveCount(2)
            ->and($debt1->fresh()->balance)->toBe(9000.0)
            ->and($debt2->fresh()->balance)->toBe(4500.0)
            ->and($this->paymentService->paymentExists($debt1->id, 1))->toBeTrue()
            ->and($this->paymentService->paymentExists($debt2->id, 1))->toBeTrue()
            ->and($this->paymentService->isMonthFullyPaid(1, [$debt1->id, $debt2->id]))->toBeTrue();
    });

    it('handles progressive multi-month payment tracking', function () {
        $debt = Debt::factory()->create([
            'balance' => 5000,
            'original_balance' => 5000,
        ]);

        $this->paymentService->recordPayment($debt, 1000, 1000, 1, '2025-01');
        $this->paymentService->updateDebtBalances();
        expect($debt->fresh()->balance)->toBe(4000.0);

        $this->paymentService->recordPayment($debt, 1000, 1000, 2, '2025-02');
        $this->paymentService->updateDebtBalances();
        expect($debt->fresh()->balance)->toBe(3000.0);

        $this->paymentService->recordPayment($debt, 1000, 1000, 3, '2025-03');
        $this->paymentService->updateDebtBalances();
        expect($debt->fresh()->balance)->toBe(2000.0);

        expect(Payment::count())->toBe(3)
            ->and($this->paymentService->calculateOverallProgress())->toBe(60.0);
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
        ]);

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'month_number' => 1,
            'actual_amount' => 1500,
        ]);

        $this->paymentService->updatePaymentAmount($debt->id, 1, 2500);

        expect(Payment::first()->actual_amount)->toBe(2500.0)
            ->and($debt->fresh()->balance)->toBe(7500.0);
    });

    it('handles bulk month deletion', function () {
        $debt1 = Debt::factory()->create(['balance' => 8000, 'original_balance' => 10000]);
        $debt2 = Debt::factory()->create(['balance' => 4000, 'original_balance' => 5000]);

        Payment::factory()->create(['debt_id' => $debt1->id, 'month_number' => 1, 'actual_amount' => 2000]);
        Payment::factory()->create(['debt_id' => $debt2->id, 'month_number' => 1, 'actual_amount' => 1000]);
        Payment::factory()->create(['debt_id' => $debt1->id, 'month_number' => 2, 'actual_amount' => 500]);

        $deletedCount = $this->paymentService->deleteMonthPayments(1);

        expect($deletedCount)->toBe(2)
            ->and(Payment::count())->toBe(1)
            ->and($debt1->fresh()->balance)->toBe(9500.0)
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
            'original_balance' => 1000,
        ]);

        $this->paymentService->recordPayment($debt, 100.03, 100.03, 10, '2025-10');
        $this->paymentService->updateDebtBalances();

        expect($debt->fresh()->balance)->toBe(0.0);
    });

    it('tracks payments with different planned and actual amounts', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $payment = $this->paymentService->recordPayment($debt, 1000, 1200, 1, '2025-01');
        $this->paymentService->updateDebtBalances();

        expect($payment->planned_amount)->toBe(1000.0)
            ->and($payment->actual_amount)->toBe(1200.0)
            ->and($debt->fresh()->balance)->toBe(8800.0);
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
        $debt1 = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000]);
        $debt2 = Debt::factory()->create(['balance' => 5000, 'original_balance' => 5000]);
        $debt3 = Debt::factory()->create(['balance' => 3000, 'original_balance' => 3000]);

        $payments = [
            ['debt_id' => $debt1->id, 'planned_amount' => 1000, 'actual_amount' => 1000],
            ['debt_id' => $debt2->id, 'planned_amount' => 500, 'actual_amount' => 500],
            ['debt_id' => $debt3->id, 'planned_amount' => 300, 'actual_amount' => 300],
        ];

        $this->paymentService->recordMonthPayments($payments, '2025-01', 1);

        expect(Payment::count())->toBe(3)
            ->and($debt1->fresh()->balance)->toBe(9000.0)
            ->and($debt2->fresh()->balance)->toBe(4500.0)
            ->and($debt3->fresh()->balance)->toBe(2700.0)
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
        $debt1 = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000]);
        $debt2 = Debt::factory()->create(['balance' => 5000, 'original_balance' => 5000]);

        $this->paymentService->recordPayment($debt1, 2500, 2500, 1, '2025-01');
        $this->paymentService->updateDebtBalances();

        $progress = $this->paymentService->calculateOverallProgress();

        expect($progress)->toBeGreaterThan(16.0)->toBeLessThan(17.0);
    });
});

describe('Payment Schedule Integration', function () {
    beforeEach(function () {
        $this->paymentService = new PaymentService;
        $this->calculationService = new DebtCalculationService($this->paymentService);
    });

    it('generates schedule with historical payments integrated', function () {
        $debt = Debt::factory()->create([
            'name' => 'Klarna',
            'original_balance' => 7404,
            'balance' => 750,
            'interest_rate' => 12,
            'minimum_payment' => 800,
        ]);

        $debt->payments()->create([
            'planned_amount' => 750,
            'actual_amount' => 6654,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debts = collect([$debt->fresh('payments')]);
        $result = $this->calculationService->generatePaymentSchedule($debts, 0);

        expect($result['schedule'])->not->toBeEmpty();

        $month1Payment = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Klarna');
        expect($month1Payment['amount'])->toBe(6654.0);

        $expectedInterest = round(7404 * (12 / 100) / 12, 2);
        $expectedRemaining = round(7404 + $expectedInterest - 6654, 2);
        expect($month1Payment['remaining'])->toBe($expectedRemaining);
    });

    it('continues schedule correctly after actual payment', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test',
            'original_balance' => 1000,
            'balance' => 500,
            'interest_rate' => 12,
            'minimum_payment' => 100,
        ]);

        $debt->payments()->create([
            'planned_amount' => 100,
            'actual_amount' => 500,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debts = collect([$debt->fresh('payments')]);
        $result = $this->calculationService->generatePaymentSchedule($debts, 0);

        $month1 = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Test');
        expect($month1['amount'])->toBe(500.0);

        if (isset($result['schedule'][1])) {
            $month2 = collect($result['schedule'][1]['payments'])->firstWhere('name', 'Test');
            $month1Remaining = $month1['remaining'];
            $expectedMonth2Payment = min(100, $month1Remaining);

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

        $debt->payments()->create([
            'planned_amount' => 500,
            'actual_amount' => 2000,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debt->payments()->create([
            'planned_amount' => 500,
            'actual_amount' => 1500,
            'payment_date' => now(),
            'month_number' => 2,
            'payment_month' => now()->addMonth()->format('Y-m'),
        ]);

        $this->paymentService->updateDebtBalances();

        expect($debt->fresh()->balance)->toBe(6500.0);
    });

    it('handles debt payoff in schedule when balance reaches zero', function () {
        $debt = Debt::factory()->create([
            'original_balance' => 1000,
            'balance' => 100,
            'interest_rate' => 0,
            'minimum_payment' => 100,
        ]);

        $debt->payments()->create([
            'planned_amount' => 900,
            'actual_amount' => 900,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debts = collect([$debt->fresh('payments')]);
        $result = $this->calculationService->generatePaymentSchedule($debts, 0);

        expect($result['months'])->toBeLessThanOrEqual(2);
    });
});

describe('Payment Data Consistency', function () {
    beforeEach(function () {
        $this->paymentService = new PaymentService;
    });

    it('maintains data consistency across service methods', function () {
        $debt = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000]);

        $payment = $this->paymentService->recordPayment($debt, 1000, 1000, 1, '2025-01');
        $this->paymentService->updateDebtBalances();

        expect($this->paymentService->paymentExists($debt->id, 1))->toBeTrue()
            ->and($this->paymentService->getPayment($debt->id, 1)->id)->toBe($payment->id)
            ->and($this->paymentService->getPaymentsForMonth('2025-01')->count())->toBe(1)
            ->and($debt->fresh()->balance)->toBe(9000.0);
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
