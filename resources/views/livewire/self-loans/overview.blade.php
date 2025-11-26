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
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/50" wire:click="closeRepaymentModal"></div>

                {{-- Modal --}}
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full" @click.stop>
                    {{-- Header --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ __('app.add_repayment') }}
                        </h3>
                        <button wire:click="closeRepaymentModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2 rounded">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Content --}}
                    <form wire:submit.prevent="addRepayment" class="p-6">
                        <div class="space-y-4">
                            {{-- Amount --}}
                            <div>
                                <label for="repayment-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('app.amount_kr') }} *
                                    <span class="font-normal text-gray-500 dark:text-gray-400">({{ __('app.up_to_amount', ['amount' => number_format($this->selectedLoanBalance, 2, ',', ' ')]) }})</span>
                                </label>
                                <input
                                    type="number"
                                    id="repayment-amount"
                                    wire:model="repaymentAmount"
                                    step="0.01"
                                    min="0.01"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                    required>
                                @error('repaymentAmount')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Date --}}
                            <div class="relative">
                                <label for="repayment-date-display" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('app.payment_date') }} *
                                </label>
                                <div class="relative" x-data="{
                                    displayDate: '{{ $repaymentDate }}',
                                    updateFromPicker(value) {
                                        if (value) {
                                            const parts = value.split('-');
                                            this.displayDate = `${parts[2]}.${parts[1]}.${parts[0]}`;
                                            $wire.set('repaymentDate', this.displayDate);
                                        }
                                    }
                                }">
                                    <input
                                        type="text"
                                        id="repayment-date-display"
                                        x-model="displayDate"
                                        readonly
                                        @click="$refs.datePicker.showPicker()"
                                        placeholder="dd.mm.åååå"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:border-transparent dark:bg-gray-700 dark:text-white cursor-pointer"
                                        required>
                                    <input
                                        type="date"
                                        x-ref="datePicker"
                                        @change="updateFromPicker($event.target.value)"
                                        max="{{ date('Y-m-d') }}"
                                        class="absolute inset-0 opacity-0 cursor-pointer"
                                        style="z-index: -1;">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                @error('repaymentDate')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label for="repayment-notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('app.notes_optional') }}
                                </label>
                                <textarea
                                    id="repayment-notes"
                                    wire:model="repaymentNotes"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                ></textarea>
                                @error('repaymentNotes')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-3 mt-6">
                            <button
                                type="button"
                                wire:click="closeRepaymentModal"
                                class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white text-sm font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 focus:ring-offset-2">
                                {{ __('app.cancel') }}
                            </button>
                            <button
                                type="submit"
                                class="flex-1 px-4 py-2 bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2">
                                {{ __('app.add_repayment') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Withdrawal Modal --}}
    @if ($showWithdrawalModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/50" wire:click="closeWithdrawalModal"></div>

                {{-- Modal --}}
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full" @click.stop>
                    {{-- Header --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ __('app.withdraw_more') }}
                        </h3>
                        <button wire:click="closeWithdrawalModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2 rounded">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Content --}}
                    <form wire:submit.prevent="addWithdrawal" class="p-6">
                        <div class="space-y-4">
                            {{-- Amount --}}
                            <div>
                                <label for="withdrawal-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('app.amount_kr') }} *
                                </label>
                                <input
                                    type="number"
                                    id="withdrawal-amount"
                                    wire:model="withdrawalAmount"
                                    step="0.01"
                                    min="0.01"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                    required>
                                @error('withdrawalAmount')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Date --}}
                            <div class="relative">
                                <label for="withdrawal-date-display" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('app.withdrawal_date') }} *
                                </label>
                                <div class="relative" x-data="{
                                    displayDate: '{{ $withdrawalDate }}',
                                    updateFromPicker(value) {
                                        if (value) {
                                            const parts = value.split('-');
                                            this.displayDate = `${parts[2]}.${parts[1]}.${parts[0]}`;
                                            $wire.set('withdrawalDate', this.displayDate);
                                        }
                                    }
                                }">
                                    <input
                                        type="text"
                                        id="withdrawal-date-display"
                                        x-model="displayDate"
                                        readonly
                                        @click="$refs.datePicker.showPicker()"
                                        placeholder="dd.mm.åååå"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:border-transparent dark:bg-gray-700 dark:text-white cursor-pointer"
                                        required>
                                    <input
                                        type="date"
                                        x-ref="datePicker"
                                        @change="updateFromPicker($event.target.value)"
                                        max="{{ date('Y-m-d') }}"
                                        class="absolute inset-0 opacity-0 cursor-pointer"
                                        style="z-index: -1;">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                @error('withdrawalDate')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label for="withdrawal-notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('app.notes_optional') }}
                                </label>
                                <textarea
                                    id="withdrawal-notes"
                                    wire:model="withdrawalNotes"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                ></textarea>
                                @error('withdrawalNotes')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-3 mt-6">
                            <button
                                type="button"
                                wire:click="closeWithdrawalModal"
                                class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white text-sm font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 focus:ring-offset-2">
                                {{ __('app.cancel') }}
                            </button>
                            <button
                                type="submit"
                                class="flex-1 px-4 py-2 bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2">
                                {{ __('app.add_withdrawal') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/50" wire:click="closeEditModal"></div>

                {{-- Modal --}}
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full" @click.stop>
                    {{-- Header --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ __('app.edit_self_loan') }}
                        </h3>
                        <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2 rounded">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Content --}}
                    <form wire:submit.prevent="updateLoan" class="p-6">
                        <div class="space-y-4">
                            {{-- Name --}}
                            <div>
                                <label for="edit-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('app.name') }} *
                                </label>
                                <input
                                    type="text"
                                    id="edit-name"
                                    wire:model="editName"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                    required>
                                @error('editName')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Description --}}
                            <div>
                                <label for="edit-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('app.description_optional') }}
                                </label>
                                <textarea
                                    id="edit-description"
                                    wire:model="editDescription"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                ></textarea>
                                @error('editDescription')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Original Amount --}}
                            <div>
                                <label for="edit-original-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ __('app.original_amount_kr') }} *
                                </label>
                                <input
                                    type="number"
                                    id="edit-original-amount"
                                    wire:model="editOriginalAmount"
                                    step="0.01"
                                    min="0.01"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                    required>
                                @error('editOriginalAmount')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-3 mt-6">
                            <button
                                type="button"
                                wire:click="closeEditModal"
                                class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors">
                                {{ __('app.cancel') }}
                            </button>
                            <button
                                type="submit"
                                class="flex-1 px-4 py-2 bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2">
                                {{ __('app.update_self_loan') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    <x-delete-confirmation-modal
        wire:model="showDeleteModal"
        :title="__('app.delete_self_loan_confirm', ['name' => $loanNameToDelete])"
        :message="__('app.delete_self_loan_warning')"
        on-confirm="deleteLoan"
    />
</div>
