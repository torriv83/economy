<?php

use App\Livewire\SelfLoans\History;
use App\Models\SelfLoan\SelfLoan;
use App\Models\SelfLoan\SelfLoanRepayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('displays all repayments correctly', function () {
    $loan = SelfLoan::factory()->create(['name' => 'Test Loan']);

    SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan->id,
        'amount' => 1000,
        'notes' => 'First payment',
    ]);

    SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan->id,
        'amount' => 500,
        'notes' => 'Second payment',
    ]);

    Livewire::test(History::class)
        ->assertSee('Test Loan')
        ->assertSee('1 000 kr')
        ->assertSee('500 kr')
        ->assertSee('First payment')
        ->assertSee('Second payment');
});

test('displays paid-off loans correctly', function () {
    SelfLoan::factory()->create([
        'name' => 'Paid Off Loan',
        'original_amount' => 5000,
        'current_balance' => 0,
    ]);

    SelfLoan::factory()->create([
        'name' => 'Another Paid Loan',
        'original_amount' => 3000,
        'current_balance' => 0,
    ]);

    Livewire::test(History::class)
        ->assertSee('Paid Off Loan')
        ->assertSee('Another Paid Loan')
        ->assertSee('5 000 kr')
        ->assertSee('3 000 kr');
});

test('shows empty state when no history exists', function () {
    Livewire::test(History::class)
        ->assertSee('Ingen Historikk Ennå');
});

test('does not show active loans in paid-off section', function () {
    $activeLoan = SelfLoan::factory()->create([
        'name' => 'Active Loan',
        'current_balance' => 1000,
    ]);

    $paidOffLoan = SelfLoan::factory()->create([
        'name' => 'Paid Off Loan',
        'current_balance' => 0,
    ]);

    $component = Livewire::test(History::class);

    // Active loans should NOT appear in the paid-off loans section
    $paidOffLoans = $component->get('paidOffLoans');
    $paidOffLoanNames = collect($paidOffLoans)->pluck('name')->toArray();

    expect($paidOffLoanNames)->not->toContain('Active Loan');
    expect($paidOffLoanNames)->toContain('Paid Off Loan');
});

test('can filter repayments by loan', function () {
    $loan1 = SelfLoan::factory()->create(['name' => 'Loan One']);
    $loan2 = SelfLoan::factory()->create(['name' => 'Loan Two']);

    SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan1->id,
        'amount' => 1000,
        'notes' => 'Payment for Loan One',
    ]);

    SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan2->id,
        'amount' => 2000,
        'notes' => 'Payment for Loan Two',
    ]);

    Livewire::test(History::class)
        ->set('selectedLoanId', $loan1->id)
        ->assertSee('Payment for Loan One')
        ->assertDontSee('Payment for Loan Two');
});

test('shows all repayments when no filter is selected', function () {
    $loan1 = SelfLoan::factory()->create(['name' => 'Loan One']);
    $loan2 = SelfLoan::factory()->create(['name' => 'Loan Two']);

    SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan1->id,
        'amount' => 1000,
    ]);

    SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan2->id,
        'amount' => 2000,
    ]);

    Livewire::test(History::class)
        ->assertSee('Loan One')
        ->assertSee('Loan Two');
});

test('can clear filter', function () {
    $loan1 = SelfLoan::factory()->create(['name' => 'Loan One']);
    $loan2 = SelfLoan::factory()->create(['name' => 'Loan Two']);

    SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan1->id,
        'amount' => 1000,
        'notes' => 'Payment One',
    ]);

    SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan2->id,
        'amount' => 2000,
        'notes' => 'Payment Two',
    ]);

    Livewire::test(History::class)
        ->set('selectedLoanId', $loan1->id)
        ->assertSee('Payment One')
        ->assertDontSee('Payment Two')
        ->call('clearFilter')
        ->assertSee('Payment One')
        ->assertSee('Payment Two');
});

test('displays available loans in filter dropdown', function () {
    $loan1 = SelfLoan::factory()->create(['name' => 'First Loan']);
    $loan2 = SelfLoan::factory()->create(['name' => 'Second Loan']);

    SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan1->id,
        'amount' => 1000,
    ]);

    Livewire::test(History::class)
        ->assertSee('First Loan')
        ->assertSee('Second Loan');
});
