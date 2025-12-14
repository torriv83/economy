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
        ->call('loadData')
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
        ->call('loadData')
        ->assertSee('Paid Off Loan')
        ->assertSee('Another Paid Loan')
        ->assertSee('5 000 kr')
        ->assertSee('3 000 kr');
});

test('shows empty state when no history exists', function () {
    Livewire::test(History::class)
        ->call('loadData')
        ->assertSee(__('app.no_history_yet'));
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

    $component = Livewire::test(History::class)
        ->call('loadData');

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
        ->call('loadData')
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
        ->call('loadData')
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
        ->call('loadData')
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
        ->call('loadData')
        ->assertSee('First Loan')
        ->assertSee('Second Loan');
});

test('can open edit modal for repayment', function () {
    $loan = SelfLoan::factory()->create([
        'name' => 'Test Loan',
        'original_amount' => 5000,
        'current_balance' => 4000,
    ]);

    $repayment = SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan->id,
        'amount' => 1000,
        'notes' => 'Test note',
        'paid_at' => now(),
    ]);

    Livewire::test(History::class)
        ->call('openEditModal', $repayment->id)
        ->assertSet('showEditModal', true)
        ->assertSet('editRepaymentId', $repayment->id)
        ->assertSet('editAmount', '1000')
        ->assertSet('editNotes', 'Test note');
});

test('can close edit modal', function () {
    $loan = SelfLoan::factory()->create();
    $repayment = SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan->id,
        'amount' => 1000,
    ]);

    Livewire::test(History::class)
        ->call('openEditModal', $repayment->id)
        ->assertSet('showEditModal', true)
        ->call('closeEditModal')
        ->assertSet('showEditModal', false)
        ->assertSet('editRepaymentId', null)
        ->assertSet('editAmount', '')
        ->assertSet('editNotes', '');
});

test('can update repayment amount', function () {
    $loan = SelfLoan::factory()->create([
        'name' => 'Test Loan',
        'original_amount' => 5000,
        'current_balance' => 4000,
    ]);

    $repayment = SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan->id,
        'amount' => 1000,
        'notes' => 'Original note',
        'paid_at' => now(),
    ]);

    Livewire::test(History::class)
        ->call('openEditModal', $repayment->id)
        ->set('editAmount', '1500')
        ->set('editNotes', 'Updated note')
        ->call('updateRepayment')
        ->assertSet('showEditModal', false);

    $repayment->refresh();
    expect($repayment->amount)->toBe(1500.0);
    expect($repayment->notes)->toBe('Updated note');

    $loan->refresh();
    expect($loan->current_balance)->toBe(3500.0); // 5000 - 1500
});

test('updating withdrawal keeps amount negative', function () {
    $loan = SelfLoan::factory()->create([
        'name' => 'Test Loan',
        'original_amount' => 5000,
        'current_balance' => 6000,
    ]);

    $withdrawal = SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan->id,
        'amount' => -1000, // Withdrawal (negative)
        'paid_at' => now(),
    ]);

    Livewire::test(History::class)
        ->call('openEditModal', $withdrawal->id)
        ->assertSet('editAmount', '1000') // Shows absolute value
        ->set('editAmount', '1500')
        ->call('updateRepayment');

    $withdrawal->refresh();
    expect($withdrawal->amount)->toBe(-1500.0); // Stays negative

    $loan->refresh();
    expect($loan->current_balance)->toBe(6500.0); // 5000 - (-1500) = 6500
});

test('can confirm delete repayment', function () {
    $loan = SelfLoan::factory()->create(['name' => 'Test Loan']);
    $repayment = SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan->id,
        'amount' => 1000,
    ]);

    Livewire::test(History::class)
        ->call('confirmDelete', $repayment->id, 'Test Loan')
        ->assertSet('showDeleteModal', true)
        ->assertSet('repaymentToDelete', $repayment->id)
        ->assertSet('repaymentLoanName', 'Test Loan');
});

test('can delete repayment and update loan balance', function () {
    $loan = SelfLoan::factory()->create([
        'name' => 'Test Loan',
        'original_amount' => 5000,
        'current_balance' => 4000,
    ]);

    $repayment = SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan->id,
        'amount' => 1000,
    ]);

    Livewire::test(History::class)
        ->call('confirmDelete', $repayment->id, 'Test Loan')
        ->call('deleteRepayment')
        ->assertSet('showDeleteModal', false);

    expect(SelfLoanRepayment::find($repayment->id))->toBeNull();

    $loan->refresh();
    expect($loan->current_balance)->toBe(5000.0); // Back to original
});

test('validates edit repayment form', function () {
    $loan = SelfLoan::factory()->create();
    $repayment = SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan->id,
        'amount' => 1000,
        'paid_at' => now(),
    ]);

    Livewire::test(History::class)
        ->call('openEditModal', $repayment->id)
        ->set('editAmount', '')
        ->set('editPaidAt', '')
        ->call('updateRepayment')
        ->assertHasErrors(['editAmount', 'editPaidAt']);
});

test('validates minimum amount for edit', function () {
    $loan = SelfLoan::factory()->create();
    $repayment = SelfLoanRepayment::factory()->create([
        'self_loan_id' => $loan->id,
        'amount' => 1000,
        'paid_at' => now(),
    ]);

    Livewire::test(History::class)
        ->call('openEditModal', $repayment->id)
        ->set('editAmount', '0')
        ->call('updateRepayment')
        ->assertHasErrors(['editAmount']);
});
