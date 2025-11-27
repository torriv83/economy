<div>
    @php
        $hasAnyLoans = count($this->availableLoans) > 0;
        $hasRepayments = count($this->allRepayments) > 0;
    @endphp

    @if ($hasAnyLoans)
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('app.all_repayments') }}</h2>

                    <div class="flex items-center gap-3">
                        <label for="loan-filter" class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ __('app.filter') }}:</label>
                        <select
                            id="loan-filter"
                            wire:model.live="selectedLoanId"
                            class="block w-full sm:w-auto min-w-[200px] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 py-2 px-3 cursor-pointer">
                            <option value="">{{ __('app.all_loans') }}</option>
                            @foreach ($this->availableLoans as $loan)
                                <option value="{{ $loan['id'] }}">{{ $loan['name'] }}</option>
                            @endforeach
                        </select>
                        @if ($selectedLoanId)
                            <button
                                wire:click="clearFilter"
                                class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline whitespace-nowrap">
                                {{ __('app.clear') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            @if ($hasRepayments)
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($this->allRepayments as $repayment)
                        <div wire:key="repayment-{{ $repayment['id'] }}" class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $repayment['loan_name'] }}</p>
                                    @if ($repayment['notes'])
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $repayment['notes'] }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ $repayment['paid_at'] }}</p>
                                </div>
                                <div class="ml-4 flex items-center gap-3">
                                    <p class="text-lg font-bold {{ $repayment['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $repayment['amount'] >= 0 ? '+' : '' }}{{ number_format($repayment['amount'], 0, ',', ' ') }} kr
                                    </p>
                                    <div class="flex items-center gap-1">
                                        <button
                                            wire:click="openEditModal({{ $repayment['id'] }})"
                                            class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors cursor-pointer"
                                            title="{{ __('app.edit') }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            wire:click="confirmDelete({{ $repayment['id'] }}, '{{ addslashes($repayment['loan_name']) }}')"
                                            class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors cursor-pointer"
                                            title="{{ __('app.delete') }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center">
                    <div class="max-w-sm mx-auto">
                        <div class="h-16 w-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ __('app.no_repayments_for_filter') }}</h2>
                        <p class="text-gray-600 dark:text-gray-400">{{ __('app.try_different_filter') }}</p>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if (count($this->paidOffLoans) > 0)
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('app.paid_off_loans') }}</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($this->paidOffLoans as $loan)
                        <div class="bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800 rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $loan['name'] }}</h3>
                            </div>
                            @if ($loan['description'])
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $loan['description'] }}</p>
                            @endif
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                <span class="font-medium">{{ __('app.amount') }}:</span> {{ number_format($loan['original_amount'], 0, ',', ' ') }} kr
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ __('app.created') }}: {{ $loan['created_at'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if (!$hasAnyLoans && count($this->paidOffLoans) === 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="max-w-sm mx-auto">
                <div class="h-16 w-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ __('app.no_history_yet') }}</h2>
                <p class="text-gray-600 dark:text-gray-400">{{ __('app.repayments_will_appear') }}</p>
            </div>
        </div>
    @endif

    {{-- Edit Repayment Modal --}}
    @if ($showEditModal)
        <div
            x-data="{ show: @entangle('showEditModal') }"
            x-show="show"
            x-on:keydown.escape.window="show = false"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
        >
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/60 dark:bg-black/80 transition-opacity cursor-pointer"
                x-on:click="show = false"
            ></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        x-show="show"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-900 text-left shadow-2xl ring-1 ring-black/10 dark:ring-white/10 transition-all sm:my-8 sm:w-full sm:max-w-lg"
                    >
                        <form wire:submit="updateRepayment">
                            <div class="bg-white dark:bg-gray-900 px-4 pb-4 pt-5 sm:p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('app.edit_repayment') }}</h3>

                                <div class="space-y-4">
                                    <div>
                                        <label for="editAmount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.amount_kr') }}</label>
                                        <input
                                            type="number"
                                            id="editAmount"
                                            wire:model="editAmount"
                                            step="0.01"
                                            min="0.01"
                                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 py-2.5 px-3"
                                        >
                                        @error('editAmount') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label for="editPaidAt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.repayment_date') }}</label>
                                        <input
                                            type="datetime-local"
                                            id="editPaidAt"
                                            wire:model="editPaidAt"
                                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 py-2.5 px-3"
                                        >
                                        @error('editPaidAt') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label for="editNotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.notes_optional') }}</label>
                                        <textarea
                                            id="editNotes"
                                            wire:model="editNotes"
                                            rows="3"
                                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 py-2.5 px-3"
                                        ></textarea>
                                        @error('editNotes') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-3">
                                <button
                                    type="submit"
                                    class="inline-flex w-full justify-center rounded-lg bg-blue-600 dark:bg-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 transition-colors cursor-pointer sm:w-auto"
                                >
                                    {{ __('app.update_repayment') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="closeEditModal"
                                    class="mt-3 inline-flex w-full justify-center rounded-lg bg-white dark:bg-gray-600 px-4 py-2.5 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 focus:ring-offset-2 transition-colors cursor-pointer sm:mt-0 sm:w-auto"
                                >
                                    {{ __('app.cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    <x-delete-confirmation-modal
        wire:model="showDeleteModal"
        :title="__('app.confirm_delete_repayment')"
        :message="__('app.delete_repayment_warning')"
        onConfirm="deleteRepayment"
    />
</div>
