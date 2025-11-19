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
    SelfLoan::factory()->create([
        'name' => 'Active Loan',
        'current_balance' => 1000,
    ]);

    Livewire::test(History::class)
        ->assertDontSee('Active Loan');
});
