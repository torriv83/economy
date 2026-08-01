<?php

use App\Livewire\PaymentPlan;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Create one payment per historical month, oldest first, so the payment plan
 * reports exactly $months historical months.
 */
function createHistoricalPaymentMonths(Debt $debt, int $months): void
{
    for ($i = $months; $i >= 1; $i--) {
        Payment::factory()->create([
            'debt_id' => $debt->id,
            'planned_amount' => 1000,
            'actual_amount' => 1000,
            'payment_date' => now()->subMonths($i),
            'month_number' => $months - $i + 1,
            'payment_month' => now()->subMonths($i)->format('Y-m'),
        ]);
    }
}

test('payment plan component renders successfully', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'original_balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);

    Livewire::test(PaymentPlan::class)
        ->assertSuccessful();
});

test('can switch between avalanche and snowball strategies', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'original_balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);
    Debt::factory()->create(['name' => 'Studielån', 'type' => 'forbrukslån', 'balance' => 200000, 'original_balance' => 200000, 'interest_rate' => 2.5, 'minimum_payment' => 3500]);

    Livewire::test(PaymentPlan::class)
        ->assertSet('strategy', 'avalanche')
        ->call('$set', 'strategy', 'snowball')
        ->assertSet('strategy', 'snowball')
        ->call('$set', 'strategy', 'avalanche')
        ->assertSet('strategy', 'avalanche');
});

test('default strategy is avalanche', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'original_balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);

    $component = Livewire::test(PaymentPlan::class);

    expect($component->get('strategy'))->toBe('avalanche');
});

test('displays debt payoff overview', function () {
    app()->setLocale('en');

    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'original_balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);

    Livewire::test(PaymentPlan::class)
        ->call('loadData')
        ->assertSee('Debt Payoff Overview')
        ->assertSee('Kredittkort');
});

test('displays overall progress', function () {
    app()->setLocale('en');

    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'original_balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);

    Livewire::test(PaymentPlan::class)
        ->call('loadData')
        ->assertSee('Overall Progress');
});

test('displays skeleton loading state before loadData is called', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'original_balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);

    Livewire::test(PaymentPlan::class)
        ->assertSet('isLoading', true)
        ->assertSee('animate-pulse');
});

test('hides skeleton and shows content after loadData is called', function () {
    app()->setLocale('en');

    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'original_balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);

    Livewire::test(PaymentPlan::class)
        ->assertSet('isLoading', true)
        ->call('loadData')
        ->assertSet('isLoading', false)
        ->assertDontSee('animate-pulse')
        ->assertSee('Overall Progress');
});

test('payments_made excludes reconciliation adjustments', function () {
    $debt = Debt::factory()->create([
        'name' => 'Kredittkort',
        'type' => 'kredittkort',
        'balance' => 50000,
        'original_balance' => 50000,
        'interest_rate' => 8.5,
        'minimum_payment' => 1500,
    ]);

    // Create 1 regular payment with month_number
    $debt->payments()->create([
        'planned_amount' => 2000,
        'actual_amount' => 2000,
        'payment_date' => now(),
        'month_number' => 1,
        'payment_month' => now()->format('Y-m'),
        'is_reconciliation_adjustment' => false,
    ]);

    // Create 2 reconciliation adjustments (no month_number)
    $debt->payments()->create([
        'planned_amount' => 0,
        'actual_amount' => 500,
        'payment_date' => now(),
        'month_number' => null,
        'payment_month' => now()->format('Y-m'),
        'is_reconciliation_adjustment' => true,
    ]);

    $debt->payments()->create([
        'planned_amount' => 0,
        'actual_amount' => -300,
        'payment_date' => now(),
        'month_number' => null,
        'payment_month' => now()->format('Y-m'),
        'is_reconciliation_adjustment' => true,
    ]);

    // Verify total payments is 3 (1 regular + 2 reconciliations)
    expect($debt->payments()->count())->toBe(3);

    // Verify payments_made only counts the regular payment (not reconciliations)
    $component = Livewire::test(PaymentPlan::class);
    $schedule = $component->get('debtPayoffSchedule');

    $debtSchedule = collect($schedule)->firstWhere('name', 'Kredittkort');

    expect($debtSchedule['payments_made'])->toBe(1);
});

test('togglePayment stores current month as payment_month when historical months exist', function () {
    $debt = Debt::factory()->create([
        'name' => 'Kredittkort',
        'type' => 'kredittkort',
        'balance' => 50000,
        'original_balance' => 54000,
        'interest_rate' => 8.5,
        'minimum_payment' => 1500,
    ]);

    // To historiske måneder gir offset 2, så inneværende måned er måned 3 i planen
    $debt->payments()->create([
        'planned_amount' => 2000,
        'actual_amount' => 2000,
        'interest_paid' => 0,
        'principal_paid' => 2000,
        'payment_date' => now()->subMonths(2),
        'month_number' => 1,
        'payment_month' => now()->subMonths(2)->format('Y-m'),
    ]);
    $debt->payments()->create([
        'planned_amount' => 2000,
        'actual_amount' => 2000,
        'interest_paid' => 0,
        'principal_paid' => 2000,
        'payment_date' => now()->subMonth(),
        'month_number' => 2,
        'payment_month' => now()->subMonth()->format('Y-m'),
    ]);

    Livewire::test(PaymentPlan::class)
        ->call('togglePayment', 3, $debt->id);

    $payment = $debt->payments()->where('month_number', 3)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->payment_month)->toBe(now()->format('Y-m'));
});

