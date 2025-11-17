<?php

use App\Models\Debt;
use App\Models\Payment;
use Carbon\Carbon;
use Livewire\Livewire;

uses()->group('calendar', 'payments');

beforeEach(function () {
    // Create a test debt
    $this->debt = Debt::factory()->create([
        'name' => 'Test Credit Card',
        'balance' => 10000,
        'interest_rate' => 12.0,
        'minimum_payment' => 500,
        'due_day' => 15,
    ]);
});

it('shows unpaid planned payments in component', function () {
    $component = Livewire::test(\App\Livewire\PayoffCalendar::class);

    $component->assertSee('Test Credit Card');

    // Check that payment events are generated for the planned date
    $events = $component->get('paymentEvents');
    expect($events)->not->toBeEmpty();
});

it('shows paid payment on actual payment date', function () {
    // Create an actual payment on a different date (20th instead of 15th due day)
    $paymentDate = Carbon::now()->setDay(20);

    Payment::create([
        'debt_id' => $this->debt->id,
        'payment_date' => $paymentDate,
        'payment_month' => $paymentDate->format('Y-m'),
        'planned_amount' => 500,
        'actual_amount' => 500,
        'interest_paid' => 50,
        'principal_paid' => 450,
        'month_number' => 1,
        'is_reconciliation_adjustment' => false,
    ]);

    $component = Livewire::test(\App\Livewire\PayoffCalendar::class);

    $component->assertSee('Test Credit Card');

    // The payment should be visible on the 20th with isPaid = true
    $events = $component->get('paymentEvents');
    $dateKey = $paymentDate->format('Y-m-d');

    expect($events)->toHaveKey($dateKey);
    expect($events[$dateKey]['debts'][0]['isPaid'])->toBeTrue();
});

it('removes payment from planned date when paid on different date', function () {
    // The debt has due_day = 15
    // Create actual payment on day 20
    $paymentDate = Carbon::now()->setDay(20);
    $plannedDate = Carbon::now()->setDay(15);

    Payment::create([
        'debt_id' => $this->debt->id,
        'payment_date' => $paymentDate,
        'payment_month' => $paymentDate->format('Y-m'),
        'planned_amount' => 500,
        'actual_amount' => 500,
        'interest_paid' => 50,
        'principal_paid' => 450,
        'month_number' => 1,
        'is_reconciliation_adjustment' => false,
    ]);

    $component = Livewire::test(\App\Livewire\PayoffCalendar::class);

    $events = $component->get('paymentEvents');

    // Payment should NOT appear on the 15th (due date)
    $plannedDateKey = $plannedDate->format('Y-m-d');
    expect($events)->not->toHaveKey($plannedDateKey);

    // Payment SHOULD appear on the 20th (actual payment date)
    $actualDateKey = $paymentDate->format('Y-m-d');
    expect($events)->toHaveKey($actualDateKey);
    expect($events[$actualDateKey]['debts'][0]['isPaid'])->toBeTrue();
});

it('shows paid payment on due date when paid on planned date', function () {
    // Create payment on the due date (15th)
    $paymentDate = Carbon::now()->setDay(15);

    Payment::create([
        'debt_id' => $this->debt->id,
        'payment_date' => $paymentDate,
        'payment_month' => $paymentDate->format('Y-m'),
        'planned_amount' => 500,
        'actual_amount' => 500,
        'interest_paid' => 50,
        'principal_paid' => 450,
        'month_number' => 1,
        'is_reconciliation_adjustment' => false,
    ]);

    $component = Livewire::test(\App\Livewire\PayoffCalendar::class);

    $component->assertSee('Test Credit Card');

    // The payment should be visible on the 15th with isPaid = true
    $events = $component->get('paymentEvents');
    $dateKey = $paymentDate->format('Y-m-d');

    expect($events)->toHaveKey($dateKey);
    expect($events[$dateKey]['debts'][0]['isPaid'])->toBeTrue();
});

it('displays actual amount paid instead of planned amount', function () {
    // Create payment with different actual amount than planned
    $paymentDate = Carbon::now()->setDay(15);

    Payment::create([
        'debt_id' => $this->debt->id,
        'payment_date' => $paymentDate,
        'payment_month' => $paymentDate->format('Y-m'),
        'planned_amount' => 500,
        'actual_amount' => 600, // Paid more than planned
        'interest_paid' => 50,
        'principal_paid' => 550,
        'month_number' => 1,
        'is_reconciliation_adjustment' => false,
    ]);

    $component = Livewire::test(\App\Livewire\PayoffCalendar::class);

    $component->assertSee('Test Credit Card')
        ->assertSee('600'); // Should show actual amount

    $events = $component->get('paymentEvents');
    $dateKey = $paymentDate->format('Y-m-d');

    expect($events[$dateKey]['debts'][0]['amount'])->toBe(600.0);
    expect($events[$dateKey]['debts'][0]['isPaid'])->toBeTrue();
});

