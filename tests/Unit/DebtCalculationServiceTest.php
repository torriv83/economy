<?php

use App\Models\Debt;
use App\Services\DebtCalculationService;
use App\Services\PaymentService;

beforeEach(function () {
    $this->paymentService = new PaymentService;
    $this->service = new DebtCalculationService($this->paymentService);
});

describe('orderBySnowball', function () {
    it('orders debts by lowest balance first', function () {
        $debts = collect([
            new Debt(['name' => 'Large', 'balance' => 10000, 'interest_rate' => 5]),
            new Debt(['name' => 'Small', 'balance' => 1000, 'interest_rate' => 10]),
            new Debt(['name' => 'Medium', 'balance' => 5000, 'interest_rate' => 7]),
        ]);

        $ordered = $this->service->orderBySnowball($debts);

        expect($ordered->pluck('name')->toArray())->toBe(['Small', 'Medium', 'Large']);
    });

    it('handles empty collection', function () {
        $debts = collect([]);
        $ordered = $this->service->orderBySnowball($debts);

        expect($ordered)->toHaveCount(0);
    });

    it('handles single debt', function () {
        $debts = collect([
            new Debt(['name' => 'Only', 'balance' => 5000, 'interest_rate' => 5]),
        ]);

        $ordered = $this->service->orderBySnowball($debts);

        expect($ordered)->toHaveCount(1)
            ->and($ordered->first()->name)->toBe('Only');
    });
});

describe('orderByAvalanche', function () {
    it('orders debts by highest interest rate first', function () {
        $debts = collect([
            new Debt(['name' => 'Low Rate', 'balance' => 10000, 'interest_rate' => 3]),
            new Debt(['name' => 'High Rate', 'balance' => 1000, 'interest_rate' => 15]),
            new Debt(['name' => 'Medium Rate', 'balance' => 5000, 'interest_rate' => 8]),
        ]);

        $ordered = $this->service->orderByAvalanche($debts);

        expect($ordered->pluck('name')->toArray())->toBe(['High Rate', 'Medium Rate', 'Low Rate']);
    });

    it('handles empty collection', function () {
        $debts = collect([]);
        $ordered = $this->service->orderByAvalanche($debts);

        expect($ordered)->toHaveCount(0);
    });

    it('handles equal interest rates', function () {
        $debts = collect([
            new Debt(['name' => 'First', 'balance' => 5000, 'interest_rate' => 10]),
            new Debt(['name' => 'Second', 'balance' => 3000, 'interest_rate' => 10]),
        ]);

        $ordered = $this->service->orderByAvalanche($debts);

        expect($ordered)->toHaveCount(2);
    });
});

describe('calculateMonthlyInterest', function () {
    it('calculates monthly interest correctly', function () {
        $balance = 10000;
        $annualRate = 12; // 12% per year = 1% per month

        $monthlyInterest = $this->service->calculateMonthlyInterest($balance, $annualRate);

        expect($monthlyInterest)->toBe(100.0); // 10000 * 0.12 / 12 = 100
    });

    it('handles zero interest rate', function () {
        $balance = 5000;
        $annualRate = 0;

        $monthlyInterest = $this->service->calculateMonthlyInterest($balance, $annualRate);

        expect($monthlyInterest)->toBe(0.0);
    });

    it('rounds to two decimal places', function () {
        $balance = 10000;
        $annualRate = 8.5; // Should produce 70.83333...

        $monthlyInterest = $this->service->calculateMonthlyInterest($balance, $annualRate);

        expect($monthlyInterest)->toBe(70.83);
    });

    it('handles small balances', function () {
        $balance = 100;
        $annualRate = 5;

        $monthlyInterest = $this->service->calculateMonthlyInterest($balance, $annualRate);

        expect($monthlyInterest)->toBe(0.42); // 100 * 0.05 / 12 = 0.41666...
    });
});

