{{-- Reconciliation Edit Modal - Used by HasReconciliationModals trait --}}
@if ($showEditModal)
    <div
        x-data="{ show: true }"
        x-show="show"
        x-cloak
        @keydown.escape.window="$wire.closeEditModal()"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="edit-modal-title"
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
            wire:click="closeEditModal"
        ></div>

        <div class="relative z-50 flex items-center justify-center min-h-screen p-4">
            <div
                class="w-full max-w-md bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all"
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                @click.stop
            >
                <form wire:submit.prevent="saveEdit">
                    {{-- Header --}}
                    <div class="bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white" id="edit-modal-title">
                                {{ __('app.edit_reconciliation') }}
                            </h3>
                            <button
                                type="button"
                                wire:click="closeEditModal"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg p-1 transition-colors cursor-pointer"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="bg-white dark:bg-gray-800 px-6 py-5">
                        <div class="space-y-5">
                            {{-- Balance Input --}}
                            <div>
                                <label for="editBalance" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('app.actual_balance') }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="number"
                                        id="editBalance"
                                        wire:model="editBalance"
                                        step="0.01"
                                        min="0"
                                        placeholder="{{ __('app.actual_balance_placeholder') }}"
                                        class="w-full px-4 py-3 pr-14 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors duration-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none @error('editBalance') border-red-500 dark:border-red-400 @enderror"
                                    >
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                        <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">NOK</span>
                                    </div>
                                </div>
                                @error('editBalance')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Date Input --}}
                            <div>
                                <label for="editDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('app.reconciliation_date') }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="editDate"
                                    wire:model="editDate"
                                    placeholder="DD.MM.YYYY"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors duration-200 @error('editDate') border-red-500 dark:border-red-400 @enderror"
                                >
                                @error('editDate')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Notes Input --}}
                            <div>
                                <label for="editNotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('app.notes') }} ({{ __('app.optional') }})
                                </label>
                                <textarea
                                    id="editNotes"
                                    wire:model="editNotes"
                                    rows="3"
                                    placeholder="{{ __('app.reconciliation_notes_placeholder') }}"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors duration-200 @error('editNotes') border-red-500 dark:border-red-400 @enderror"
                                ></textarea>
                                @error('editNotes')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button
                            type="button"
                            wire:click="closeEditModal"
                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors cursor-pointer"
                        >
                            {{ __('app.cancel') }}
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="saveEdit">{{ __('app.save') }}</span>
                            <span wire:loading wire:target="saveEdit" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('app.saving') }}...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
