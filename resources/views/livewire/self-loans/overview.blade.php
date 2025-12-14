<div wire:init="loadData">
    @if ($isLoading)
        <div class="animate-pulse space-y-6">
            {{-- Summary Card Skeleton --}}
            <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6">
                <div class="flex items-center justify-between">
                    <div class="space-y-3">
                        <div class="h-4 w-32 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        <div class="h-8 w-48 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        <div class="h-4 w-24 bg-slate-200 dark:bg-slate-700 rounded"></div>
                    </div>
                    <div class="w-16 h-16 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
                </div>
            </div>

            {{-- Loan Cards Skeleton Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @for ($i = 0; $i < 3; $i++)
                    <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6 space-y-4">
                        <div class="h-6 w-3/4 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        <div class="h-4 w-full bg-slate-200 dark:bg-slate-700 rounded"></div>
                        <div class="h-2 w-full bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                        <div class="space-y-2">
                            <div class="h-4 w-1/2 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div class="h-4 w-2/3 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-4">
                            <div class="h-10 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
                            <div class="h-10 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    @else
        @if (session('message'))
            <div class="mb-6 premium-card rounded-xl border border-emerald-200 dark:border-emerald-800/50 px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ session('message') }}</p>
                </div>
            </div>
        @endif

        @if (count($this->selfLoans) > 0)
        {{-- Summary Card --}}
        <div class="mb-8 premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">
                        {{ __('app.total_owed_to_self') }}
                    </p>
                    <p class="font-display font-bold text-3xl">
                        <span class="gradient-text">{{ number_format($this->totalBorrowed, 0, ',', ' ') }}</span>
                        <span class="text-lg font-normal text-slate-400 dark:text-slate-500">kr</span>
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                        {{ $this->loansCount }} {{ trans_choice('app.active_loans', $this->loansCount) }}
                    </p>
                </div>
                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-emerald-500/10 to-cyan-500/10 dark:from-emerald-500/20 dark:to-cyan-500/20 flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Self-Loans Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($this->selfLoans as $loan)
                <div wire:key="loan-{{ $loan['id'] }}" class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 card-interactive overflow-hidden">
                    <div class="p-6">
                        {{-- Loan Name with Edit Icon --}}
                        <div class="mb-4 flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-display font-semibold text-lg text-slate-900 dark:text-white">
                                    {{ $loan['name'] }}
                                </h3>
                            </div>
                            <button
                                wire:click="openEditModal({{ $loan['id'] }})"
                                class="ml-2 p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                title="{{ __('app.edit') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>
                        </div>
                        @if ($loan['description'])
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 mb-4">
                                {{ $loan['description'] }}
                            </p>
                        @endif

                        {{-- Progress Bar --}}
                        @if ($loan['progress_percentage'] > 0)
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                        {{ __('app.repaid_progress') }}
                                    </span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ number_format($loan['progress_percentage'], 1, ',', ' ') }}%
                                    </span>
                                </div>
                                <div class="relative h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div
                                        class="absolute inset-y-0 left-0 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-full transition-all duration-500"
                                        style="width: {{ $loan['progress_percentage'] }}%"
                                    ></div>
                                </div>
                                <div class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ number_format($loan['total_repaid'], 0, ',', ' ') }} kr {{ __('app.of') }} {{ number_format($loan['original_amount'], 0, ',', ' ') }} kr {{ __('app.repaid') }}
                                </div>
                            </div>
                        @endif

                        {{-- Loan Details --}}
                        <div class="space-y-3 mb-3">
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.balance') }}</span>
                                <span class="font-display font-bold text-xl text-slate-900 dark:text-white">
                                    {{ number_format($loan['current_balance'], 0, ',', ' ') }} kr
                                </span>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.original_amount') }}</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">
                                    {{ number_format($loan['original_amount'], 0, ',', ' ') }} kr
                                </span>
                            </div>
                            <div class="flex justify-between items-baseline pt-3 border-t border-slate-200/50 dark:border-slate-700/50">
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('app.created') }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $loan['created_at'] }}
                                </span>
                            </div>
                            @if ($loan['ynab_account_name'] || $loan['ynab_category_name'])
                                <div class="flex justify-between items-baseline">
                                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('app.linked_to_ynab') }}</span>
                                    <span class="text-xs text-blue-600 dark:text-blue-400">
                                        {{ $loan['ynab_account_name'] ?? $loan['ynab_category_name'] }}
                                    </span>
                                </div>
                                @if ($loan['ynab_available'] !== null)
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('app.available_in_ynab') }}</span>
                                        <span class="text-xs text-emerald-600 dark:text-emerald-400">
                                            {{ number_format($loan['ynab_available'], 0, ',', ' ') }} kr
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                            <button
                                wire:click="openRepaymentModal({{ $loan['id'] }})"
                                class="btn-momentum px-4 py-2.5 text-sm font-medium rounded-xl transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                {{ __('app.add_repayment') }}
                            </button>
                            <button
                                wire:click="openWithdrawalModal({{ $loan['id'] }})"
                                class="px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xl transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                                {{ __('app.withdraw_more') }}
                            </button>
                            <button
                                type="button"
                                wire:click="confirmDelete({{ $loan['id'] }}, '{{ $loan['name'] }}')"
                                class="col-span-2 px-4 py-2.5 text-sm font-medium text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:text-rose-600 hover:border-rose-300 dark:hover:text-rose-400 dark:hover:border-rose-800 rounded-xl transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                                {{ __('app.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Add New Self-Loan Placeholder --}}
            <button
                wire:click="$parent.showCreate"
                class="premium-card rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-600 hover:border-emerald-500 dark:hover:border-emerald-500 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-all p-6 flex flex-col items-center justify-center min-h-[300px] group focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 cursor-pointer">
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 group-hover:bg-gradient-to-br group-hover:from-emerald-500/10 group-hover:to-cyan-500/10 dark:group-hover:from-emerald-500/20 dark:group-hover:to-cyan-500/20 rounded-xl flex items-center justify-center mb-4 transition-colors">
                    <svg class="w-8 h-8 text-slate-400 dark:text-slate-500 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </div>
                <h3 class="font-display font-semibold text-lg text-slate-600 dark:text-slate-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                    {{ __('app.create_self_loan') }}
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-500 mt-2 text-center">
                    {{ __('app.click_to_add_self_loan') }}
                </p>
            </button>
        </div>

    @else
        {{-- Empty State - Create Self-Loan Placeholder --}}
        <button
            wire:click="$parent.showCreate"
            class="w-full premium-card rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-600 hover:border-emerald-500 dark:hover:border-emerald-500 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-all p-12 flex flex-col items-center justify-center group focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 cursor-pointer">
            <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 group-hover:bg-gradient-to-br group-hover:from-emerald-500/10 group-hover:to-cyan-500/10 dark:group-hover:from-emerald-500/20 dark:group-hover:to-cyan-500/20 rounded-2xl flex items-center justify-center mb-6 transition-colors">
                <svg class="w-10 h-10 text-slate-400 dark:text-slate-500 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </div>
            <h2 class="font-display font-semibold text-xl text-slate-600 dark:text-slate-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors mb-2">
                {{ __('app.create_self_loan') }}
            </h2>
            <p class="text-slate-500 dark:text-slate-500 text-center">
                {{ __('app.no_self_loans_message') }}
            </p>
        </button>
    @endif
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

                    {{-- YNAB Connection --}}
                    @if ($this->isYnabConfigured)
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                                {{ __('app.link_to_ynab_optional') }}
                            </label>

                            <div class="space-y-3">
                                {{-- No connection --}}
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 dark:has-[:checked]:bg-emerald-900/20">
                                    <input type="radio" wire:model.live="editYnabConnectionType" value="none" class="text-emerald-500 focus:ring-emerald-500">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('app.no_ynab_connection') }}</span>
                                </label>

                                {{-- Account connection --}}
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 dark:has-[:checked]:bg-emerald-900/20">
                                    <input type="radio" wire:model.live="editYnabConnectionType" value="account" class="text-emerald-500 focus:ring-emerald-500">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('app.link_to_ynab_account') }}</span>
                                </label>

                                @if ($editYnabConnectionType === 'account')
                                    <div class="ml-7">
                                        <select
                                            wire:model="editYnabAccountId"
                                            class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-colors">
                                            <option value="">{{ __('app.select_account') }}</option>
                                            @foreach ($this->ynabAccounts as $account)
                                                <option value="{{ $account['id'] }}">
                                                    {{ $account['name'] }} ({{ number_format($account['balance'], 0, ',', ' ') }} kr)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                {{-- Category connection --}}
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 dark:has-[:checked]:bg-emerald-900/20">
                                    <input type="radio" wire:model.live="editYnabConnectionType" value="category" class="text-emerald-500 focus:ring-emerald-500">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('app.link_to_ynab_category') }}</span>
                                </label>

                                @if ($editYnabConnectionType === 'category')
                                    <div class="ml-7">
                                        <select
                                            wire:model="editYnabCategoryId"
                                            class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-colors">
                                            <option value="">{{ __('app.select_category') }}</option>
                                            @foreach ($this->ynabCategories as $category)
                                                <option value="{{ $category['id'] }}">
                                                    {{ $category['group_name'] }}: {{ $category['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>

                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                {{ __('app.ynab_connection_help') }}
                            </p>
                        </div>
                    @endif
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