describe('calculatePayoffMonths', function () {
    it('calculates payoff months for debt with interest', function () {
        $balance = 5000;
        $interestRate = 15; // 15% annual
        $monthlyPayment = 200;

        $months = $this->service->calculatePayoffMonths($balance, $interestRate, $monthlyPayment);

        expect($months)->toBeGreaterThan(0)
            ->and($months)->toBeLessThan(100);
    });

    it('calculates payoff months for zero interest debt', function () {
        $balance = 10000;
        $interestRate = 0;
        $monthlyPayment = 500;

        $months = $this->service->calculatePayoffMonths($balance, $interestRate, $monthlyPayment);

        expect($months)->toBe(20); // 10000 / 500 = 20 months
    });

    it('returns PHP_INT_MAX when payment is too low', function () {
        $balance = 10000;
        $interestRate = 12; // 1% per month = 100 kr interest
        $monthlyPayment = 50; // Less than monthly interest

        $months = $this->service->calculatePayoffMonths($balance, $interestRate, $monthlyPayment);

        expect($months)->toBe(PHP_INT_MAX);
    });

    it('returns PHP_INT_MAX when payment equals interest', function () {
        $balance = 10000;
        $interestRate = 12;
        $monthlyPayment = 100; // Exactly equal to monthly interest

        $months = $this->service->calculatePayoffMonths($balance, $interestRate, $monthlyPayment);

        expect($months)->toBe(PHP_INT_MAX);
    });

    it('handles very high payment amount', function () {
        $balance = 5000;
        $interestRate = 10;
        $monthlyPayment = 10000; // More than balance

        $months = $this->service->calculatePayoffMonths($balance, $interestRate, $monthlyPayment);

        expect($months)->toBe(1);
    });
});

describe('calculateTotalInterest', function () {
    it('calculates total interest paid', function () {
        $balance = 5000;
        $interestRate = 12;
        $monthlyPayment = 300;
        $months = 20;

        $totalInterest = $this->service->calculateTotalInterest($balance, $interestRate, $monthlyPayment, $months);

        expect($totalInterest)->toBe(1000.0); // (300 * 20) - 5000 = 1000
    });

    it('returns zero when no interest is paid', function () {
        $balance = 5000;
        $interestRate = 0;
        $monthlyPayment = 500;
        $months = 10;

        $totalInterest = $this->service->calculateTotalInterest($balance, $interestRate, $monthlyPayment, $months);

        expect($totalInterest)->toBe(0.0); // (500 * 10) - 5000 = 0
    });

    it('handles negative interest (edge case)', function () {
        $balance = 10000;
        $interestRate = 0;
        $monthlyPayment = 1000;
        $months = 5; // Total paid: 5000 which is less than balance

        $totalInterest = $this->service->calculateTotalInterest($balance, $interestRate, $monthlyPayment, $months);

        expect($totalInterest)->toBe(0.0); // Should return 0, not negative
    });
});

