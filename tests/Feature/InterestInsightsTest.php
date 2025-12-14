<?php

use App\Livewire\InterestInsights;
use App\Models\Debt;
use App\Models\Payment;
use Carbon\Carbon;
use Database\Factories\PaymentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    PaymentFactory::resetMonthNumberTracker();
});

describe('InterestInsights component', function () {
    it('renders with default month period and loading state', function () {
        Livewire::test(InterestInsights::class)
            ->assertSet('period', 'month')
            ->assertSet('isLoading', true)
            ->assertStatus(200);
    });

    it('sets isLoading to false after loadData is called', function () {
        Livewire::test(InterestInsights::class)
            ->assertSet('isLoading', true)
            ->call('loadData')
            ->assertSet('isLoading', false);
    });

    it('can toggle between month and all time period', function () {
        Livewire::test(InterestInsights::class)
            ->assertSet('period', 'month')
            ->call('setPeriod', 'all')
            ->assertSet('period', 'all')
            ->call('setPeriod', 'month')
            ->assertSet('period', 'month');
    });

    it('shows empty state when no payments exist', function () {
        Livewire::test(InterestInsights::class)
            ->call('loadData')
            ->assertSee(__('app.no_payments_yet'))
            ->assertSee(__('app.no_payments_yet_description'));
    });

    it('shows interest breakdown when payments exist', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'actual_amount' => 500,
            'interest_paid' => 100,
            'principal_paid' => 400,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => '2024-03',
        ]);

        Livewire::test(InterestInsights::class)
            ->call('loadData')
            ->assertSee(__('app.interest_breakdown'))
            ->assertSee(__('app.principal_paid'))
            ->assertSee(__('app.interest_paid'));

        Carbon::setTestNow();
    });

    it('shows per debt breakdown with payments', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt = Debt::factory()->create([
            'name' => 'Credit Card',
            'balance' => 10000,
            'interest_rate' => 18,
            'minimum_payment' => 300,
        ]);

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'actual_amount' => 300,
            'interest_paid' => 150,
            'principal_paid' => 150,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => '2024-03',
        ]);

        Livewire::test(InterestInsights::class)
            ->call('loadData')
            ->assertSee(__('app.per_debt_breakdown'))
            ->assertSee('Credit Card');

        Carbon::setTestNow();
    });

    it('highlights most expensive debt', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt1 = Debt::factory()->create([
            'name' => 'High Interest',
            'balance' => 5000,
            'interest_rate' => 24,
            'minimum_payment' => 200,
        ]);

        $debt2 = Debt::factory()->create([
            'name' => 'Low Interest',
            'balance' => 5000,
            'interest_rate' => 6,
            'minimum_payment' => 200,
        ]);

        Payment::factory()->create([
            'debt_id' => $debt1->id,
            'actual_amount' => 200,
            'interest_paid' => 100, // Higher interest
            'principal_paid' => 100,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => '2024-03',
        ]);

        Payment::factory()->create([
            'debt_id' => $debt2->id,
            'actual_amount' => 200,
            'interest_paid' => 25, // Lower interest
            'principal_paid' => 175,
            'payment_date' => now(),
            'month_number' => 2,
            'payment_month' => '2024-03',
        ]);

        Livewire::test(InterestInsights::class)
            ->call('loadData')
            ->assertSee(__('app.most_expensive_debt'))
            ->assertSee('High Interest');

        Carbon::setTestNow();
    });

    it('shows extra payment optimizer section', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'actual_amount' => 500,
            'interest_paid' => 100,
            'principal_paid' => 400,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => '2024-03',
        ]);

        Livewire::test(InterestInsights::class)
            ->call('loadData')
            ->assertSee(__('app.extra_payment_optimizer'))
            ->assertSee(__('app.current_extra_payment'))
            ->assertSee(__('app.if_you_add'));

        Carbon::setTestNow();
    });

    it('correctly calculates principal percentage', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        // 80% principal, 20% interest
        Payment::factory()->create([
            'debt_id' => $debt->id,
            'actual_amount' => 500,
            'interest_paid' => 100,
            'principal_paid' => 400,
            'payment_date' => now(),
            'month_number' => 1,
            'payment_month' => '2024-03',
        ]);

        $component = Livewire::test(InterestInsights::class)
            ->call('loadData');

        expect($component->get('principalPercentage'))->toBe(80.0);

        Carbon::setTestNow();
    });

    it('filters payments by period correctly', function () {
        Carbon::setTestNow(Carbon::create(2024, 3, 15));

        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
        ]);

        // Current month payment
        Payment::create([
            'debt_id' => $debt->id,
            'planned_amount' => 500,
            'actual_amount' => 500,
            'interest_paid' => 100,
            'principal_paid' => 400,
            'payment_date' => now(),
            'month_number' => 2,
            'payment_month' => '2024-03',
        ]);

        // Previous month payment
        Payment::create([
            'debt_id' => $debt->id,
            'planned_amount' => 500,
            'actual_amount' => 500,
            'interest_paid' => 110,
            'principal_paid' => 390,
            'payment_date' => Carbon::create(2024, 2, 15),
            'month_number' => 1,
            'payment_month' => '2024-02',
        ]);

        $component = Livewire::test(InterestInsights::class)
            ->call('loadData');

        // Month view should only show March payment
        expect($component->get('breakdown')['total_paid'])->toBe(500.0);

        // All time should show both payments
        $component->call('setPeriod', 'all');
        expect($component->get('breakdown')['total_paid'])->toBe(1000.0);

        Carbon::setTestNow();
    });
});
