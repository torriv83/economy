<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Debt;
use App\Models\Payment;
use App\Services\PaymentService;

trait HasReconciliationModals
{
    public bool $showEditModal = false;

    public ?int $editingReconciliationId = null;

    public string $editBalance = '';

    public string $editDate = '';

    public ?string $editNotes = null;

    public bool $showDeleteConfirm = false;

    public ?int $deletingReconciliationId = null;

    protected PaymentService $paymentService;

    public function bootHasReconciliationModals(PaymentService $paymentService): void
    {
        $this->paymentService = $paymentService;
    }

    /**
     * @return array<string, mixed>
     */
    protected function reconciliationRules(): array
    {
        return [
            'editBalance' => ['required', 'numeric', 'min:0'],
            'editDate' => ['required', 'date_format:d.m.Y'],
            'editNotes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function reconciliationMessages(): array
    {
        return [
            'editBalance.required' => __('app.validation_actual_balance_required'),
            'editBalance.numeric' => __('app.validation_actual_balance_numeric'),
            'editBalance.min' => __('app.validation_actual_balance_min'),
            'editDate.required' => __('app.validation_reconciliation_date_required'),
            'editDate.date_format' => __('app.validation_reconciliation_date_format'),
            'editNotes.max' => __('app.validation_reconciliation_notes_max'),
        ];
    }

    public function openEditModal(int $paymentId): void
    {
        $payment = Payment::find($paymentId);

        if (! $payment || ! $payment->is_reconciliation_adjustment) {
            return;
        }

        $this->editingReconciliationId = $paymentId;
        $this->editDate = $payment->payment_date->format('d.m.Y');
        $this->editNotes = $payment->notes;

        /** @var Debt $debt */
        $debt = $payment->debt;
        $this->editBalance = (string) $debt->balance;

        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingReconciliationId = null;
        $this->editBalance = '';
        $this->editDate = '';
        $this->editNotes = null;
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        $this->validate($this->reconciliationRules(), $this->reconciliationMessages());

        if (! $this->editingReconciliationId) {
            return;
        }

        $payment = Payment::find($this->editingReconciliationId);

        if (! $payment || ! $payment->is_reconciliation_adjustment) {
            return;
        }

        // Convert Norwegian date format (DD.MM.YYYY) to database format (YYYY-MM-DD)
        $dateObject = \DateTime::createFromFormat('d.m.Y', $this->editDate);
        $databaseDate = $dateObject ? $dateObject->format('Y-m-d') : now()->format('Y-m-d');

        $this->paymentService->updateReconciliation(
            $payment,
            (float) $this->editBalance,
            $databaseDate,
            $this->editNotes
        );

        $this->closeEditModal();
        $this->afterReconciliationSaved();
        $this->dispatch('reconciliation-updated');
    }

    public function confirmDelete(int $paymentId): void
    {
        $this->deletingReconciliationId = $paymentId;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deletingReconciliationId = null;
    }

    public function deleteReconciliation(): void
    {
        if (! $this->deletingReconciliationId) {
            return;
        }

        $payment = Payment::find($this->deletingReconciliationId);

        if (! $payment || ! $payment->is_reconciliation_adjustment) {
            return;
        }

        $this->paymentService->deleteReconciliation($payment);

        $this->cancelDelete();
        $this->afterReconciliationDeleted();
        $this->dispatch('reconciliation-deleted');
    }

    /**
     * Hook called after a reconciliation is saved
     */
    protected function afterReconciliationSaved(): void
    {
        // Override in component if needed
    }

    /**
     * Hook called after a reconciliation is deleted
     */
    protected function afterReconciliationDeleted(): void
    {
        // Override in component if needed
    }
}
