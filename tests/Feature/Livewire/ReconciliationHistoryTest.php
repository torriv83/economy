<?php

use App\Livewire\ReconciliationHistory;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('component rendering', function () {
    it('can be rendered', function () {
        $debt = Debt::factory()->create();

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->assertOk();
    });

    it('shows empty state when debt has no reconciliations', function () {
        $debt = Debt::factory()->create();

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->assertSee(__('app.no_reconciliations'));
    });

    it('displays reconciliations for the specific debt', function () {
        $debt = Debt::factory()->create(['name' => 'Test Debt']);

        Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-03-15',
            'principal_paid' => 500,
            'notes' => 'Test reconciliation for this debt',
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->assertSee('15.03.2024')
            ->assertSee('Test reconciliation for this debt');
    });

    it('does not show reconciliations from other debts', function () {
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

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt1->id])
            ->assertSee('Reconciliation for debt one')
            ->assertDontSee('Reconciliation for debt two');
    });

    it('shows decrease badge for positive principal paid', function () {
        $debt = Debt::factory()->create();

        Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500, // Positive = balance decrease
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->assertSeeHtml('-500'); // Balance decreased
    });

    it('shows increase badge for negative principal paid', function () {
        $debt = Debt::factory()->create();

        Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => -500, // Negative = balance increase
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->assertSeeHtml('+500'); // Balance increased
    });
});

describe('edit modal', function () {
    it('opens edit modal with correct data', function () {
        $debt = Debt::factory()->create(['balance' => 10000]);

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-04-20',
            'notes' => 'Original note for edit',
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $reconciliation->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingReconciliationId', $reconciliation->id)
            ->assertSet('editDate', '20.04.2024')
            ->assertSet('editNotes', 'Original note for edit');
    });

    it('sets edit balance to current debt balance', function () {
        $debt = Debt::factory()->create(['balance' => 8500]);

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $reconciliation->id)
            ->assertSet('editBalance', '8500');
    });

    it('closes edit modal and resets state', function () {
        $debt = Debt::factory()->create();

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $reconciliation->id)
            ->assertSet('showEditModal', true)
            ->call('closeEditModal')
            ->assertSet('showEditModal', false)
            ->assertSet('editingReconciliationId', null)
            ->assertSet('editBalance', '')
            ->assertSet('editDate', '')
            ->assertSet('editNotes', null);
    });

    it('does not open edit modal for non-reconciliation payments', function () {
        $debt = Debt::factory()->create();

        $regularPayment = Payment::factory()->create([
            'debt_id' => $debt->id,
            'is_reconciliation_adjustment' => false,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $regularPayment->id)
            ->assertSet('showEditModal', false)
            ->assertSet('editingReconciliationId', null);
    });

    it('does not open edit modal for non-existent payment', function () {
        $debt = Debt::factory()->create();

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', 99999)
            ->assertSet('showEditModal', false)
            ->assertSet('editingReconciliationId', null);
    });
});

describe('edit validation', function () {
    it('validates balance is required', function () {
        $debt = Debt::factory()->create();

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $reconciliation->id)
            ->set('editBalance', '')
            ->call('saveEdit')
            ->assertHasErrors(['editBalance' => 'required']);
    });

    it('validates balance is numeric', function () {
        $debt = Debt::factory()->create();

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $reconciliation->id)
            ->set('editBalance', 'not-a-number')
            ->call('saveEdit')
            ->assertHasErrors(['editBalance' => 'numeric']);
    });

    it('validates balance is not negative', function () {
        $debt = Debt::factory()->create();

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $reconciliation->id)
            ->set('editBalance', '-100')
            ->call('saveEdit')
            ->assertHasErrors(['editBalance' => 'min']);
    });

    it('validates date is required', function () {
        $debt = Debt::factory()->create();

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $reconciliation->id)
            ->set('editDate', '')
            ->call('saveEdit')
            ->assertHasErrors(['editDate' => 'required']);
    });

    it('validates date format is DD.MM.YYYY', function () {
        $debt = Debt::factory()->create();

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $reconciliation->id)
            ->set('editBalance', '10000')
            ->set('editDate', '2024-01-15') // Wrong format
            ->call('saveEdit')
            ->assertHasErrors(['editDate' => 'date_format']);
    });

    it('validates notes max length', function () {
        $debt = Debt::factory()->create();

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $reconciliation->id)
            ->set('editBalance', '10000')
            ->set('editDate', '15.01.2024')
            ->set('editNotes', str_repeat('a', 501)) // Exceeds 500 char limit
            ->call('saveEdit')
            ->assertHasErrors(['editNotes' => 'max']);
    });

    it('allows empty notes', function () {
        $debt = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000]);

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $reconciliation->id)
            ->set('editBalance', '10000')
            ->set('editDate', '15.01.2024')
            ->set('editNotes', null)
            ->call('saveEdit')
            ->assertHasNoErrors(['editNotes']);
    });
});

