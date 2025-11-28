<div>
    @if (session('message'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if (count($this->selfLoans) > 0)
        {{-- Summary Card --}}
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                        {{ __('app.total_owed_to_self') }}
                    </p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($this->totalBorrowed, 0, ',', ' ') }} kr
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        {{ $this->loansCount }} {{ trans_choice('app.active_loans', $this->loansCount) }}
                    </p>
                </div>
                <div class="h-16 w-16 bg-teal-100 dark:bg-teal-900/20 rounded-lg flex items-center justify-center">
                    <svg class="h-8 w-8 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Self-Loans Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($this->selfLoans as $loan)
                <div wire:key="loan-{{ $loan['id'] }}" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                    <div class="p-6">
                        {{-- Loan Name with Edit Icon --}}
                        <div class="mb-4 flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $loan['name'] }}
                                </h3>
                            </div>
                            <button
                                wire:click="openEditModal({{ $loan['id'] }})"
                                class="ml-2 p-1.5 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2 rounded"
                                title="{{ __('app.edit') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </button>
                        </div>
                        @if ($loan['description'])
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 mb-4">
                                {{ $loan['description'] }}
                            </p>
                        @endif

                        {{-- Progress Bar --}}
                        @if ($loan['progress_percentage'] > 0)
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                        {{ __('app.repaid_progress') }}
                                    </span>
                                    <span class="text-xs font-bold text-teal-600 dark:text-teal-400">
                                        {{ number_format($loan['progress_percentage'], 1, ',', ' ') }}%
                                    </span>
                                </div>
                                <div class="relative h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        class="absolute inset-y-0 left-0 bg-gradient-to-r from-teal-500 to-teal-600 dark:from-teal-600 dark:to-teal-700 rounded-full transition-all duration-500"
                                        style="width: {{ $loan['progress_percentage'] }}%"
                                    ></div>
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($loan['total_repaid'], 0, ',', ' ') }} kr {{ __('app.of') }} {{ number_format($loan['original_amount'], 0, ',', ' ') }} kr {{ __('app.repaid') }}
                                </div>
                            </div>
                        @endif

                        {{-- Loan Details --}}
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.balance') }}</span>
                                <span class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ number_format($loan['current_balance'], 0, ',', ' ') }} kr
                                </span>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.original_amount') }}</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ number_format($loan['original_amount'], 0, ',', ' ') }} kr
                                </span>
                            </div>
                            <div class="flex justify-between items-baseline pt-2 border-t border-gray-200 dark:border-gray-700">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('app.created') }}</span>
                                <span class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ $loan['created_at'] }}
                                </span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="grid grid-cols-2 gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button
                                wire:click="openRepaymentModal({{ $loan['id'] }})"
                                class="px-3 py-2 text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600 rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2">
                                {{ __('app.add_repayment') }}
                            </button>
                            <button
                                wire:click="openWithdrawalModal({{ $loan['id'] }})"
                                class="px-3 py-2 text-sm font-medium text-teal-700 dark:text-teal-300 bg-teal-100 hover:bg-teal-200 dark:bg-teal-900/30 dark:hover:bg-teal-900/50 rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2">
                                {{ __('app.withdraw_more') }}
                            </button>
                            <button
                                type="button"
                                wire:click="confirmDelete({{ $loan['id'] }}, '{{ $loan['name'] }}')"
                                class="col-span-2 px-3 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-500 dark:focus:ring-red-400 focus:ring-offset-2">
                                {{ __('app.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Add New Self-Loan Placeholder --}}
            <button
                wire:click="$parent.showCreate"
                class="bg-white dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-teal-500 dark:hover:border-teal-500 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all p-6 flex flex-col items-center justify-center min-h-[300px] group focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2 cursor-pointer">
                <div class="h-16 w-16 bg-gray-100 dark:bg-gray-700 group-hover:bg-teal-100 dark:group-hover:bg-teal-900/30 rounded-full flex items-center justify-center mb-4 transition-colors">
                    <svg class="h-8 w-8 text-gray-400 dark:text-gray-500 group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-400 group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                    {{ __('app.create_self_loan') }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-2 text-center">
                    {{ __('app.click_to_add_self_loan') }}
                </p>
            </button>
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="max-w-sm mx-auto">
                <div class="h-16 w-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    {{ __('app.no_active_self_loans') }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ __('app.no_self_loans_message') }}
                </p>
            </div>
        </div>
    @endif

    {{-- Repayment Modal --}}
    @if ($showRepaymentModal)
        <x-modal wire:model="showRepaymentModal" max-width="md">
            <x-modal.form
                :title="__('app.add_repayment')"
                on-close="closeRepaymentModal"
                on-submit="addRepayment"
                :submit-text="__('app.add_repayment')"
                :loading="true"
                loading-target="addRepayment"
            >
                <div class="space-y-4">
                    @include('components.form.amount-input', [
                        'id' => 'repayment-amount',
                        'label' => __('app.amount_kr'),
                        'hint' => __('app.up_to_amount', ['amount' => number_format($this->selectedLoanBalance, 2, ',', ' ')]),
                        'model' => 'repaymentAmount',
                        'required' => true,
                        'error' => $errors->first('repaymentAmount'),
                    ])

                    @include('components.form.date-picker', [
                        'id' => 'repayment-date',
                        'label' => __('app.payment_date'),
                        'model' => 'repaymentDate',
                        'value' => $repaymentDate,
                        'maxDate' => date('Y-m-d'),
                        'required' => true,
                        'error' => $errors->first('repaymentDate'),
                    ])

                    @include('components.form.textarea', [
                        'id' => 'repayment-notes',
                        'label' => __('app.notes_optional'),
                        'model' => 'repaymentNotes',
                        'error' => $errors->first('repaymentNotes'),
                    ])
                </div>
            </x-modal.form>
        </x-modal>
    @endif

    {{-- Withdrawal Modal --}}
    @if ($showWithdrawalModal)
        <x-modal wire:model="showWithdrawalModal" max-width="md">
            <x-modal.form
                :title="__('app.withdraw_more')"
                on-close="closeWithdrawalModal"
                on-submit="addWithdrawal"
                :submit-text="__('app.add_withdrawal')"
                :loading="true"
                loading-target="addWithdrawal"
            >
                <div class="space-y-4">
                    @include('components.form.amount-input', [
                        'id' => 'withdrawal-amount',
                        'label' => __('app.amount_kr'),
                        'model' => 'withdrawalAmount',
                        'required' => true,
                        'error' => $errors->first('withdrawalAmount'),
                    ])

                    @include('components.form.date-picker', [
                        'id' => 'withdrawal-date',
                        'label' => __('app.withdrawal_date'),
                        'model' => 'withdrawalDate',
                        'value' => $withdrawalDate,
                        'maxDate' => date('Y-m-d'),
                        'required' => true,
                        'error' => $errors->first('withdrawalDate'),
                    ])

                    @include('components.form.textarea', [
                        'id' => 'withdrawal-notes',
                        'label' => __('app.notes_optional'),
                        'model' => 'withdrawalNotes',
                        'error' => $errors->first('withdrawalNotes'),
                    ])
                </div>
            </x-modal.form>
        </x-modal>
    @endif

    {{-- Edit Modal --}}
    @if ($showEditModal)
        <x-modal wire:model="showEditModal" max-width="md">
            <x-modal.form
                :title="__('app.edit_self_loan')"
                on-close="closeEditModal"
                on-submit="updateLoan"
                :submit-text="__('app.update_self_loan')"
                :loading="true"
                loading-target="updateLoan"
            >
                <div class="space-y-4">
                    @include('components.form.text-input', [
                        'id' => 'edit-name',
                        'label' => __('app.name'),
                        'model' => 'editName',
                        'required' => true,
                        'error' => $errors->first('editName'),
                    ])

                    @include('components.form.textarea', [
                        'id' => 'edit-description',
                        'label' => __('app.description_optional'),
                        'model' => 'editDescription',
                        'error' => $errors->first('editDescription'),
                    ])

                    @include('components.form.amount-input', [
                        'id' => 'edit-original-amount',
                        'label' => __('app.original_amount_kr'),
                        'model' => 'editOriginalAmount',
                        'required' => true,
                        'error' => $errors->first('editOriginalAmount'),
                    ])
                </div>
            </x-modal.form>
        </x-modal>
    @endif

    {{-- Delete Confirmation Modal --}}
    <x-delete-confirmation-modal
        wire:model="showDeleteModal"
        :title="__('app.delete_self_loan_confirm', ['name' => $recordNameToDelete])"
        :message="__('app.delete_self_loan_warning')"
        on-confirm="executeDelete"
    />
</div>
