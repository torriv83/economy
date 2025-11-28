<?php

use App\Livewire\ReconciliationHistory;
use App\Models\Debt;
use App\Models\Payment;
use Livewire\Livewire;

it('shows empty state when no reconciliations exist', function () {
    Livewire::test(ReconciliationHistory::class)
        ->assertSee(__('app.no_reconciliations'));
});

it('displays all reconciliations', function () {
    $debt = Debt::factory()->create(['name' => 'Test Debt']);

    Payment::factory()->reconciliation()->create([
        'debt_id' => $debt->id,
        'payment_date' => '2024-01-15',
        'principal_paid' => 500,
        'notes' => 'Test reconciliation note',
    ]);

    Livewire::test(ReconciliationHistory::class)
        ->assertSee('Test Debt')
        ->assertSee('15.01.2024')
        ->assertSee('Test reconciliation note');
});

it('filters reconciliations by debt', function () {
    $debt1 = Debt::factory()->create(['name' => 'Debt One']);
    $debt2 = Debt::factory()->create(['name' => 'Debt Two']);

    Payment::factory()->reconciliation()->create([
        'debt_id' => $debt1->id,
        'notes' => 'Reconciliation for debt one',
    ]);

    Payment::factory()->reconciliation()->create([
        'debt_id' => $debt2->id,
        'notes' => 'Reconciliation for debt two',
    ]);

    Livewire::test(ReconciliationHistory::class)
        ->assertSee('Reconciliation for debt one')
        ->assertSee('Reconciliation for debt two')
        ->set('filterDebtId', $debt1->id)
        ->assertSee('Reconciliation for debt one')
        ->assertDontSee('Reconciliation for debt two');
});

it('opens edit modal with correct data', function () {
    $debt = Debt::factory()->create(['balance' => 10000]);

    $reconciliation = Payment::factory()->reconciliation()->create([
        'debt_id' => $debt->id,
        'payment_date' => '2024-02-20',
        'notes' => 'Original note',
    ]);

    Livewire::test(ReconciliationHistory::class)
        ->call('openEditModal', $reconciliation->id)
        ->assertSet('showEditModal', true)
        ->assertSet('editingReconciliationId', $reconciliation->id)
        ->assertSet('editDate', '20.02.2024')
        ->assertSet('editNotes', 'Original note');
});

it('closes edit modal and resets state', function () {
    $debt = Debt::factory()->create();

    $reconciliation = Payment::factory()->reconciliation()->create([
        'debt_id' => $debt->id,
    ]);

    Livewire::test(ReconciliationHistory::class)
        ->call('openEditModal', $reconciliation->id)
        ->assertSet('showEditModal', true)
        ->call('closeEditModal')
        ->assertSet('showEditModal', false)
        ->assertSet('editingReconciliationId', null)
        ->assertSet('editBalance', '')
        ->assertSet('editDate', '')
        ->assertSet('editNotes', null);
});

it('validates edit form fields', function () {
    $debt = Debt::factory()->create();

    $reconciliation = Payment::factory()->reconciliation()->create([
        'debt_id' => $debt->id,
    ]);

    Livewire::test(ReconciliationHistory::class)
        ->call('openEditModal', $reconciliation->id)
        ->set('editBalance', '')
        ->set('editDate', 'invalid-date')
        ->call('saveEdit')
        ->assertHasErrors(['editBalance', 'editDate']);
});

it('successfully saves edited reconciliation', function () {
    $debt = Debt::factory()->create([
        'balance' => 10000,
        'original_balance' => 15000,
    ]);

    $reconciliation = Payment::factory()->reconciliation()->create([
        'debt_id' => $debt->id,
        'payment_date' => '2024-02-20',
        'principal_paid' => 500,
        'notes' => 'Original note',
    ]);

    Livewire::test(ReconciliationHistory::class)
        ->call('openEditModal', $reconciliation->id)
        ->set('editBalance', '9500')
        ->set('editDate', '25.02.2024')
        ->set('editNotes', 'Updated note')
        ->call('saveEdit')
        ->assertSet('showEditModal', false)
        ->assertSet('editingReconciliationId', null)
        ->assertDispatched('reconciliation-updated');

    // Verify the reconciliation was updated
    $reconciliation->refresh();
    expect($reconciliation->payment_date->format('Y-m-d'))->toBe('2024-02-25');
    expect($reconciliation->notes)->toBe('Updated note');
});

it('opens delete confirmation modal', function () {
    $debt = Debt::factory()->create();

    $reconciliation = Payment::factory()->reconciliation()->create([
        'debt_id' => $debt->id,
    ]);

    Livewire::test(ReconciliationHistory::class)
        ->call('confirmDelete', $reconciliation->id)
        ->assertSet('showDeleteConfirm', true)
        ->assertSet('deletingReconciliationId', $reconciliation->id);
});

it('cancels delete confirmation', function () {
    $debt = Debt::factory()->create();

    $reconciliation = Payment::factory()->reconciliation()->create([
        'debt_id' => $debt->id,
    ]);

    Livewire::test(ReconciliationHistory::class)
        ->call('confirmDelete', $reconciliation->id)
        ->assertSet('showDeleteConfirm', true)
        ->call('cancelDelete')
        ->assertSet('showDeleteConfirm', false)
        ->assertSet('deletingReconciliationId', null);
});

it('deletes a reconciliation', function () {
    $debt = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000]);

    $reconciliation = Payment::factory()->reconciliation()->create([
        'debt_id' => $debt->id,
        'principal_paid' => 500,
    ]);

    Livewire::test(ReconciliationHistory::class)
        ->call('confirmDelete', $reconciliation->id)
        ->call('deleteReconciliation')
        ->assertSet('showDeleteConfirm', false)
        ->assertDispatched('reconciliation-deleted');

    expect(Payment::find($reconciliation->id))->toBeNull();
});

it('shows debts in filter dropdown', function () {
    $debt1 = Debt::factory()->create(['name' => 'First Debt']);
    $debt2 = Debt::factory()->create(['name' => 'Second Debt']);

    Livewire::test(ReconciliationHistory::class)
        ->assertSee('First Debt')
        ->assertSee('Second Debt');
});

it('does not open edit modal for non-reconciliation payments', function () {
    $debt = Debt::factory()->create();

    // Create a regular payment, not a reconciliation
    $payment = Payment::factory()->create([
        'debt_id' => $debt->id,
        'is_reconciliation_adjustment' => false,
    ]);

    Livewire::test(ReconciliationHistory::class)
        ->call('openEditModal', $payment->id)
        ->assertSet('showEditModal', false)
        ->assertSet('editingReconciliationId', null);
});

it('does not delete non-reconciliation payments', function () {
    $debt = Debt::factory()->create();

    // Create a regular payment, not a reconciliation
    $payment = Payment::factory()->create([
        'debt_id' => $debt->id,
        'is_reconciliation_adjustment' => false,
    ]);

    Livewire::test(ReconciliationHistory::class)
        ->set('deletingReconciliationId', $payment->id)
        ->set('showDeleteConfirm', true)
        ->call('deleteReconciliation');

    // Payment should still exist
    expect(Payment::find($payment->id))->not->toBeNull();
});
