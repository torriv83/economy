{{-- Reconciliation Delete Confirmation Modal - Used by HasReconciliationModals trait --}}
@if ($showDeleteConfirm)
    <x-modal wire:model="showDeleteConfirm" max-width="sm">
        <x-modal.confirmation
            :title="__('app.delete_reconciliation_confirm')"
            :message="__('app.delete_reconciliation_warning')"
            on-confirm="deleteReconciliation"
            on-cancel="cancelDelete"
            variant="danger"
            :loading="true"
            loading-target="deleteReconciliation"
        />
    </x-modal>
@endif