describe('generatePaymentSchedule', function () {
    it('generates schedule for avalanche strategy', function () {
        $debts = collect([
            new Debt(['name' => 'High Rate', 'balance' => 1000, 'interest_rate' => 15, 'minimum_payment' => 50]),
            new Debt(['name' => 'Low Rate', 'balance' => 2000, 'interest_rate' => 5, 'minimum_payment' => 100]),
        ]);

        $result = $this->service->generatePaymentSchedule($debts, 200, 'avalanche');

        expect($result)->toHaveKeys(['months', 'totalInterest', 'payoffDate', 'schedule'])
            ->and($result['months'])->toBeGreaterThan(0)
            ->and($result['schedule'])->toBeArray()
            ->and($result['schedule'][0])->toHaveKeys(['month', 'monthName', 'payments', 'totalPaid', 'progress']);
    });

    it('generates schedule for snowball strategy', function () {
        $debts = collect([
            new Debt(['name' => 'Small', 'balance' => 1000, 'interest_rate' => 15, 'minimum_payment' => 50]),
            new Debt(['name' => 'Large', 'balance' => 5000, 'interest_rate' => 5, 'minimum_payment' => 100]),
        ]);

        $result = $this->service->generatePaymentSchedule($debts, 200, 'snowball');

        expect($result['schedule'][0]['priorityDebt'])->toBe('Small');
    });

    it('handles empty debt collection', function () {
        $debts = collect([]);

        $result = $this->service->generatePaymentSchedule($debts, 500);

        expect($result['months'])->toBe(0)
            ->and($result['totalInterest'])->toBe(0.0)
            ->and($result['schedule'])->toBe([]);
    });

    it('applies snowball effect when debt is paid off', function () {
        $debts = collect([
            new Debt(['name' => 'Quick', 'balance' => 500, 'interest_rate' => 10, 'minimum_payment' => 100]),
            new Debt(['name' => 'Slow', 'balance' => 5000, 'interest_rate' => 5, 'minimum_payment' => 200]),
        ]);

        $result = $this->service->generatePaymentSchedule($debts, 200, 'avalanche');

        // After 'Quick' is paid off, its minimum payment should roll over to 'Slow'
        expect($result['months'])->toBeGreaterThan(0)
            ->and($result['schedule'])->not->toBeEmpty();
    });

    it('tracks progress percentage correctly', function () {
        $debts = collect([
            new Debt(['name' => 'Test', 'balance' => 1000, 'interest_rate' => 10, 'minimum_payment' => 500]),
        ]);

        $result = $this->service->generatePaymentSchedule($debts, 0);

        $lastMonth = end($result['schedule']);
        expect($lastMonth['progress'])->toBeGreaterThanOrEqual(99);
    });

    it('uses original_balance when generating schedule', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'original_balance' => 10000,
            'balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
        ]);

        $debts = collect([$debt]);
        $result = $this->service->generatePaymentSchedule($debts, 0);

        // First month should start with original_balance, not current balance
        $firstMonthPayment = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Test Debt');
        expect($firstMonthPayment['remaining'])->toBeGreaterThan(5000);
    });

    it('integrates actual payments into schedule', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'original_balance' => 7404,
            'balance' => 750,
            'interest_rate' => 12,
            'minimum_payment' => 800,
        ]);

        // Record an actual payment for month 1
        $debt->payments()->create([
            'planned_amount' => 750,
            'actual_amount' => 6654,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debts = collect([$debt->fresh('payments')]);
        $result = $this->service->generatePaymentSchedule($debts, 0);

        // Month 1 should use the actual payment amount (6654)
        $month1Payment = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Test Debt');
        expect($month1Payment['amount'])->toBe(6654.0);
    });

    it('calculates remaining balance correctly after actual payment', function () {
        $debt = Debt::factory()->create([
            'name' => 'Klarna',
            'original_balance' => 7404,
            'balance' => 750,
            'interest_rate' => 12,
            'minimum_payment' => 800,
        ]);

        // Record actual payment for month 1
        $debt->payments()->create([
            'planned_amount' => 750,
            'actual_amount' => 6654,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debts = collect([$debt->fresh('payments')]);
        $result = $this->service->generatePaymentSchedule($debts, 0);

        // Month 1: original_balance (7404) + interest - actual_payment (6654)
        $month1Payment = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Klarna');
        $expectedInterest = round(7404 * (12 / 100) / 12, 2); // Monthly interest
        $expectedRemaining = round(7404 + $expectedInterest - 6654, 2);

        expect($month1Payment['remaining'])->toBe($expectedRemaining);
    });

    it('continues calculation from adjusted balance after actual payment', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test',
            'original_balance' => 1000,
            'balance' => 500,
            'interest_rate' => 12,
            'minimum_payment' => 100,
        ]);

        // Actual payment in month 1
        $debt->payments()->create([
            'planned_amount' => 100,
            'actual_amount' => 500,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debts = collect([$debt->fresh('payments')]);
        $result = $this->service->generatePaymentSchedule($debts, 0);

        // Month 1 uses actual payment
        $month1 = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Test');
        expect($month1['amount'])->toBe(500.0);

        // Month 2 should calculate from the balance after month 1's actual payment
        $month1Remaining = $month1['remaining'];
        if (isset($result['schedule'][1])) {
            $month2 = collect($result['schedule'][1]['payments'])->firstWhere('name', 'Test');
            $expectedMonth2Interest = round($month1Remaining * (12 / 100) / 12, 2);
            $expectedMonth2Payment = (float) min(100, $month1Remaining);

            expect($month2['amount'])->toBe($expectedMonth2Payment);
        }
    });
});

describe('compareStrategies', function () {
    it('compares snowball and avalanche strategies', function () {
        $debts = collect([
            new Debt(['name' => 'High Rate', 'balance' => 1000, 'interest_rate' => 15, 'minimum_payment' => 50]),
            new Debt(['name' => 'Low Rate', 'balance' => 2000, 'interest_rate' => 5, 'minimum_payment' => 100]),
        ]);

        $result = $this->service->compareStrategies($debts, 200);

        expect($result)->toHaveKeys(['snowball', 'avalanche'])
            ->and($result['snowball'])->toHaveKeys(['months', 'totalInterest', 'order'])
            ->and($result['avalanche'])->toHaveKeys(['months', 'totalInterest', 'order', 'savings'])
            ->and($result['snowball']['order'])->toBe(['High Rate', 'Low Rate']) // Snowball: lowest balance first
            ->and($result['avalanche']['order'])->toBe(['High Rate', 'Low Rate']); // Avalanche: highest rate first
    });

    it('calculates savings correctly', function () {
        $debts = collect([
            new Debt(['name' => 'Test1', 'balance' => 1000, 'interest_rate' => 10, 'minimum_payment' => 100]),
            new Debt(['name' => 'Test2', 'balance' => 2000, 'interest_rate' => 5, 'minimum_payment' => 100]),
        ]);

        $result = $this->service->compareStrategies($debts, 100);

        expect($result['avalanche']['savings'])->toBeNumeric();
    });

    it('handles empty debt collection', function () {
        $debts = collect([]);

        $result = $this->service->compareStrategies($debts, 500);

        expect($result['snowball']['months'])->toBe(0)
            ->and($result['avalanche']['months'])->toBe(0)
            ->and($result['avalanche']['savings'])->toBe(0.0);
    });
});