test('markMonthAsPaid stores current month as payment_month when historical months exist', function () {
    $debtA = Debt::factory()->create([
        'name' => 'Kredittkort',
        'type' => 'kredittkort',
        'balance' => 50000,
        'original_balance' => 52000,
        'interest_rate' => 8.5,
        'minimum_payment' => 1500,
    ]);
    $debtB = Debt::factory()->create([
        'name' => 'Forbrukslån',
        'type' => 'forbrukslån',
        'balance' => 20000,
        'original_balance' => 20000,
        'interest_rate' => 12.0,
        'minimum_payment' => 800,
    ]);

    // Én historisk måned gir offset 1, så inneværende måned er måned 2 i planen
    $debtA->payments()->create([
        'planned_amount' => 2000,
        'actual_amount' => 2000,
        'interest_paid' => 0,
        'principal_paid' => 2000,
        'payment_date' => now()->subMonth(),
        'month_number' => 1,
        'payment_month' => now()->subMonth()->format('Y-m'),
    ]);

    Livewire::test(PaymentPlan::class)
        ->call('markMonthAsPaid', 2);

    $payments = \App\Models\Payment::where('month_number', 2)->get();

    expect($payments)->not->toBeEmpty();

    foreach ($payments as $payment) {
        expect($payment->payment_month)->toBe(now()->format('Y-m'));
    }
});

test('shows only the show all historical button when 12 or fewer months remain', function () {
    app()->setLocale('en');

    $debt = Debt::factory()->create([
        'name' => 'Kredittkort',
        'type' => 'kredittkort',
        'balance' => 50000,
        'original_balance' => 70000,
        'interest_rate' => 8.5,
        'minimum_payment' => 1500,
    ]);

    // 8 historiske måneder - 6 er synlige, så 2 gjenstår
    createHistoricalPaymentMonths($debt, 8);

    Livewire::test(PaymentPlan::class)
        ->call('loadData')
        ->assertSet('visibleHistoricalMonths', 6)
        ->assertSee('Show all earlier')
        ->assertDontSee('Show earlier months')
        ->assertDontSeeHtml('wire:click="loadMoreHistoricalMonths"');
});

test('shows only the show all historical button when exactly 12 months remain', function () {
    app()->setLocale('en');

    $debt = Debt::factory()->create([
        'name' => 'Kredittkort',
        'type' => 'kredittkort',
        'balance' => 50000,
        'original_balance' => 70000,
        'interest_rate' => 8.5,
        'minimum_payment' => 1500,
    ]);

    // 18 historiske måneder - 6 er synlige, så nøyaktig 12 gjenstår
    createHistoricalPaymentMonths($debt, 18);

    Livewire::test(PaymentPlan::class)
        ->call('loadData')
        ->assertSee('Show all earlier')
        ->assertDontSee('Show earlier months')
        ->assertDontSeeHtml('wire:click="loadMoreHistoricalMonths"');
});

test('shows both historical buttons when more than 12 months remain', function () {
    app()->setLocale('en');

    $debt = Debt::factory()->create([
        'name' => 'Kredittkort',
        'type' => 'kredittkort',
        'balance' => 50000,
        'original_balance' => 70000,
        'interest_rate' => 8.5,
        'minimum_payment' => 1500,
    ]);

    // 20 historiske måneder - 6 er synlige, så 14 gjenstår
    createHistoricalPaymentMonths($debt, 20);

    Livewire::test(PaymentPlan::class)
        ->call('loadData')
        ->assertSee('Show earlier months')
        ->assertSee('Show all earlier')
        ->assertSeeHtml('wire:click="loadMoreHistoricalMonths"')
        ->assertSeeHtml('wire:click="showAllHistoricalMonths"');
});

test('load more historical hides the load more button once 12 or fewer months remain', function () {
    app()->setLocale('en');

    $debt = Debt::factory()->create([
        'name' => 'Kredittkort',
        'type' => 'kredittkort',
        'balance' => 50000,
        'original_balance' => 70000,
        'interest_rate' => 8.5,
        'minimum_payment' => 1500,
    ]);

    // 25 historiske måneder - 19 gjenstår, etter innlasting gjenstår 7
    createHistoricalPaymentMonths($debt, 25);

    Livewire::test(PaymentPlan::class)
        ->call('loadData')
        ->assertSeeHtml('wire:click="loadMoreHistoricalMonths"')
        ->call('loadMoreHistoricalMonths')
        ->assertSet('visibleHistoricalMonths', 18)
        ->assertDontSeeHtml('wire:click="loadMoreHistoricalMonths"')
        ->assertSee('Show all earlier');
});

test('togglePayment stores schedule month as payment_month for future months', function () {
    $debt = Debt::factory()->create([
        'name' => 'Kredittkort',
        'type' => 'kredittkort',
        'balance' => 50000,
        'original_balance' => 50000,
        'interest_rate' => 8.5,
        'minimum_payment' => 1500,
    ]);

    // Uten historikk er måned 2 i planen neste kalendermåned
    Livewire::test(PaymentPlan::class)
        ->call('togglePayment', 2, $debt->id);

    $payment = $debt->payments()->where('month_number', 2)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->payment_month)->toBe(now()->addMonth()->format('Y-m'));
});
