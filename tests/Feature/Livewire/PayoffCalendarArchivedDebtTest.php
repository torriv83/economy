<?php

use App\Livewire\PayoffCalendar;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * A debt is archived the moment its balance reaches zero, which removes it from
 * Debt::active() and therefore from the payment schedule. These tests cover the
 * final payment staying visible in the calendar regardless.
 */
function calendarFor(int $year, int $month): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
        ->set('currentYear', $year)
        ->set('currentMonth', $month);
}

it('shows the final payment of a debt paid off in the current month', function () {
    $debt = Debt::factory()->create([
        'name' => 'Klarna Mobil',
        'balance' => 0,
        'original_balance' => 5000,
        'interest_rate' => 0,
        'minimum_payment' => 657.38,
        'due_day' => 28,
        'paid_off_at' => now(),
    ]);

    $payment = Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 657.38,
        'principal_paid' => 657.38,
        'payment_date' => now(),
        'payment_month' => now()->format('Y-m'),
        'is_reconciliation_adjustment' => false,
    ]);

    $events = calendarFor((int) now()->year, (int) now()->month)
        ->instance()
        ->getPaymentEventsProperty();

    $dateKey = now()->format('Y-m-d');

    expect($events)->toHaveKey($dateKey)
        ->and($events[$dateKey]['debts'])->toHaveCount(1)
        ->and($events[$dateKey]['debts'][0]['name'])->toBe('Klarna Mobil')
        ->and($events[$dateKey]['debts'][0]['isPaid'])->toBeTrue()
        ->and($events[$dateKey]['debts'][0]['payment_id'])->toBe($payment->id)
        ->and($events[$dateKey]['amount'])->toBe(657.38);
});

it('marks the payoff milestone for a debt archived in the current month', function () {
    $debt = Debt::factory()->create([
        'name' => 'Klarna Mobil',
        'balance' => 0,
        'original_balance' => 5000,
        'due_day' => 28,
        'paid_off_at' => now(),
    ]);

    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 657.38,
        'principal_paid' => 657.38,
        'payment_date' => now(),
        'payment_month' => now()->format('Y-m'),
        'is_reconciliation_adjustment' => false,
    ]);

    $milestones = calendarFor((int) now()->year, (int) now()->month)
        ->instance()
        ->getMilestonesProperty();

    $dateKey = now()->format('Y-m-d');

    expect($milestones)->toHaveKey($dateKey)
        ->and($milestones[$dateKey][0]['type'])->toBe('debt_payoff')
        ->and($milestones[$dateKey][0]['debtName'])->toBe('Klarna Mobil');
});

it('places the milestone on the payment date, not the archival timestamp', function () {
    $paymentDate = now()->startOfMonth()->addDays(3);

    $debt = Debt::factory()->create([
        'name' => 'Etterregistrert',
        'balance' => 0,
        'original_balance' => 5000,
        'paid_off_at' => now(),
    ]);

    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 1000,
        'principal_paid' => 1000,
        'payment_date' => $paymentDate,
        'payment_month' => $paymentDate->format('Y-m'),
        'is_reconciliation_adjustment' => false,
    ]);

    $milestones = calendarFor((int) now()->year, (int) now()->month)
        ->instance()
        ->getMilestonesProperty();

    expect($milestones)->toHaveKey($paymentDate->format('Y-m-d'));
});

it('still shows payments from a past month', function () {
    $lastMonth = now()->subMonthNoOverflow();

    $debt = Debt::factory()->create([
        'name' => 'Nordax 2',
        'balance' => 0,
        'original_balance' => 8000,
        'paid_off_at' => $lastMonth,
    ]);

    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 1500,
        'principal_paid' => 1500,
        'payment_date' => $lastMonth,
        'payment_month' => $lastMonth->format('Y-m'),
        'is_reconciliation_adjustment' => false,
    ]);

    $events = calendarFor((int) $lastMonth->year, (int) $lastMonth->month)
        ->instance()
        ->getPaymentEventsProperty();

    $dateKey = $lastMonth->format('Y-m-d');

    expect($events)->toHaveKey($dateKey)
        ->and($events[$dateKey]['debts'][0]['name'])->toBe('Nordax 2')
        ->and($events[$dateKey]['debts'][0]['isPaid'])->toBeTrue();
});

it('does not duplicate a payment that the schedule already covers', function () {
    $debt = Debt::factory()->create([
        'name' => 'Aktiv gjeld',
        'balance' => 4000,
        'original_balance' => 5000,
        'interest_rate' => 10,
        'minimum_payment' => 1000,
        'due_day' => 15,
    ]);

    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 1000,
        'principal_paid' => 1000,
        'payment_date' => now(),
        'payment_month' => now()->format('Y-m'),
        'month_number' => 1,
        'is_reconciliation_adjustment' => false,
    ]);

    $events = calendarFor((int) now()->year, (int) now()->month)
        ->instance()
        ->getPaymentEventsProperty();

    $occurrences = collect($events)
        ->flatMap(fn (array $event) => $event['debts'])
        ->where('name', 'Aktiv gjeld')
        ->count();

    expect($occurrences)->toBe(1);
});

it('shows a final payment even when no active debts remain', function () {
    $debt = Debt::factory()->create([
        'name' => 'Siste gjeld',
        'balance' => 0,
        'original_balance' => 3000,
        'paid_off_at' => now(),
    ]);

    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 3000,
        'principal_paid' => 3000,
        'payment_date' => now(),
        'payment_month' => now()->format('Y-m'),
        'is_reconciliation_adjustment' => false,
    ]);

    expect(Debt::active()->count())->toBe(0);

    $events = calendarFor((int) now()->year, (int) now()->month)
        ->instance()
        ->getPaymentEventsProperty();

    expect($events)->toHaveKey(now()->format('Y-m-d'))
        ->and($events[now()->format('Y-m-d')]['debts'][0]['name'])->toBe('Siste gjeld');
});

it('excludes reconciliation adjustments from calendar events', function () {
    $debt = Debt::factory()->create([
        'name' => 'Avstemt gjeld',
        'balance' => 0,
        'original_balance' => 5000,
        'paid_off_at' => now(),
    ]);

    Payment::factory()->reconciliation()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 250,
        'principal_paid' => 250,
        'payment_date' => now(),
        'payment_month' => now()->format('Y-m'),
    ]);

    $events = calendarFor((int) now()->year, (int) now()->month)
        ->instance()
        ->getPaymentEventsProperty();

    expect($events)->toBe([]);
});
