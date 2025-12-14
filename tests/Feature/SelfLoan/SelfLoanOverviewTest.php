<?php

use App\Livewire\SelfLoans\Overview;
use App\Models\SelfLoan\SelfLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('self loan overview component renders successfully', function () {
    $this->actingAs(User::factory()->create());

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
        ->call('loadData')
        ->assertSee('Emergency Fund')
        ->assertSee('7 000')
        ->assertSee('Car Repair')
        ->assertSee('2 000')
        ->assertSee('9 000');
});

test('displays empty state when no active loans exist', function () {
    Livewire::test(Overview::class)
        ->call('loadData')
        ->assertSee(__('app.no_active_self_loans'));
});

test('calculates total borrowed correctly', function () {
    SelfLoan::factory()->create(['current_balance' => 5000]);
    SelfLoan::factory()->create(['current_balance' => 3000]);
    SelfLoan::factory()->create(['current_balance' => 2000]);

    $component = Livewire::test(Overview::class)
        ->call('loadData');

    expect($component->get('totalBorrowed'))->toBe(10000.0);
});

test('can delete a self-loan', function () {
    $loan = SelfLoan::factory()->create([
        'name' => 'Test Loan',
        'current_balance' => 1000,
    ]);

    Livewire::test(Overview::class)
        ->call('loadData')
        ->assertSee('Test Loan')
        ->call('confirmDelete', $loan->id, 'Test Loan')
        ->call('executeDelete')
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
        ->set('repaymentDate', now()->format('d.m.Y'))
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
        ->call('loadData')
        ->assertSee('30');
});

test('can add repayment with custom date', function () {
    $loan = SelfLoan::factory()->create([
        'original_amount' => 10000,
        'current_balance' => 10000,
    ]);

    $customDate = now()->subDays(5);

    Livewire::test(Overview::class)
        ->call('openRepaymentModal', $loan->id)
        ->set('repaymentAmount', 1500)
        ->set('repaymentDate', $customDate->format('d.m.Y'))
        ->call('addRepayment');

    $loan->refresh();

    expect($loan->repayments()->count())->toBe(1);
    expect($loan->repayments()->first()->paid_at->format('Y-m-d'))->toBe($customDate->format('Y-m-d'));
});

test('cannot add repayment with future date', function () {
    $loan = SelfLoan::factory()->create([
        'current_balance' => 5000,
    ]);

    $futureDate = now()->addDays(1)->format('d.m.Y');

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
        ->assertSet('repaymentDate', now()->format('d.m.Y'));
});

test('can withdraw more from a self-loan', function () {
    $loan = SelfLoan::factory()->create([
        'original_amount' => 10000,
        'current_balance' => 7000,
    ]);

    Livewire::test(Overview::class)
        ->call('openWithdrawalModal', $loan->id)
        ->set('withdrawalAmount', 3000)
        ->set('withdrawalDate', now()->format('d.m.Y'))
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
        ->assertSet('withdrawalDate', now()->format('d.m.Y'));
});

test('cannot add withdrawal with future date', function () {
    $loan = SelfLoan::factory()->create([
        'current_balance' => 5000,
    ]);

    $futureDate = now()->addDays(1)->format('d.m.Y');

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

    $customDate = now()->subDays(10)->format('d.m.Y');

    Livewire::test(Overview::class)
        ->call('openWithdrawalModal', $loan->id)
        ->set('withdrawalAmount', 2000)
        ->set('withdrawalDate', $customDate)
        ->call('addWithdrawal');

    $loan->refresh();

    expect($loan->current_balance)->toBe(12000.0);
    expect($loan->original_amount)->toBe(12000.0);
});

test('can open edit modal and load loan data', function () {
    $loan = SelfLoan::factory()->create([
        'name' => 'Test Loan',
        'description' => 'Test Description',
        'original_amount' => 5000,
    ]);

    Livewire::test(Overview::class)
        ->call('openEditModal', $loan->id)
        ->assertSet('showEditModal', true)
        ->assertSet('editName', 'Test Loan')
        ->assertSet('editDescription', 'Test Description')
        ->assertSet('editOriginalAmount', '5000');
});

test('can update self-loan via modal', function () {
    $loan = SelfLoan::factory()->create([
        'name' => 'Old Name',
        'description' => 'Old Description',
        'original_amount' => 5000,
    ]);

    Livewire::test(Overview::class)
        ->call('openEditModal', $loan->id)
        ->set('editName', 'New Name')
        ->set('editDescription', 'New Description')
        ->set('editOriginalAmount', '7500')
        ->call('updateLoan');

    $loan->refresh();

    expect($loan->name)->toBe('New Name');
    expect($loan->description)->toBe('New Description');
    expect($loan->original_amount)->toBe(7500.0);
});

test('edit modal validates required fields', function () {
    $loan = SelfLoan::factory()->create();

    Livewire::test(Overview::class)
        ->call('openEditModal', $loan->id)
        ->set('editName', '')
        ->call('updateLoan')
        ->assertHasErrors(['editName']);
});

test('edit modal validates original amount is numeric', function () {
    $loan = SelfLoan::factory()->create();

    Livewire::test(Overview::class)
        ->call('openEditModal', $loan->id)
        ->set('editOriginalAmount', 'not-a-number')
        ->call('updateLoan')
        ->assertHasErrors(['editOriginalAmount']);
});

test('edit modal validates original amount is positive', function () {
    $loan = SelfLoan::factory()->create();

    Livewire::test(Overview::class)
        ->call('openEditModal', $loan->id)
        ->set('editOriginalAmount', '-100')
        ->call('updateLoan')
        ->assertHasErrors(['editOriginalAmount']);
});

test('edit modal closes after successful update', function () {
    $loan = SelfLoan::factory()->create();

    Livewire::test(Overview::class)
        ->call('openEditModal', $loan->id)
        ->set('editName', 'Updated Name')
        ->call('updateLoan')
        ->assertSet('showEditModal', false);
});

test('can close edit modal without saving', function () {
    $loan = SelfLoan::factory()->create([
        'name' => 'Original Name',
    ]);

    Livewire::test(Overview::class)
        ->call('openEditModal', $loan->id)
        ->set('editName', 'Changed Name')
        ->call('closeEditModal')
        ->assertSet('showEditModal', false);

    $loan->refresh();
    expect($loan->name)->toBe('Original Name');
});
