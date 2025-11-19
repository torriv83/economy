<?php

use App\Livewire\SelfLoans\Overview;
use App\Models\SelfLoan\SelfLoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('self loan overview component renders successfully', function () {
    $response = $this->get('/self-loans');

    $response->assertSuccessful();
});

test('displays active self-loans correctly', function () {
    SelfLoan::factory()->create([
        'name' => 'Emergency Fund',
        'original_amount' => 10000,
        'current_balance' => 7000,
    ]);

    SelfLoan::factory()->create([
        'name' => 'Car Repair',
        'original_amount' => 5000,
        'current_balance' => 2000,
    ]);

    Livewire::test(Overview::class)
        ->assertSee('Emergency Fund')
        ->assertSee('7 000 kr')
        ->assertSee('Car Repair')
        ->assertSee('2 000 kr')
        ->assertSee('9 000 kr');
});

test('displays empty state when no active loans exist', function () {
    Livewire::test(Overview::class)
        ->assertSee('Ingen Aktive Privat Lån');
});

test('calculates total borrowed correctly', function () {
    SelfLoan::factory()->create(['current_balance' => 5000]);
    SelfLoan::factory()->create(['current_balance' => 3000]);
    SelfLoan::factory()->create(['current_balance' => 2000]);

    $component = Livewire::test(Overview::class);

    expect($component->get('totalBorrowed'))->toBe(10000.0);
});

test('can delete a self-loan', function () {
    $loan = SelfLoan::factory()->create([
        'name' => 'Test Loan',
        'current_balance' => 1000,
    ]);

    Livewire::test(Overview::class)
        ->assertSee('Test Loan')
        ->call('deleteLoan', $loan->id)
        ->assertDontSee('Test Loan');

    expect(SelfLoan::find($loan->id))->toBeNull();
});

test('can add repayment to a self-loan', function () {
    $loan = SelfLoan::factory()->create([
        'original_amount' => 10000,
        'current_balance' => 10000,
    ]);

    Livewire::test(Overview::class)
        ->call('openRepaymentModal', $loan->id)
        ->set('repaymentAmount', 2000)
        ->set('repaymentNotes', 'First payment')
        ->set('repaymentDate', now()->format('Y-m-d'))
        ->call('addRepayment');

    $loan->refresh();

    expect($loan->current_balance)->toBe(8000.0);
    expect($loan->repayments()->count())->toBe(1);
    expect($loan->repayments()->first()->amount)->toBe(2000.0);
    expect($loan->repayments()->first()->notes)->toBe('First payment');
});

test('cannot add repayment exceeding current balance', function () {
    $loan = SelfLoan::factory()->create([
        'current_balance' => 1000,
    ]);

    Livewire::test(Overview::class)
        ->call('openRepaymentModal', $loan->id)
        ->set('repaymentAmount', 2000)
        ->call('addRepayment')
        ->assertHasErrors(['repaymentAmount']);
});

test('shows progress percentage correctly', function () {
    $loan = SelfLoan::factory()->create([
        'original_amount' => 10000,
        'current_balance' => 7000,
    ]);

    Livewire::test(Overview::class)
        ->assertSee('30');
});

test('can add repayment with custom date', function () {
    $loan = SelfLoan::factory()->create([
        'original_amount' => 10000,
        'current_balance' => 10000,
    ]);

    $customDate = now()->subDays(5)->format('Y-m-d');

    Livewire::test(Overview::class)
        ->call('openRepaymentModal', $loan->id)
        ->set('repaymentAmount', 1500)
        ->set('repaymentDate', $customDate)
        ->call('addRepayment');

    $loan->refresh();

    expect($loan->repayments()->count())->toBe(1);
    expect($loan->repayments()->first()->paid_at->format('Y-m-d'))->toBe($customDate);
});

test('cannot add repayment with future date', function () {
    $loan = SelfLoan::factory()->create([
        'current_balance' => 5000,
    ]);

    $futureDate = now()->addDays(1)->format('Y-m-d');

    Livewire::test(Overview::class)
        ->call('openRepaymentModal', $loan->id)
        ->set('repaymentAmount', 1000)
        ->set('repaymentDate', $futureDate)
        ->call('addRepayment')
        ->assertHasErrors(['repaymentDate']);
});

test('repayment date defaults to today when opening modal', function () {
    $loan = SelfLoan::factory()->create([
        'current_balance' => 5000,
    ]);

    Livewire::test(Overview::class)
        ->call('openRepaymentModal', $loan->id)
        ->assertSet('repaymentDate', now()->format('Y-m-d'));
});

test('can withdraw more from a self-loan', function () {
    $loan = SelfLoan::factory()->create([
        'original_amount' => 10000,
        'current_balance' => 7000,
    ]);

    Livewire::test(Overview::class)
        ->call('openWithdrawalModal', $loan->id)
        ->set('withdrawalAmount', 3000)
        ->set('withdrawalDate', now()->format('Y-m-d'))
        ->call('addWithdrawal');

    $loan->refresh();

    expect($loan->current_balance)->toBe(10000.0);
    expect($loan->original_amount)->toBe(13000.0);
});

test('withdrawal date defaults to today when opening modal', function () {
    $loan = SelfLoan::factory()->create([
        'current_balance' => 5000,
    ]);

    Livewire::test(Overview::class)
        ->call('openWithdrawalModal', $loan->id)
        ->assertSet('withdrawalDate', now()->format('Y-m-d'));
});

test('cannot add withdrawal with future date', function () {
    $loan = SelfLoan::factory()->create([
        'current_balance' => 5000,
    ]);

    $futureDate = now()->addDays(1)->format('Y-m-d');

    Livewire::test(Overview::class)
        ->call('openWithdrawalModal', $loan->id)
        ->set('withdrawalAmount', 1000)
        ->set('withdrawalDate', $futureDate)
        ->call('addWithdrawal')
        ->assertHasErrors(['withdrawalDate']);
});

test('can withdraw with custom date', function () {
    $loan = SelfLoan::factory()->create([
        'original_amount' => 10000,
        'current_balance' => 10000,
    ]);

    $customDate = now()->subDays(10)->format('Y-m-d');

    Livewire::test(Overview::class)
        ->call('openWithdrawalModal', $loan->id)
        ->set('withdrawalAmount', 2000)
        ->set('withdrawalDate', $customDate)
        ->call('addWithdrawal');

    $loan->refresh();

    expect($loan->current_balance)->toBe(12000.0);
    expect($loan->original_amount)->toBe(12000.0);
});
