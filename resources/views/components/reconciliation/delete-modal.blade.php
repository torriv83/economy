{{-- Reconciliation Delete Confirmation Modal - Used by HasReconciliationModals trait --}}
@if ($showDeleteConfirm)
    <div
        x-data="{ show: true }"
        x-show="show"
        x-cloak
        @keydown.escape.window="$wire.cancelDelete()"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="delete-modal-title"
        role="dialog"
        aria-modal="true"
    >
        {{-- Background overlay --}}
        <div
            class="fixed inset-0 z-40 bg-black/50 transition-opacity cursor-pointer"
            aria-hidden="true"
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            wire:click="cancelDelete"
        ></div>

        <div class="relative z-50 flex items-center justify-center min-h-screen p-4">
            <div
                class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all"
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                @click.stop
            >
                <div class="bg-white dark:bg-gray-800 px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div class="h-12 w-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center shrink-0">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="delete-modal-title">
                                {{ __('app.delete_reconciliation_confirm') }}
                            </h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('app.delete_reconciliation_warning') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                    <button
                        type="button"
                        wire:click="cancelDelete"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors cursor-pointer"
                    >
                        {{ __('app.cancel') }}
                    </button>
                    <button
                        type="button"
                        wire:click="deleteReconciliation"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white font-medium focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors cursor-pointer"
                    >
                        <span wire:loading.remove wire:target="deleteReconciliation">{{ __('app.delete') }}</span>
                        <span wire:loading wire:target="deleteReconciliation" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('app.deleting') }}...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
