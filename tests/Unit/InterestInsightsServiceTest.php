<?php

use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCalculationService;
use App\Services\InterestInsightsService;
use App\Services\PaymentService;
use App\Services\PayoffSettingsService;
use Carbon\Carbon;
use Database\Factories\PaymentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    PaymentFactory::resetMonthNumberTracker();

    $this->paymentService = new PaymentService;
    $this->calculationService = new DebtCalculationService($this->paymentService);
    $this->settingsService = new PayoffSettingsService;
    $this->service = new InterestInsightsService(
        $this->calculationService,
        $this->settingsService
    );
});

describe('getInterestBreakdown', function () {
    it('calculates interest breakdown for current month', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt = Debt::factory()->create([
            'name' => 'Credit Card',
            'balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        // Create a payment for current month
        Payment::factory()->create([
            'debt_id' => $debt->id,
            'actual_amount' => 500,
            'interest_paid' => 100,
            'principal_paid' => 400,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => '2024-03',
        ]);

        $result = $this->service->getInterestBreakdown('month');

        expect($result)->toHaveKeys(['total_paid', 'interest_paid', 'principal_paid', 'interest_percentage'])
            ->and($result['total_paid'])->toBe(500.0)
            ->and($result['interest_paid'])->toBe(100.0)
            ->and($result['principal_paid'])->toBe(400.0)
            ->and($result['interest_percentage'])->toBe(20.0); // 100/500 * 100

        Carbon::setTestNow();
    });

    it('calculates interest breakdown for all time', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt = Debt::factory()->create([
            'name' => 'Car Loan',
            'balance' => 15000,
            'interest_rate' => 8,
            'minimum_payment' => 400,
        ]);

        // Create payments directly to bypass factory's automatic interest calculation
        Payment::create([
            'debt_id' => $debt->id,
            'planned_amount' => 400,
            'actual_amount' => 400,
            'interest_paid' => 100,
            'principal_paid' => 300,
            'payment_date' => Carbon::create(2024, 1, 15),
            'month_number' => 1,
            'payment_month' => '2024-01',
        ]);

        Payment::create([
            'debt_id' => $debt->id,
            'planned_amount' => 400,
            'actual_amount' => 400,
            'interest_paid' => 98,
            'principal_paid' => 302,
            'payment_date' => Carbon::create(2024, 2, 15),
            'month_number' => 2,
            'payment_month' => '2024-02',
        ]);

        Payment::create([
            'debt_id' => $debt->id,
            'planned_amount' => 400,
            'actual_amount' => 400,
            'interest_paid' => 96,
            'principal_paid' => 304,
            'payment_date' => Carbon::create(2024, 3, 15),
            'month_number' => 3,
            'payment_month' => '2024-03',
        ]);

        $result = $this->service->getInterestBreakdown('all');

        expect($result['total_paid'])->toBe(1200.0)
            ->and($result['interest_paid'])->toBe(294.0)
            ->and($result['principal_paid'])->toBe(906.0)
            ->and($result['interest_percentage'])->toBe(24.5); // 294/1200 * 100

        Carbon::setTestNow();
    });

    it('handles no payments gracefully', function () {
        Debt::factory()->create([
            'name' => 'Empty Debt',
            'balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 200,
        ]);

        $result = $this->service->getInterestBreakdown('month');

        expect($result['total_paid'])->toBe(0.0)
            ->and($result['interest_paid'])->toBe(0.0)
            ->and($result['principal_paid'])->toBe(0.0)
            ->and($result['interest_percentage'])->toBe(0.0);
    });

    it('handles zero interest debts', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt = Debt::factory()->create([
            'name' => 'Interest Free Loan',
            'balance' => 5000,
            'interest_rate' => 0,
            'minimum_payment' => 500,
        ]);

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'actual_amount' => 500,
            'interest_paid' => 0,
            'principal_paid' => 500,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => '2024-03',
        ]);

        $result = $this->service->getInterestBreakdown('month');

        expect($result['total_paid'])->toBe(500.0)
            ->and($result['interest_paid'])->toBe(0.0)
            ->and($result['principal_paid'])->toBe(500.0)
            ->and($result['interest_percentage'])->toBe(0.0);

        Carbon::setTestNow();
    });
});