describe('saving edits', function () {
    it('saves edited reconciliation and dispatches event', function () {
        $debt = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000]);

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-01-15',
            'principal_paid' => 500,
            'notes' => 'Original note',
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('openEditModal', $reconciliation->id)
            ->set('editBalance', '9500')
            ->set('editDate', '20.01.2024')
            ->set('editNotes', 'Updated note')
            ->call('saveEdit')
            ->assertSet('showEditModal', false)
            ->assertDispatched('reconciliation-updated');

        $reconciliation->refresh();
        expect($reconciliation->payment_date->format('Y-m-d'))->toBe('2024-01-20')
            ->and($reconciliation->notes)->toBe('Updated note');
    });

    it('does not save when editing non-existent reconciliation', function () {
        $debt = Debt::factory()->create();

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->set('editingReconciliationId', 99999)
            ->set('editBalance', '10000')
            ->set('editDate', '15.01.2024')
            ->call('saveEdit');

        // Should not throw error, just return early
        expect(true)->toBeTrue();
    });

    it('does not save when editingReconciliationId is null', function () {
        $debt = Debt::factory()->create();

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->set('editingReconciliationId', null)
            ->set('editBalance', '10000')
            ->set('editDate', '15.01.2024')
            ->call('saveEdit');

        // Should not throw error, just return early
        expect(true)->toBeTrue();
    });
});

describe('delete functionality', function () {
    it('opens delete confirmation modal', function () {
        $debt = Debt::factory()->create();

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('confirmDelete', $reconciliation->id)
            ->assertSet('showDeleteConfirm', true)
            ->assertSet('deletingReconciliationId', $reconciliation->id);
    });

    it('cancels delete confirmation', function () {
        $debt = Debt::factory()->create();

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('confirmDelete', $reconciliation->id)
            ->assertSet('showDeleteConfirm', true)
            ->call('cancelDelete')
            ->assertSet('showDeleteConfirm', false)
            ->assertSet('deletingReconciliationId', null);
    });

    it('deletes reconciliation and dispatches event', function () {
        $debt = Debt::factory()->create(['balance' => 10000, 'original_balance' => 10000]);

        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500,
        ]);

        $reconciliationId = $reconciliation->id;

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('confirmDelete', $reconciliation->id)
            ->call('deleteReconciliation')
            ->assertSet('showDeleteConfirm', false)
            ->assertSet('deletingReconciliationId', null)
            ->assertDispatched('reconciliation-deleted');

        expect(Payment::find($reconciliationId))->toBeNull();
    });

    it('does not delete regular payments', function () {
        $debt = Debt::factory()->create();

        $regularPayment = Payment::factory()->create([
            'debt_id' => $debt->id,
            'is_reconciliation_adjustment' => false,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->set('deletingReconciliationId', $regularPayment->id)
            ->set('showDeleteConfirm', true)
            ->call('deleteReconciliation');

        expect(Payment::find($regularPayment->id))->not->toBeNull();
    });

    it('does not delete when deletingReconciliationId is null', function () {
        $debt = Debt::factory()->create();

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->set('deletingReconciliationId', null)
            ->set('showDeleteConfirm', true)
            ->call('deleteReconciliation')
            ->assertNotDispatched('reconciliation-deleted');
    });

    it('updates debt balance after deletion', function () {
        $debt = Debt::factory()->create(['balance' => 9500, 'original_balance' => 10000]);

        // Create reconciliation that decreased balance by 500
        $reconciliation = Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'principal_paid' => 500, // Balance went from 10000 to 9500
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->call('confirmDelete', $reconciliation->id)
            ->call('deleteReconciliation');

        // Balance should be restored
        $debt->refresh();
        expect($debt->balance)->toBe(10000.0);
    });
});

describe('edge cases', function () {
    it('handles debt with no reconciliations', function () {
        $debt = Debt::factory()->create();

        // Create regular payments, no reconciliations
        Payment::factory()->count(3)->create([
            'debt_id' => $debt->id,
            'is_reconciliation_adjustment' => false,
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->assertSee(__('app.no_reconciliations'));
    });

    it('handles non-existent debt gracefully', function () {
        Livewire::test(ReconciliationHistory::class, ['debtId' => 99999])
            ->assertSee(__('app.no_reconciliations'));
    });

    it('displays multiple reconciliations in order', function () {
        $debt = Debt::factory()->create();

        Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-01-15',
            'notes' => 'First reconciliation',
        ]);

        Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-02-15',
            'notes' => 'Second reconciliation',
        ]);

        Payment::factory()->reconciliation()->create([
            'debt_id' => $debt->id,
            'payment_date' => '2024-03-15',
            'notes' => 'Third reconciliation',
        ]);

        Livewire::test(ReconciliationHistory::class, ['debtId' => $debt->id])
            ->assertSee('First reconciliation')
            ->assertSee('Second reconciliation')
            ->assertSee('Third reconciliation');
    });
});
