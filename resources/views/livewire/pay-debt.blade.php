<div>
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    @if ($this->currentMonth)
        @php
            $monthData = $this->currentMonth;
            $monthNumber = $monthData['month'];
            $debts = $this->debts;
            $allPaid = $this->isAllPaid();
        @endphp

        {{-- Month Header --}}
        <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-display text-xl font-bold text-slate-900 dark:text-white">
                    {{ $monthData['monthName'] }}
                </h2>

                {{-- Mark all button --}}
                <button
                    wire:click="markAllAsPaid"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 {{ $allPaid ? 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-red-100 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400' : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/50' }}"
                >
                    @if ($allPaid)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        {{ __('app.unmark_all_as_paid') }}
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ __('app.mark_all_as_paid') }}
                    @endif
                </button>
            </div>

            {{-- Payment List --}}
            <div class="space-y-3">
                @foreach ($monthData['payments'] as $payment)
                    @php
                        $debt = $debts->get($payment['name']);
                        $debtId = $debt?->id;
                        $isPaid = $debtId && app(App\Services\PaymentService::class)->paymentExists($debtId, $monthNumber);
                        $actualPayment = $isPaid ? app(App\Services\PaymentService::class)->getPayment($debtId, $monthNumber) : null;
                        $displayAmount = $actualPayment ? $actualPayment->actual_amount : $payment['amount'];
                        $key = "{$monthNumber}_{$debtId}";
                    @endphp

                    @if ($payment['amount'] > 0 && $debtId)
                        <div
                            wire:key="payment-{{ $monthNumber }}-{{ $debtId }}"
                            class="flex items-center gap-4 p-4 rounded-xl transition-all duration-200 {{ $isPaid ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700' }}"
                        >
                            {{-- Checkbox --}}
                            <button
                                wire:click="togglePayment({{ $monthNumber }}, {{ $debtId }})"
                                class="flex-shrink-0 w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all duration-200 {{ $isPaid ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 dark:border-slate-600 hover:border-emerald-400 dark:hover:border-emerald-500' }}"
                            >
                                @if ($isPaid)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </button>

                            {{-- Debt Name --}}
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-slate-900 dark:text-white {{ $isPaid ? 'line-through opacity-60' : '' }}">
                                    {{ $payment['name'] }}
                                </div>
                                @if ($payment['isPriority'] ?? false)
                                    <span class="inline-flex items-center text-xs font-medium text-cyan-600 dark:text-cyan-400">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('app.priority_debt') }}
                                    </span>
                                @endif
                            </div>

                            {{-- Amount --}}
                            <div class="flex-shrink-0 text-right">
                                @if ($isPaid)
                                    {{-- Editable amount when paid --}}
                                    <div class="flex items-center gap-2" x-data="{ editing: false }">
                                        <template x-if="!editing">
                                            <button
                                                @click="editing = true; $nextTick(() => $refs.input.focus())"
                                                class="font-display font-semibold text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer"
                                                title="{{ __('app.edit_amount') }}"
                                            >
                                                {{ number_format($displayAmount, 0, ',', ' ') }} kr
                                            </button>
                                        </template>
                                        <template x-if="editing">
                                            <form wire:submit.prevent="updatePaymentAmount({{ $monthNumber }}, {{ $debtId }})" class="flex items-center gap-2">
                                                <input
                                                    x-ref="input"
                                                    type="number"
                                                    step="1"
                                                    wire:model="editingPayments.{{ $key }}"
                                                    @blur="editing = false"
                                                    @keydown.escape="editing = false"
                                                    class="w-24 px-2 py-1 text-right text-sm font-display font-semibold border border-emerald-300 dark:border-emerald-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                                    placeholder="{{ number_format($displayAmount, 0) }}"
                                                >
                                                <span class="text-sm text-slate-500 dark:text-slate-400">kr</span>
                                            </form>
                                        </template>
                                    </div>
                                @else
                                    <span class="font-display font-semibold text-slate-900 dark:text-white">
                                        {{ number_format($payment['amount'], 0, ',', ' ') }} kr
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Total --}}
            <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-slate-600 dark:text-slate-400">{{ __('app.total_debt') }}</span>
                    <span class="font-display text-lg font-bold text-slate-900 dark:text-white">
                        {{ number_format(collect($monthData['payments'])->sum('amount'), 0, ',', ' ') }} kr
                    </span>
                </div>
            </div>
        </div>
    @else
        {{-- No debts or all paid off --}}
        <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 mb-4">
                <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="font-display text-lg font-bold text-slate-900 dark:text-white mb-2">
                {{ __('app.no_debts') }}
            </h3>
            <p class="text-slate-500 dark:text-slate-400">
                {{ __('app.no_debts_message') }}
            </p>
        </div>
    @endif
</div>