describe('getPerDebtInterestBreakdown', function () {
    it('calculates per debt interest breakdown', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt1 = Debt::factory()->create([
            'name' => 'High Interest Card',
            'balance' => 10000,
            'interest_rate' => 18,
            'minimum_payment' => 300,
        ]);

        $debt2 = Debt::factory()->create([
            'name' => 'Low Interest Loan',
            'balance' => 15000,
            'interest_rate' => 6,
            'minimum_payment' => 400,
        ]);

        Payment::factory()->create([
            'debt_id' => $debt1->id,
            'actual_amount' => 300,
            'interest_paid' => 150, // Higher interest
            'principal_paid' => 150,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => '2024-03',
        ]);

        Payment::factory()->create([
            'debt_id' => $debt2->id,
            'actual_amount' => 400,
            'interest_paid' => 75, // Lower interest
            'principal_paid' => 325,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => '2024-03',
        ]);

        $result = $this->service->getPerDebtInterestBreakdown('month');

        expect($result)->toHaveCount(2);

        // First should be High Interest Card (more interest paid)
        $first = $result->first();
        expect($first['debt_name'])->toBe('High Interest Card')
            ->and($first['interest_paid'])->toBe(150.0)
            ->and($first['principal_paid'])->toBe(150.0)
            ->and($first['total_paid'])->toBe(300.0);

        // Second should be Low Interest Loan
        $second = $result->last();
        expect($second['debt_name'])->toBe('Low Interest Loan')
            ->and($second['interest_paid'])->toBe(75.0);

        Carbon::setTestNow();
    });

    it('orders by interest paid descending', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt1 = Debt::factory()->create(['name' => 'Debt A']);
        $debt2 = Debt::factory()->create(['name' => 'Debt B']);
        $debt3 = Debt::factory()->create(['name' => 'Debt C']);

        // Create payments directly to bypass factory's automatic interest calculation
        Payment::create([
            'debt_id' => $debt1->id,
            'planned_amount' => 300,
            'actual_amount' => 300,
            'interest_paid' => 50, // Lowest
            'principal_paid' => 250,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => '2024-03',
        ]);

        Payment::create([
            'debt_id' => $debt2->id,
            'planned_amount' => 400,
            'actual_amount' => 400,
            'interest_paid' => 200, // Highest
            'principal_paid' => 200,
            'payment_date' => now(),
            'month_number' => 2,
            'payment_month' => '2024-03',
        ]);

        Payment::create([
            'debt_id' => $debt3->id,
            'planned_amount' => 350,
            'actual_amount' => 350,
            'interest_paid' => 100, // Middle
            'principal_paid' => 250,
            'payment_date' => now(),
            'month_number' => 3,
            'payment_month' => '2024-03',
        ]);

        $result = $this->service->getPerDebtInterestBreakdown('month');

        $names = $result->pluck('debt_name')->toArray();
        expect($names)->toBe(['Debt B', 'Debt C', 'Debt A']);

        Carbon::setTestNow();
    });

    it('handles debts with zero payments', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt1 = Debt::factory()->create(['name' => 'Active Debt']);
        $debt2 = Debt::factory()->create(['name' => 'No Payments Debt']);

        Payment::factory()->create([
            'debt_id' => $debt1->id,
            'actual_amount' => 300,
            'interest_paid' => 100,
            'principal_paid' => 200,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => '2024-03',
        ]);

        $result = $this->service->getPerDebtInterestBreakdown('month');

        // Should include debts with zero payments for completeness
        expect($result)->toHaveCount(2);

        $noPaymentsDebt = $result->firstWhere('debt_name', 'No Payments Debt');
        expect($noPaymentsDebt['interest_paid'])->toBe(0.0)
            ->and($noPaymentsDebt['principal_paid'])->toBe(0.0)
            ->and($noPaymentsDebt['total_paid'])->toBe(0.0);

        Carbon::setTestNow();
    });

    it('handles all time period', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt = Debt::factory()->create(['name' => 'Test Debt']);

        // Create payments directly to bypass factory's automatic interest calculation
        Payment::create([
            'debt_id' => $debt->id,
            'planned_amount' => 300,
            'actual_amount' => 300,
            'interest_paid' => 100,
            'principal_paid' => 200,
            'payment_date' => Carbon::create(2024, 1, 15),
            'month_number' => 1,
            'payment_month' => '2024-01',
        ]);

        Payment::create([
            'debt_id' => $debt->id,
            'planned_amount' => 300,
            'actual_amount' => 300,
            'interest_paid' => 90,
            'principal_paid' => 210,
            'payment_date' => Carbon::create(2024, 2, 15),
            'month_number' => 2,
            'payment_month' => '2024-02',
        ]);

        $result = $this->service->getPerDebtInterestBreakdown('all');

        $testDebt = $result->firstWhere('debt_name', 'Test Debt');
        expect($testDebt['interest_paid'])->toBe(190.0)
            ->and($testDebt['principal_paid'])->toBe(410.0)
            ->and($testDebt['total_paid'])->toBe(600.0);

        Carbon::setTestNow();
    });
});

