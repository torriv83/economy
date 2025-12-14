<div wire:init="loadData">
    @if ($isLoading)
        <div class="animate-pulse space-y-6">
            {{-- Main Card Skeleton --}}
            <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
                {{-- Header Skeleton --}}
                <div class="px-6 py-4 border-b border-slate-200/50 dark:border-slate-700/50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="h-6 w-40 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        <div class="flex items-center gap-3">
                            <div class="h-4 w-12 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div class="h-10 w-48 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
                        </div>
                    </div>
                </div>

                {{-- Repayment List Skeleton --}}
                <div class="divide-y divide-slate-200/50 dark:divide-slate-700/50">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="px-6 py-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 space-y-2">
                                    <div class="h-5 w-32 bg-slate-200 dark:bg-slate-700 rounded"></div>
                                    <div class="h-4 w-48 bg-slate-200 dark:bg-slate-700 rounded"></div>
                                    <div class="h-3 w-24 bg-slate-200 dark:bg-slate-700 rounded"></div>
                                </div>
                                <div class="h-6 w-20 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @else
        @php
            $hasAnyLoans = count($this->availableLoans) > 0;
            $hasRepayments = count($this->allRepayments) > 0;
        @endphp

        @if ($hasAnyLoans)
        <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
            {{-- Header with Filter --}}
            <div class="px-6 py-4 border-b border-slate-200/50 dark:border-slate-700/50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 class="font-display font-semibold text-lg text-slate-900 dark:text-white">{{ __('app.all_repayments') }}</h2>

                    <div class="flex items-center gap-3">
                        <label for="loan-filter" class="text-sm font-medium text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ __('app.filter') }}:</label>
                        <select
                            id="loan-filter"
                            wire:model.live="selectedLoanId"
                            class="block w-full sm:w-auto min-w-[200px] rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-4 cursor-pointer">
                            <option value="">{{ __('app.all_loans') }}</option>
                            @foreach ($this->availableLoans as $loan)
                                <option value="{{ $loan['id'] }}">{{ $loan['name'] }}</option>
                            @endforeach
                        </select>
                        @if ($selectedLoanId)
                            <button
                                wire:click="clearFilter"
                                class="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 hover:underline whitespace-nowrap transition-colors">
                                {{ __('app.clear') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            @if ($hasRepayments)
                <div class="divide-y divide-slate-200/50 dark:divide-slate-700/50">
                    @foreach ($this->allRepayments as $repayment)
                        <div wire:key="repayment-{{ $repayment['id'] }}" class="px-6 py-4 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $repayment['loan_name'] }}</p>
                                    @if ($repayment['notes'])
                                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $repayment['notes'] }}</p>
                                    @endif
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">{{ $repayment['paid_at'] }}</p>
                                </div>
                                <div class="ml-4 flex items-center gap-3">
                                    <p class="font-display font-bold text-lg {{ $repayment['amount'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $repayment['amount'] >= 0 ? '+' : '' }}{{ number_format($repayment['amount'], 0, ',', ' ') }} kr
                                    </p>
                                    <div class="flex items-center gap-1">
                                        <button
                                            wire:click="openEditModal({{ $repayment['id'] }})"
                                            class="p-2 rounded-lg text-slate-400 hover:text-cyan-600 dark:hover:text-cyan-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                                            title="{{ __('app.edit') }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                        <button
                                            wire:click="confirmDelete({{ $repayment['id'] }}, '{{ addslashes($repayment['loan_name']) }}')"
                                            class="p-2 rounded-lg text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                                            title="{{ __('app.delete') }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- No repayments for filter --}}
                <div class="p-12 text-center">
                    <div class="max-w-sm mx-auto">
                        <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-cyan-500/10 to-emerald-500/10 dark:from-cyan-500/20 dark:to-emerald-500/20 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="font-display font-semibold text-xl text-slate-900 dark:text-white mb-2">{{ __('app.no_repayments_for_filter') }}</h2>
                        <p class="text-slate-500 dark:text-slate-400">{{ __('app.try_different_filter') }}</p>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Paid Off Loans Section --}}
    @if (count($this->paidOffLoans) > 0)
        <div class="mt-8 premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200/50 dark:border-slate-700/50">
                <h2 class="font-display font-semibold text-lg text-slate-900 dark:text-white">{{ __('app.paid_off_loans') }}</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($this->paidOffLoans as $loan)
                        <div class="premium-card rounded-xl border border-emerald-200/50 dark:border-emerald-800/30 bg-emerald-50/50 dark:bg-emerald-900/10 p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $loan['name'] }}</h3>
                            </div>
                            @if ($loan['description'])
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">{{ $loan['description'] }}</p>
                            @endif
                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                <span class="font-medium">{{ __('app.amount') }}:</span> {{ number_format($loan['original_amount'], 0, ',', ' ') }} kr
                            </p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">{{ __('app.created') }}: {{ $loan['created_at'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

        {{-- Empty State - No loans at all --}}
        @if (!$hasAnyLoans && count($this->paidOffLoans) === 0)
            <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-12 text-center">
                <div class="max-w-sm mx-auto">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-500/10 to-cyan-500/10 dark:from-emerald-500/20 dark:to-cyan-500/20 flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="font-display font-semibold text-xl text-slate-900 dark:text-white mb-2">{{ __('app.no_history_yet') }}</h2>
                    <p class="text-slate-500 dark:text-slate-400">{{ __('app.repayments_will_appear') }}</p>
                </div>
            </div>
        @endif
    @endif

    {{-- Edit Repayment Modal --}}
    @if ($showEditModal)
        <x-modal wire:model="showEditModal" max-width="md">
            <x-modal.form
                :title="__('app.edit_repayment')"
                on-close="closeEditModal"
                on-submit="updateRepayment"
                :submit-text="__('app.update_repayment')"
                :loading="true"
                loading-target="updateRepayment"
            >
                <div class="space-y-4">
                    @include('components.form.amount-input', [
                        'id' => 'editAmount',
                        'label' => __('app.amount_kr'),
                        'model' => 'editAmount',
                        'required' => true,
                        'error' => $errors->first('editAmount'),
                    ])

                    <div>
                        <label for="editPaidAt" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            {{ __('app.repayment_date') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="datetime-local"
                            id="editPaidAt"
                            wire:model="editPaidAt"
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-colors @error('editPaidAt') border-red-500 dark:border-red-400 @enderror"
                        >
                        @error('editPaidAt')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    @include('components.form.textarea', [
                        'id' => 'editNotes',
                        'label' => __('app.notes_optional'),
                        'model' => 'editNotes',
                        'error' => $errors->first('editNotes'),
                    ])
                </div>
            </x-modal.form>
        </x-modal>
    @endif

    {{-- Delete Confirmation Modal --}}
    <x-delete-confirmation-modal
        wire:model="showDeleteModal"
        :title="__('app.confirm_delete_repayment')"
        :message="__('app.delete_repayment_warning')"
        onConfirm="deleteRepayment"
    />
</div>
