<?php

use App\Livewire\PaymentPlan;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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