it('does not show reconciliation adjustments in calendar', function () {
    // Create a reconciliation adjustment
    $paymentDate = Carbon::now()->setDay(10);

    Payment::create([
        'debt_id' => $this->debt->id,
        'payment_date' => $paymentDate,
        'payment_month' => $paymentDate->format('Y-m'),
        'planned_amount' => 0,
        'actual_amount' => 100,
        'interest_paid' => 0,
        'principal_paid' => 100,
        'month_number' => null,
        'is_reconciliation_adjustment' => true, // This should be filtered out
    ]);

    $component = Livewire::test(\App\Livewire\PayoffCalendar::class);

    // Should not see the reconciliation adjustment in events
    $events = $component->get('paymentEvents');

    // The only events should be the planned payments, not the reconciliation
    $dateKey = $paymentDate->format('Y-m-d');
    expect($events)->not->toHaveKey($dateKey);
});

it('handles multiple debts with different payment statuses', function () {
    // Create another debt
    $debt2 = Debt::factory()->create([
        'name' => 'Student Loan',
        'balance' => 50000,
        'interest_rate' => 5.0,
        'minimum_payment' => 1000,
        'due_day' => 25,
    ]);

    // Create payment for first debt (paid)
    $paymentDate = Carbon::now()->setDay(15);
    Payment::create([
        'debt_id' => $this->debt->id,
        'payment_date' => $paymentDate,
        'payment_month' => $paymentDate->format('Y-m'),
        'planned_amount' => 500,
        'actual_amount' => 500,
        'interest_paid' => 50,
        'principal_paid' => 450,
        'month_number' => 1,
        'is_reconciliation_adjustment' => false,
    ]);

    // Don't create payment for second debt (unpaid)

    $component = Livewire::test(\App\Livewire\PayoffCalendar::class);

    $component->assertSee('Test Credit Card') // Paid debt
        ->assertSee('Student Loan'); // Unpaid debt

    $events = $component->get('paymentEvents');

    // First debt should have isPaid = true on the 15th
    $dateKey15 = Carbon::now()->setDay(15)->format('Y-m-d');
    $firstDebtEvent = collect($events[$dateKey15]['debts'])->firstWhere('name', 'Test Credit Card');
    expect($firstDebtEvent['isPaid'])->toBeTrue();

    // Second debt should have isPaid = false on the 25th
    $dateKey25 = Carbon::now()->setDay(25)->format('Y-m-d');
    if (isset($events[$dateKey25])) {
        $secondDebtEvent = collect($events[$dateKey25]['debts'])->firstWhere('name', 'Student Loan');
        expect($secondDebtEvent['isPaid'])->toBeFalse();
    }
});

it('marks unpaid payments as overdue when past due date', function () {
    // Travel back in time to create an overdue scenario in the current month
    // Set current date to after the 15th (due day) so payment is overdue
    Carbon::setTestNow(Carbon::now()->setDay(20)); // Set to 20th of current month

    $component = Livewire::test(\App\Livewire\PayoffCalendar::class);

    $events = $component->get('paymentEvents');

    // The payment on the 15th should be marked as overdue (we're on the 20th)
    $dateKey = Carbon::now()->setDay(15)->format('Y-m-d');

    expect($events)->toHaveKey($dateKey);
    $debtEvent = collect($events[$dateKey]['debts'])->firstWhere('name', 'Test Credit Card');
    expect($debtEvent)->not->toBeNull();
    expect($debtEvent['isOverdue'])->toBeTrue();
    expect($debtEvent['isPaid'])->toBeFalse();

    // Reset time
    Carbon::setTestNow();
});

it('does not mark future payments as overdue', function () {
    // Set component to view a future month
    $futureMonth = Carbon::now()->addMonth();

    $component = Livewire::test(\App\Livewire\PayoffCalendar::class)
        ->set('currentMonth', $futureMonth->month)
        ->set('currentYear', $futureMonth->year);

    $events = $component->get('paymentEvents');

    // The payment on the 15th of next month should NOT be marked as overdue
    $dateKey = $futureMonth->setDay(15)->format('Y-m-d');
    if (isset($events[$dateKey])) {
        $debtEvent = collect($events[$dateKey]['debts'])->firstWhere('name', 'Test Credit Card');
        expect($debtEvent['isOverdue'] ?? false)->toBeFalse();
        expect($debtEvent['isPaid'])->toBeFalse();
    }
});

it('does not mark today payment as overdue', function () {
    // Create a debt with due day = today
    $today = Carbon::now();
    $debt = Debt::factory()->create([
        'name' => 'Today Debt',
        'balance' => 5000,
        'interest_rate' => 10.0,
        'minimum_payment' => 300,
        'due_day' => $today->day,
    ]);

    $component = Livewire::test(\App\Livewire\PayoffCalendar::class);

    $events = $component->get('paymentEvents');

    // Today's payment should NOT be marked as overdue
    $dateKey = $today->format('Y-m-d');
    if (isset($events[$dateKey])) {
        $debtEvent = collect($events[$dateKey]['debts'])->firstWhere('name', 'Today Debt');
        expect($debtEvent['isOverdue'] ?? false)->toBeFalse();
    }
});
