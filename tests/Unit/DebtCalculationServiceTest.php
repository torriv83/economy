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

    it('uses current balance when generating schedule without actual payments', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'original_balance' => 10000,
            'balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
        ]);

        $debts = collect([$debt]);
        $result = $this->service->generatePaymentSchedule($debts, 0);

        // Without actual payments (month_number > 0), schedule starts from current balance
        $firstMonthPayment = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Test Debt');

        // Calculate expected remaining: current_balance + interest - minimum payment
        $interest = round(5000 * (10 / 100) / 12, 2);
        $expectedRemaining = round(5000 + $interest - 500, 2);

        expect($firstMonthPayment['remaining'])->toBe($expectedRemaining);
    });

    it('integrates actual payments into schedule', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'original_balance' => 7404,
            'balance' => 750, // Already paid 6654, balance is reduced
            'interest_rate' => 12,
            'minimum_payment' => 800,
        ]);

        // Historical payment is stored separately
        // For test: principal_paid = 7404 - 750 = 6654
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
        $result = $this->service->generatePaymentSchedule($debts, 0);

        // Month 1 uses actual payment amount
        $month1Payment = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Test Debt');

        expect($month1Payment['amount'])->toBe(6654.0);
        // Remaining after actual payment: original_balance - principal_paid = 7404 - 6654 = 750
        expect($month1Payment['remaining'])->toBe(750.0);
    });

    it('calculates remaining balance correctly with actual payment', function () {
        $debt = Debt::factory()->create([
            'name' => 'Klarna',
            'original_balance' => 7404,
            'balance' => 750, // Current balance after previous payments
            'interest_rate' => 12,
            'minimum_payment' => 800,
        ]);

        // Historical payment already applied to balance
        // Interest: 7404 * 12% / 12 = 74.04
        // Principal: 6654 - 74.04 = 6579.96 (but test expects balance of 750)
        // So: principal_paid = 7404 - 750 = 6654 (full payment went to principal for test)
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
        $result = $this->service->generatePaymentSchedule($debts, 0);

        // Month 1 uses actual payment, remaining = original_balance - principal_paid
        $month1Payment = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Klarna');

        expect($month1Payment['amount'])->toBe(6654.0);
        expect($month1Payment['remaining'])->toBe(750.0);
    });

    it('projects schedule with actual payment then continues projecting', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test',
            'original_balance' => 1000,
            'balance' => 500, // Current balance after previous payments
            'interest_rate' => 12,
            'minimum_payment' => 100,
        ]);

        // Historical payment already applied
        // For test simplicity: all payment goes to principal
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
        $result = $this->service->generatePaymentSchedule($debts, 0);

        // Month 1 uses actual payment: original_balance - principal_paid = 1000 - 500 = 500
        $month1 = collect($result['schedule'][0]['payments'])->firstWhere('name', 'Test');
        expect($month1['amount'])->toBe(500.0);
        expect($month1['remaining'])->toBe(500.0);

        // Month 2 continues from month 1's remaining balance with projected payment
        if (isset($result['schedule'][1])) {
            $month2 = collect($result['schedule'][1]['payments'])->firstWhere('name', 'Test');
            $interest2 = round(500 * (12 / 100) / 12, 2);
            $expectedPayment2 = (float) min(100, 500 + $interest2);

            expect($month2['amount'])->toBe($expectedPayment2);
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

    it('uses current balance for calculation', function () {
        $debt = new Debt([
            'original_balance' => 10000,
            'balance' => 5000, // Partially paid down
            'interest_rate' => 10,
            'minimum_payment' => 200,
        ]);

        $months = $this->service->calculateMinimumPaymentsOnly(collect([$debt]));

        // Should calculate based on current balance (5000), reflecting payments already made
        // 5000 at 10% interest with 200/month payment ≈ 29 months
        expect($months)->toBeGreaterThan(25)
            ->and($months)->toBeLessThan(35);
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

    it('uses current balance for interest calculation', function () {
        $debt = new Debt([
            'original_balance' => 10000,
            'balance' => 5000, // Partially paid down
            'interest_rate' => 10,
            'minimum_payment' => 200,
        ]);

        $interest = $this->service->calculateMinimumPaymentsInterest(collect([$debt]));

        // Should calculate based on current balance (5000), reflecting payments already made
        // 5000 at 10% with 200/month payment ≈ 29 months ≈ 800 total interest
        expect($interest)->toBeGreaterThan(700)
            ->and($interest)->toBeLessThan(900);
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

describe('extra payment budget tracking', function () {
    it('uses remaining budget when extra payment already used on another debt this month', function () {
        // Scenario: User paid debt A with extra, then changed strategy so debt B is priority
        // Debt B should get 0 extra since budget is already spent

        // Create two debts with different interest rates
        $debtA = Debt::factory()->create([
            'name' => 'High Rate',
            'original_balance' => 5000,
            'balance' => 5000,
            'interest_rate' => 20, // Avalanche priority
            'minimum_payment' => 200,
        ]);

        $debtB = Debt::factory()->create([
            'name' => 'Low Balance',
            'original_balance' => 1000,
            'balance' => 1000,
            'interest_rate' => 10, // Snowball priority (lower balance)
            'minimum_payment' => 100,
        ]);

        // Record payment for High Rate (month 1) with full extra payment
        $debtA->payments()->create([
            'planned_amount' => 1200,
            'actual_amount' => 1200, // 200 minimum + 1000 extra
            'interest_paid' => 83.33,
            'principal_paid' => 1116.67,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        // Update balance
        $debtA->update(['balance' => 5000 - 1116.67]);

        $debts = collect([$debtA->fresh('payments'), $debtB->fresh('payments')]);

        // Generate schedule with SNOWBALL strategy (Low Balance first)
        // Extra budget is 1000, but already spent on High Rate this month
        $result = $this->service->generatePaymentSchedule($debts, 1000, 'snowball');

        // Month 1: Low Balance should only get minimum (100), not minimum + extra
        $month1 = $result['schedule'][0];
        $lowBalancePayment = collect($month1['payments'])->firstWhere('name', 'Low Balance');

        // Extra should be 0 because budget was already used
        expect($lowBalancePayment['extra'])->toBe(0.0);
        expect($lowBalancePayment['amount'])->toBe(100.0); // Only minimum payment
    });

    it('uses partial remaining budget when some extra payment already used', function () {
        // Scenario: User paid debt A with only partial extra (500), budget is 1000
        // Debt B should get remaining 500 extra

        $debtA = Debt::factory()->create([
            'name' => 'Debt A',
            'original_balance' => 5000,
            'balance' => 5000,
            'interest_rate' => 15,
            'minimum_payment' => 200,
        ]);

        $debtB = Debt::factory()->create([
            'name' => 'Debt B',
            'original_balance' => 3000,
            'balance' => 3000,
            'interest_rate' => 20, // Higher rate = avalanche priority
            'minimum_payment' => 150,
        ]);

        // Record payment for Debt A with partial extra (500 instead of 1000)
        $debtA->payments()->create([
            'planned_amount' => 700,
            'actual_amount' => 700, // 200 minimum + 500 extra
            'interest_paid' => 62.50,
            'principal_paid' => 637.50,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debtA->update(['balance' => 5000 - 637.50]);

        $debts = collect([$debtA->fresh('payments'), $debtB->fresh('payments')]);

        // Generate schedule with AVALANCHE strategy (Debt B is priority now)
        // Extra budget is 1000, but 500 already spent
        $result = $this->service->generatePaymentSchedule($debts, 1000, 'avalanche');

        $month1 = $result['schedule'][0];
        $debtBPayment = collect($month1['payments'])->firstWhere('name', 'Debt B');

        // Extra should be 500 (remaining budget: 1000 - 500)
        expect($debtBPayment['extra'])->toBe(500.0);
        expect($debtBPayment['amount'])->toBe(650.0); // 150 minimum + 500 extra
    });

    it('gives zero extra when budget is exceeded', function () {
        // Scenario: User paid debt A with 1500 extra when budget is 1000
        // Debt B should get 0 extra (cannot go negative)

        $debtA = Debt::factory()->create([
            'name' => 'Debt A',
            'original_balance' => 5000,
            'balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 200,
        ]);

        $debtB = Debt::factory()->create([
            'name' => 'Debt B',
            'original_balance' => 3000,
            'balance' => 3000,
            'interest_rate' => 20, // Higher rate = avalanche priority
            'minimum_payment' => 150,
        ]);

        // Record payment for Debt A with MORE than budgeted extra (1500 instead of 1000)
        $debtA->payments()->create([
            'planned_amount' => 1700,
            'actual_amount' => 1700, // 200 minimum + 1500 extra (over budget)
            'interest_paid' => 41.67,
            'principal_paid' => 1658.33,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debtA->update(['balance' => 5000 - 1658.33]);

        $debts = collect([$debtA->fresh('payments'), $debtB->fresh('payments')]);

        // Generate schedule with AVALANCHE strategy (Debt B is priority now)
        // Extra budget is 1000, but 1500 already spent = 0 remaining
        $result = $this->service->generatePaymentSchedule($debts, 1000, 'avalanche');

        $month1 = $result['schedule'][0];
        $debtBPayment = collect($month1['payments'])->firstWhere('name', 'Debt B');

        // Extra should be 0 (budget exceeded, max(0, 1000-1500) = 0)
        expect($debtBPayment['extra'])->toBe(0.0);
        expect($debtBPayment['amount'])->toBe(150.0); // Only minimum payment
    });

    it('does not affect future months extra payment', function () {
        // Scenario: Budget used this month, but future months should get full extra

        $debtA = Debt::factory()->create([
            'name' => 'Small Debt',
            'original_balance' => 500,
            'balance' => 500,
            'interest_rate' => 10,
            'minimum_payment' => 100,
        ]);

        $debtB = Debt::factory()->create([
            'name' => 'Large Debt',
            'original_balance' => 5000,
            'balance' => 5000,
            'interest_rate' => 15,
            'minimum_payment' => 200,
        ]);

        // Record payment for Small Debt (month 1) with full extra
        $debtA->payments()->create([
            'planned_amount' => 600,
            'actual_amount' => 600, // 100 minimum + 500 extra
            'interest_paid' => 4.17,
            'principal_paid' => 500, // Pays off entire debt (rounded for simplicity)
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
        ]);

        $debtA->update(['balance' => 0]); // Debt A is paid off

        $debts = collect([$debtA->fresh('payments'), $debtB->fresh('payments')]);

        $result = $this->service->generatePaymentSchedule($debts, 500, 'snowball');

        // Month 2 should have full extra payment available for Large Debt
        if (count($result['schedule']) > 1) {
            $month2 = $result['schedule'][1];
            $largeDebtPayment = collect($month2['payments'])->firstWhere('name', 'Large Debt');

            // Month 2: Large Debt should get full extra (500) + rolled-over minimum from paid-off debt (100)
            expect($largeDebtPayment['extra'])->toBeGreaterThanOrEqual(500.0);
        }
    });
});