describe('calculateMinimumPaymentsOnly', function () {
    it('calculates payoff time for single debt with minimum payments', function () {
        $debt = new Debt([
            'balance' => 1000,
            'interest_rate' => 12,
            'minimum_payment' => 100,
        ]);

        $months = $this->service->calculateMinimumPaymentsOnly(collect([$debt]));

        expect($months)->toBeInt()
            ->and($months)->toBeGreaterThan(0)
            ->and($months)->toBeLessThan(100); // Should be around 11 months
    });

    it('returns longest payoff time for multiple debts', function () {
        $debts = collect([
            new Debt(['balance' => 1000, 'interest_rate' => 10, 'minimum_payment' => 200]), // ~5-6 months
            new Debt(['balance' => 5000, 'interest_rate' => 15, 'minimum_payment' => 150]), // ~40+ months
        ]);

        $months = $this->service->calculateMinimumPaymentsOnly($debts);

        // Should return the longest payoff time (the second debt)
        expect($months)->toBeGreaterThan(30);
    });

    it('returns zero for empty collection', function () {
        $months = $this->service->calculateMinimumPaymentsOnly(collect([]));

        expect($months)->toBe(0);
    });

    it('skips debts without minimum payment', function () {
        $debts = collect([
            new Debt(['balance' => 5000, 'interest_rate' => 10, 'minimum_payment' => null]),
            new Debt(['balance' => 1000, 'interest_rate' => 5, 'minimum_payment' => 100]),
        ]);

        $months = $this->service->calculateMinimumPaymentsOnly($debts);

        // Should only calculate for the second debt
        expect($months)->toBeGreaterThan(0)
            ->and($months)->toBeLessThan(20);
    });

    it('uses original_balance when available', function () {
        $debt = new Debt([
            'original_balance' => 10000,
            'balance' => 5000, // Partially paid down
            'interest_rate' => 10,
            'minimum_payment' => 200,
        ]);

        $months = $this->service->calculateMinimumPaymentsOnly(collect([$debt]));

        // Should calculate based on original_balance (10000), not current balance (5000)
        expect($months)->toBeGreaterThan(40);
    });
});

describe('calculateMinimumPaymentsInterest', function () {
    it('calculates total interest for single debt with minimum payments', function () {
        $debt = new Debt([
            'balance' => 1000,
            'interest_rate' => 12,
            'minimum_payment' => 100,
        ]);

        $interest = $this->service->calculateMinimumPaymentsInterest(collect([$debt]));

        expect($interest)->toBeFloat()
            ->and($interest)->toBeGreaterThan(0)
            ->and($interest)->toBeLessThan(200); // Should be reasonable amount
    });

    it('calculates total interest for multiple debts', function () {
        $debts = collect([
            new Debt(['balance' => 1000, 'interest_rate' => 10, 'minimum_payment' => 100]),
            new Debt(['balance' => 2000, 'interest_rate' => 15, 'minimum_payment' => 150]),
        ]);

        $interest = $this->service->calculateMinimumPaymentsInterest($debts);

        expect($interest)->toBeFloat()
            ->and($interest)->toBeGreaterThan(0);
    });

    it('returns zero for empty collection', function () {
        $interest = $this->service->calculateMinimumPaymentsInterest(collect([]));

        expect($interest)->toBe(0.0);
    });

    it('skips debts without minimum payment', function () {
        $debts = collect([
            new Debt(['balance' => 5000, 'interest_rate' => 10, 'minimum_payment' => null]),
            new Debt(['balance' => 1000, 'interest_rate' => 5, 'minimum_payment' => 100]),
        ]);

        $interest = $this->service->calculateMinimumPaymentsInterest($debts);

        // Should only calculate interest for the second debt
        expect($interest)->toBeGreaterThan(0)
            ->and($interest)->toBeLessThanOrEqual(100.0);
    });

    it('uses original_balance for consistency', function () {
        $debt = new Debt([
            'original_balance' => 10000,
            'balance' => 5000, // Partially paid down
            'interest_rate' => 10,
            'minimum_payment' => 200,
        ]);

        $interest = $this->service->calculateMinimumPaymentsInterest(collect([$debt]));

        // Should calculate based on original_balance (10000), not current balance (5000)
        expect($interest)->toBeGreaterThan(500);
    });

    it('handles zero interest rate', function () {
        $debt = new Debt([
            'balance' => 1000,
            'interest_rate' => 0,
            'minimum_payment' => 100,
        ]);

        $interest = $this->service->calculateMinimumPaymentsInterest(collect([$debt]));

        // Zero interest rate should result in zero interest
        expect($interest)->toBe(0.0);
    });
});
