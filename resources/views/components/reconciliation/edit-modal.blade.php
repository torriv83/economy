{{-- Reconciliation Edit Modal - Used by HasReconciliationModals trait --}}
@if ($showEditModal)
    <x-modal wire:model="showEditModal" max-width="md">
        <form wire:submit.prevent="saveEdit">
            <x-modal.header
                :title="__('app.edit_reconciliation')"
                on-close="closeEditModal"
            />

            <x-modal.body>
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
                                class="w-full px-4 py-2.5 pr-14 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none @error('editBalance') border-red-500 dark:border-red-400 @enderror"
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
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors @error('editDate') border-red-500 dark:border-red-400 @enderror"
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
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors @error('editNotes') border-red-500 dark:border-red-400 @enderror"
                        ></textarea>
                        @error('editNotes')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-modal.body>

            <x-modal.footer>
                <x-modal.button-secondary wire:click="closeEditModal">
                    {{ __('app.cancel') }}
                </x-modal.button-secondary>
                <x-modal.button-primary
                    type="submit"
                    :loading="true"
                    loading-target="saveEdit"
                    :loading-text="__('app.saving')"
                >
                    {{ __('app.save') }}
                </x-modal.button-primary>
            </x-modal.footer>
        </form>
    </x-modal>
@endif