describe('getExtraPaymentScenarios', function () {
    it('calculates extra payment scenarios', function () {
        $this->settingsService->saveSettings(500, 'avalanche');

        Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 300,
        ]);

        $result = $this->service->getExtraPaymentScenarios([500, 1000, 2000]);

        expect($result)->toHaveCount(3);

        foreach ($result as $scenario) {
            expect($scenario)->toHaveKeys(['increment', 'total_interest', 'months', 'savings', 'months_saved']);
        }

        // Higher extra payments should result in more savings
        expect($result[0]['increment'])->toBe(500)
            ->and($result[1]['increment'])->toBe(1000)
            ->and($result[2]['increment'])->toBe(2000);

        // More extra payment = more savings
        expect($result[2]['savings'])->toBeGreaterThan($result[1]['savings'])
            ->and($result[1]['savings'])->toBeGreaterThan($result[0]['savings']);

        // More extra payment = fewer months
        expect($result[2]['months'])->toBeLessThan($result[1]['months'])
            ->and($result[1]['months'])->toBeLessThan($result[0]['months']);
    });

    it('calculates savings relative to current extra payment', function () {
        $this->settingsService->saveSettings(200, 'avalanche');

        Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 300,
        ]);

        $result = $this->service->getExtraPaymentScenarios([500]);

        // Savings should be positive (saving money by adding 500 more)
        expect($result[0]['savings'])->toBeGreaterThan(0);
        expect($result[0]['months_saved'])->toBeGreaterThan(0);
    });

    it('handles no debts gracefully', function () {
        $result = $this->service->getExtraPaymentScenarios([500, 1000]);

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2);

        foreach ($result as $scenario) {
            expect($scenario['total_interest'])->toBe(0.0)
                ->and($scenario['months'])->toBe(0)
                ->and($scenario['savings'])->toBe(0.0)
                ->and($scenario['months_saved'])->toBe(0);
        }
    });

    it('uses current strategy from settings', function () {
        $this->settingsService->saveSettings(500, 'snowball');

        Debt::factory()->create([
            'name' => 'Small Debt',
            'balance' => 1000,
            'interest_rate' => 15,
            'minimum_payment' => 100,
        ]);

        Debt::factory()->create([
            'name' => 'Large Debt',
            'balance' => 10000,
            'interest_rate' => 10,
            'minimum_payment' => 200,
        ]);

        $result = $this->service->getExtraPaymentScenarios([500]);

        // Should calculate without errors using snowball strategy
        expect($result[0]['months'])->toBeGreaterThan(0);
    });

    it('calculates with multiple debts', function () {
        $this->settingsService->saveSettings(1000, 'avalanche');

        Debt::factory()->create([
            'name' => 'Debt A',
            'balance' => 5000,
            'interest_rate' => 18,
            'minimum_payment' => 200,
        ]);

        Debt::factory()->create([
            'name' => 'Debt B',
            'balance' => 8000,
            'interest_rate' => 12,
            'minimum_payment' => 250,
        ]);

        $result = $this->service->getExtraPaymentScenarios([500, 1000]);

        expect($result)->toHaveCount(2);
        expect($result[1]['months'])->toBeLessThan($result[0]['months']);
    });
});
