<div x-data="{
    order: @js(collect($this->debts)->pluck('id')->toArray()),
    updatePosition(item, position) {
        const itemId = parseInt(item);
        const currentIndex = this.order.indexOf(itemId);
        if (currentIndex > -1) {
            this.order.splice(currentIndex, 1);
        }
        this.order.splice(position, 0, itemId);
    },
    saveOrder() {
        $wire.updateOrder(this.order);
    }
}">
    {{-- Page Header with Actions --}}
    <x-page-header :title="__('app.debts_overview')" :subtitle="__('app.debts_overview_description')">
        <x-slot:actions>
            @if (!$reorderMode)
                @if ($ynabEnabled)
                    <button
                        type="button"
                        wire:click="checkYnab"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-cyan-50 hover:bg-cyan-100 dark:bg-cyan-900/20 dark:hover:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 font-medium rounded-xl transition-all cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 btn-lift">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        {{ __('app.check_ynab') }}
                    </button>
                @endif
                <button
                    type="button"
                    wire:click="enableReorderMode"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium rounded-xl transition-all cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 focus-visible:ring-offset-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    {{ __('app.reorder_debts') }}
                </button>
            @else
                <button
                    type="button"
                    wire:click="cancelReorder"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium rounded-xl transition-all cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 focus-visible:ring-offset-2">
                    {{ __('app.cancel') }}
                </button>
                <button
                    type="button"
                    x-on:click="saveOrder"
                    class="inline-flex items-center gap-2 px-5 py-2.5 btn-momentum rounded-xl cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                    {{ __('app.save_order') }}
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (count($this->debts) > 0)
        {{-- Hero Stats Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
            {{-- Total Debt Card --}}
            <div class="premium-card rounded-2xl p-6 stat-card">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                            {{ __('app.total_debt') }}
                        </p>
                        <p class="text-4xl font-display font-bold tracking-tight">
                            <span class="gradient-text">{{ number_format($this->totalDebt, 0, ',', ' ') }}</span>
                            <span class="text-xl font-normal text-slate-400 dark:text-slate-500">kr</span>
                        </p>
                        @if ($this->lastUpdated)
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-3 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ __('app.last_updated') }}: {{ $this->lastUpdated }}
                            </p>
                        @endif
                    </div>
                    {{-- Decorative Icon --}}
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-rose-500/10 to-orange-500/10 dark:from-rose-500/20 dark:to-orange-500/20 flex items-center justify-center">
                        <svg class="h-8 w-8 text-rose-500 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Debt Free Timeline Card --}}
            <div class="premium-card rounded-2xl p-6 stat-card">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">
                            {{ __('app.debt_free_in') }}
                        </p>
                        @if ($this->strategyEstimate)
                            <p class="text-4xl font-display font-bold tracking-tight">
                                <span class="gradient-text">
                                    @if ($this->strategyEstimate['years'] > 0)
                                        {{ $this->strategyEstimate['years'] }} {{ trans_choice('app.years', $this->strategyEstimate['years']) }}
                                    @endif
                                    @if ($this->strategyEstimate['months'] > 0)
                                        {{ $this->strategyEstimate['months'] }} {{ trans_choice('app.months', $this->strategyEstimate['months']) }}
                                    @endif
                                </span>
                            </p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-3">
                                <span>{{ __('app.with_chosen_strategy') }}</span>
                                @if ($this->payoffEstimate)
                                    <span class="text-xs text-slate-400 dark:text-slate-500 ml-1">({{ $this->payoffEstimate['years'] > 0 ? $this->payoffEstimate['years'] . ' ' . trans_choice('app.years', $this->payoffEstimate['years']) . ' ' : '' }}{{ $this->payoffEstimate['months'] > 0 ? $this->payoffEstimate['months'] . ' ' . trans_choice('app.months', $this->payoffEstimate['months']) . ' ' : '' }}{{ __('app.with_minimum_payments') }})</span>
                                @endif
                            </p>
                        @else
                            <p class="text-2xl font-display font-bold text-slate-400 dark:text-slate-500">
                                {{ __('app.unable_to_calculate') }}
                            </p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-3">
                                {{ __('app.add_minimum_payments') }}
                            </p>
                        @endif
                    </div>
                    {{-- Decorative Icon --}}
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500/10 to-cyan-500/10 dark:from-emerald-500/20 dark:to-cyan-500/20 flex items-center justify-center">
                        <svg class="h-8 w-8 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Debt Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 stagger-children"
             @if($reorderMode) x-sort.ghost="updatePosition($item, $position)" x-sort:config="{{ json_encode(['animation' => 150]) }}" @endif>
            @foreach ($this->debts as $index => $debt)
                <div wire:key="debt-{{ $debt['id'] }}"
                     @if($reorderMode) x-sort:item="{{ $debt['id'] }}" @endif
                     class="debt-card premium-card overflow-hidden {{ $reorderMode ? 'cursor-grab active:cursor-grabbing' : '' }}">
                    {{-- Card Header with Priority Badge --}}
                    <div class="relative p-6 pb-4">
                        {{-- Priority Badge --}}
                        <div class="absolute top-4 right-4 priority-badge">
                            <div class="w-10 h-10 rounded-xl {{ $reorderMode ? 'bg-gradient-to-br from-emerald-500 to-cyan-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600' }} flex items-center justify-center text-sm font-bold transition-all">
                                {{ $debt['customPriority'] ?? $index + 1 }}
                            </div>
                        </div>

                        {{-- Debt Name & Warning --}}
                        <div class="flex items-start gap-2 pr-14">
                            <button
                               wire:click="$parent.showDetail({{ $debt['id'] }})"
                               class="text-lg font-display font-semibold text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors cursor-pointer text-left">
                                {{ $debt['name'] }}
                            </button>
                            @if (!$debt['isCompliant'] && !$reorderMode)
                                <div class="relative group shrink-0">
                                    <div class="w-6 h-6 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    {{-- Tooltip --}}
                                    <div class="absolute right-0 top-full mt-2 w-64 p-3 bg-slate-900 dark:bg-slate-700 text-white text-sm rounded-xl shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-20">
                                        <div class="absolute -top-1 right-4 w-2 h-2 bg-slate-900 dark:bg-slate-700 transform rotate-45"></div>
                                        <p class="font-medium mb-1">{{ __('app.non_compliant_minimum') }}</p>
                                        <p class="text-xs text-slate-300 dark:text-slate-400">{{ $debt['warning'] }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Progress Ring --}}
                        @if ($debt['originalBalance'])
                            @php
                                $displayProgress = max(0, $debt['progressPercentage']);
                                $displayAmountPaid = max(0, $debt['amountPaid']);
                            @endphp
                            <div class="mt-4 flex items-center gap-4">
                                {{-- SVG Progress Ring --}}
                                <div class="relative w-20 h-20 shrink-0">
                                    <svg class="progress-ring w-20 h-20" viewBox="0 0 80 80">
                                        {{-- Background circle --}}
                                        <circle
                                            class="progress-ring-bg"
                                            cx="40"
                                            cy="40"
                                            r="34"
                                            fill="none"
                                            stroke-width="6"
                                        />
                                        {{-- Progress circle --}}
                                        <circle
                                            class="progress-ring-circle"
                                            cx="40"
                                            cy="40"
                                            r="34"
                                            fill="none"
                                            stroke="url(#momentum-gradient)"
                                            stroke-width="6"
                                            stroke-linecap="round"
                                            stroke-dasharray="{{ 2 * 3.14159 * 34 }}"
                                            stroke-dashoffset="{{ 2 * 3.14159 * 34 * (1 - $displayProgress / 100) }}"
                                        />
                                    </svg>
                                    {{-- Percentage in center --}}
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">{{ number_format($displayProgress, 0) }}%</span>
                                    </div>
                                </div>
                                {{-- Progress Text --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">{{ __('app.paid_off_progress') }}</p>
                                    <p class="text-sm text-slate-700 dark:text-slate-300">
                                        <span class="font-semibold">{{ number_format($displayAmountPaid, 0, ',', ' ') }}</span>
                                        <span class="text-slate-400 dark:text-slate-500">{{ __('app.of') }} {{ number_format($debt['originalBalance'], 0, ',', ' ') }} kr</span>
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Card Body --}}
                    <div class="px-6 pb-4 space-y-3">
                        {{-- Balance Row --}}
                        <div class="flex justify-between items-baseline">
                            <span class="text-sm text-slate-500 dark:text-slate-400">
                                {{ __('app.balance') }}
                                @if (!$reorderMode)
                                    <button
                                        wire:click="openReconciliationModal({{ $debt['id'] }})"
                                        type="button"
                                        class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 hover:underline ml-1 cursor-pointer text-xs font-medium"
                                    >({{ __('app.reconcile') }})</button>
                                    @php
                                        $reconciliationCount = $this->getReconciliationCountForDebt($debt['id']);
                                    @endphp
                                    @if ($reconciliationCount > 0)
                                        <button
                                            wire:click="openReconciliationHistory({{ $debt['id'] }})"
                                            type="button"
                                            class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 hover:underline ml-1 cursor-pointer text-xs"
                                        >({{ $reconciliationCount }})</button>
                                    @endif
                                @endif
                            </span>
                            <span class="text-xl font-display font-bold text-slate-900 dark:text-white">
                                {{ number_format($debt['balance'], 0, ',', ' ') }} <span class="text-sm font-normal text-slate-400">kr</span>
                            </span>
                        </div>

                        {{-- Interest Rate --}}
                        <div class="flex justify-between items-baseline">
                            <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.interest_rate') }}</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">
                                {{ number_format($debt['interestRate'], 1, ',', ' ') }}%
                            </span>
                        </div>

                        {{-- Minimum Payment --}}
                        @if ($debt['minimumPayment'])
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.minimum_payment') }}</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300">
                                    {{ number_format($debt['minimumPayment'], 0, ',', ' ') }} kr{{ __('app.per_month') }}
                                </span>
                            </div>
                        @endif

                        {{-- Due Day --}}
                        @if ($debt['dueDay'])
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.payment_due') }}</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">
                                    {{ __('app.due_on_day', ['day' => $debt['dueDay']]) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer --}}
                    <div class="border-t border-slate-100 dark:border-slate-800/50">
                        {{-- Metadata row with subtle background --}}
                        <div class="px-6 py-3 bg-slate-50/50 dark:bg-slate-800/30">
                            <div class="flex items-center justify-between text-xs text-slate-400 dark:text-slate-500">
                                <span>{{ __('app.added_on') }} {{ $debt['createdAt'] }}</span>
                                <span class="{{ $debt['lastVerifiedAt'] ? '' : 'text-amber-500 dark:text-amber-400' }}">
                                    @if ($debt['lastVerifiedAt'])
                                        {{ __('app.verified') }} {{ $debt['lastVerifiedAt'] }}
                                    @else
                                        {{ __('app.never_verified') }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        @if (!$reorderMode)
                            <div class="px-6 py-4 flex gap-2">
                                <button
                                    type="button"
                                    wire:click="$parent.editDebt({{ $debt['id'] }})"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 dark:bg-emerald-600 dark:hover:bg-emerald-500 rounded-xl transition-colors text-center cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                                    {{ __('app.edit') }}
                                </button>
                                <button type="button"
                                        wire:click="confirmDelete({{ $debt['id'] }}, '{{ $debt['name'] }}')"
                                        aria-label="{{ __('app.delete_debt', ['name' => $debt['name']]) }}"
                                        class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:text-rose-600 hover:border-rose-300 dark:hover:text-rose-400 dark:hover:border-rose-800 rounded-xl transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2">
                                    {{ __('app.delete') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Add New Debt Card --}}
            @if (!$reorderMode)
                <button
                   wire:click="$parent.showCreate"
                   class="debt-card border-2 border-dashed border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 hover:border-emerald-400 dark:hover:border-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10 rounded-2xl p-8 flex flex-col items-center justify-center min-h-[280px] group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 cursor-pointer transition-all">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/30 flex items-center justify-center mb-4 transition-colors">
                        <svg class="h-8 w-8 text-slate-400 dark:text-slate-500 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-display font-semibold text-slate-600 dark:text-slate-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors mb-1">
                        {{ __('app.add_new_debt') }}
                    </h3>
                    <p class="text-sm text-slate-400 dark:text-slate-500 text-center">
                        {{ __('app.click_to_add_debt') }}
                    </p>
                </button>
            @endif
        </div>
    @else
        {{-- Empty State --}}
        <div class="premium-card rounded-2xl p-16 text-center">
            <div class="max-w-md mx-auto">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-500/10 to-cyan-500/10 dark:from-emerald-500/20 dark:to-cyan-500/20 flex items-center justify-center mx-auto mb-6">
                    <svg class="h-10 w-10 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-display font-bold text-slate-900 dark:text-white mb-3">
                    {{ __('app.no_debts') }}
                </h2>
                <p class="text-slate-500 dark:text-slate-400 mb-8">
                    {{ __('app.no_debts_message') }}
                </p>
                <button
                   wire:click="$parent.showCreate"
                   class="inline-flex items-center gap-2 px-6 py-3 btn-momentum rounded-xl cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                    {{ __('app.add_first_debt') }}
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- YNAB Sync Modal --}}
    @if ($showYnabSync)
        <x-modal wire:model="showYnabSync" max-width="2xl">
            <x-modal.header :title="__('app.ynab_sync')" on-close="closeSyncModal" />

            <x-modal.body>
                        @if (count($ynabDiscrepancies['new']) === 0 && count($ynabDiscrepancies['closed']) === 0 && count($ynabDiscrepancies['potential_matches']) === 0 && empty($ynabDiscrepancies['balance_mismatch'] ?? []))
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-4 text-lg font-medium text-slate-900 dark:text-white">{{ __('app.ynab_all_synced') }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ __('app.ynab_no_differences') }}</p>
                            </div>
                        @else
                            {{-- Potential Matches Section --}}
                            @if (count($ynabDiscrepancies['potential_matches']) > 0)
                                <div class="mb-6">
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                        <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                        </svg>
                                        {{ __('app.ynab_potential_matches') }} ({{ count($ynabDiscrepancies['potential_matches']) }})
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach ($ynabDiscrepancies['potential_matches'] as $match)
                                            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                                                <div class="space-y-3">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('app.ynab_source') }}</p>
                                                            <p class="font-semibold text-slate-900 dark:text-white">{{ $match['ynab']['name'] }}</p>
                                                            <div class="mt-1 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                                                                <p>{{ number_format($match['ynab']['balance'], 0, ',', ' ') }} kr • {{ number_format($match['ynab']['interest_rate'], 1, ',', ' ') }}%</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="border-t border-amber-200 dark:border-amber-800 pt-3">
                                                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('app.your_app') }}</p>
                                                        <p class="font-semibold text-slate-900 dark:text-white">{{ $match['local']['name'] }}</p>
                                                        <div class="mt-1 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                                                            <p>{{ number_format($match['local']['balance'], 0, ',', ' ') }} kr • {{ number_format($match['local']['interest_rate'], 1, ',', ' ') }}%</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex gap-2 pt-2">
                                                        <button
                                                            wire:click="ignorePotentialMatch('{{ $match['ynab']['name'] }}')"
                                                            class="flex-1 px-3 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-900 dark:text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                            {{ __('app.not_same_debt') }}
                                                        </button>
                                                        <button
                                                            wire:click="openLinkConfirmation({{ $match['local']['id'] }}, {{ json_encode($match['ynab']) }})"
                                                            class="flex-1 px-3 py-2 bg-amber-600 hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                            {{ __('app.same_debt') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- New Debts Section --}}
                            @if (count($ynabDiscrepancies['new']) > 0)
                                <div class="mb-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                            <svg class="h-5 w-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            {{ __('app.new_debts_in_ynab') }} ({{ count($ynabDiscrepancies['new']) }})
                                        </h4>
                                        <button
                                            wire:click="importAllYnabDebts"
                                            class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer flex items-center gap-2">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            {{ __('app.import_all') }}
                                        </button>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach ($ynabDiscrepancies['new'] as $debt)
                                            <div class="bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-xl p-4">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <p class="font-semibold text-slate-900 dark:text-white">{{ $debt['name'] }}</p>
                                                        <div class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                                                            <p>{{ __('app.balance_label') }} <span class="font-medium">{{ number_format($debt['balance'], 0, ',', ' ') }} kr</span></p>
                                                            <p>{{ __('app.interest_label') }} <span class="font-medium">{{ number_format($debt['interest_rate'], 1, ',', ' ') }}%</span></p>
                                                            @if ($debt['minimum_payment'])
                                                                <p>{{ __('app.min_payment_label') }} <span class="font-medium">{{ number_format($debt['minimum_payment'], 0, ',', ' ') }} kr</span></p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <button
                                                        wire:click="importYnabDebt({{ json_encode($debt) }})"
                                                        class="ml-4 px-3 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                        {{ __('app.import') }}
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Balance Mismatch Section --}}
                            @if (!empty($ynabDiscrepancies['balance_mismatch'] ?? []))
                                <div class="mb-6">
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                        <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                        {{ __('app.balance_mismatch') }} ({{ count($ynabDiscrepancies['balance_mismatch']) }})
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach ($ynabDiscrepancies['balance_mismatch'] as $mismatch)
                                            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <p class="font-semibold text-slate-900 dark:text-white">{{ $mismatch['local_debt']->name }}</p>
                                                        <div class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                                                            <p>{{ __('app.in_app') }} <span class="font-medium">{{ number_format($mismatch['local_balance'], 0, ',', ' ') }} kr</span></p>
                                                            <p>{{ __('app.in_ynab') }} <span class="font-medium">{{ number_format($mismatch['ynab_balance'], 0, ',', ' ') }} kr</span></p>
                                                            <p class="{{ $mismatch['difference'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                                                {{ __('app.difference_label') }} <span class="font-medium">{{ $mismatch['difference'] >= 0 ? '+' : '' }}{{ number_format($mismatch['difference'], 0, ',', ' ') }} kr</span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <button
                                                        wire:click="openReconciliationFromYnab({{ $mismatch['local_debt']->id }}, {{ $mismatch['ynab_balance'] }})"
                                                        class="ml-4 px-3 py-2 bg-amber-600 hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                        {{ __('app.reconcile') }}
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Closed Debts Section --}}
                            @if (count($ynabDiscrepancies['closed']) > 0)
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                                        <svg class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        {{ __('app.closed_in_ynab') }} ({{ count($ynabDiscrepancies['closed']) }})
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach ($ynabDiscrepancies['closed'] as $debt)
                                            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="font-semibold text-slate-900 dark:text-white">{{ $debt['name'] }}</p>
                                                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                                            {{ __('app.closed_in_ynab_exists_here') }}
                                                        </p>
                                                    </div>
                                                    <button
                                                        wire:click="deleteClosedDebt({{ $debt['id'] }}, '{{ $debt['name'] }}')"
                                                        class="ml-4 px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                        {{ __('app.delete') }}
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
            </x-modal.body>
        </x-modal>
    @endif

    {{-- Link Confirmation Modal --}}
    @if ($showLinkConfirmation)
        <x-modal wire:model="showLinkConfirmation" max-width="md">
            <x-modal.header :title="__('app.link_to_ynab')" on-close="closeLinkConfirmation" />

            <x-modal.body>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    {{ __('app.select_fields_to_update') }}
                </p>

                <div class="space-y-3 mb-6">
                    {{-- Name checkbox --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="selectedFieldsToUpdate"
                            value="name"
                            class="mt-1 h-4 w-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-700 cursor-pointer">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ __('app.name') }}</p>
                            @if (!empty($linkingYnabDebt))
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $linkingYnabDebt['name'] }}</p>
                            @endif
                        </div>
                    </label>

                    {{-- Balance checkbox --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="selectedFieldsToUpdate"
                            value="balance"
                            class="mt-1 h-4 w-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-700 cursor-pointer">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ __('app.balance') }}</p>
                            @if (!empty($linkingYnabDebt))
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($linkingYnabDebt['balance'], 0, ',', ' ') }} kr</p>
                            @endif
                        </div>
                    </label>

                    {{-- Interest rate checkbox --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="selectedFieldsToUpdate"
                            value="interest_rate"
                            class="mt-1 h-4 w-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-700 cursor-pointer">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ __('app.interest_rate') }}</p>
                            @if (!empty($linkingYnabDebt))
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($linkingYnabDebt['interest_rate'], 1, ',', ' ') }}%</p>
                            @endif
                        </div>
                    </label>

                    {{-- Minimum payment checkbox --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="selectedFieldsToUpdate"
                            value="minimum_payment"
                            class="mt-1 h-4 w-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-700 cursor-pointer">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ __('app.minimum_payment') }}</p>
                            @if (!empty($linkingYnabDebt) && $linkingYnabDebt['minimum_payment'])
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($linkingYnabDebt['minimum_payment'], 0, ',', ' ') }} kr{{ __('app.per_month') }}</p>
                            @endif
                        </div>
                    </label>
                </div>

                <div class="bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-xl p-3">
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        <strong>{{ __('app.important') }}</strong> {{ __('app.ynab_account_id_saved') }}
                    </p>
                </div>
            </x-modal.body>

            <x-modal.footer>
                <x-modal.button-secondary wire:click="closeLinkConfirmation">
                    {{ __('app.cancel') }}
                </x-modal.button-secondary>
                <x-modal.button-primary wire:click="confirmLinkToExistingDebt" variant="warning">
                    {{ __('app.link_to_ynab') }}
                </x-modal.button-primary>
            </x-modal.footer>
        </x-modal>
    @endif

    {{-- Delete Confirmation Modal --}}
    <x-delete-confirmation-modal
        wire:model="showDeleteModal"
        :title="__('app.confirm_delete_debt', ['name' => $recordNameToDelete])"
        :message="__('app.delete_debt_warning')"
        on-confirm="executeDelete"
    />

    {{-- Reconciliation Modals --}}
    @foreach (\App\Models\Debt::all() as $debtModel)
        @if (isset($this->reconciliations[$debtModel->id]['show']) && $this->reconciliations[$debtModel->id]['show'])
        <x-modal wire:model="reconciliations.{{ $debtModel->id }}.show" max-width="lg">
            <form wire:submit.prevent="reconcileDebt({{ $debtModel->id }})">
                <x-modal.header :title="__('app.reconcile_debt')">
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $debtModel->name }}</p>
                </x-modal.header>

                <x-modal.body>
                    <div class="space-y-5">
                        {{-- Current Calculated Balance --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('app.calculated_balance') }}
                            </label>
                            <div class="text-lg font-semibold text-slate-900 dark:text-white">
                                {{ number_format($debtModel->balance, 2, ',', ' ') }} kr
                            </div>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                {{ __('app.based_on_original_balance') }}
                            </p>
                        </div>

                        {{-- Actual Balance Input --}}
                        <div>
                            <label for="actualBalance-{{ $debtModel->id }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('app.actual_balance') }} *
                            </label>
                            <div class="relative">
                                <input
                                    type="number"
                                    id="actualBalance-{{ $debtModel->id }}"
                                    wire:model.live="reconciliations.{{ $debtModel->id }}.balance"
                                    step="0.01"
                                    min="0"
                                    placeholder="{{ __('app.actual_balance_placeholder') }}"
                                    class="w-full px-4 py-2.5 pr-14 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none @error('reconciliations.' . $debtModel->id . '.balance') border-rose-500 dark:border-rose-400 @enderror"
                                >
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                    <span class="text-slate-500 dark:text-slate-400 text-sm font-medium">NOK</span>
                                </div>
                            </div>
                            @error('reconciliations.' . $debtModel->id . '.balance')
                                <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Difference Display --}}
                        @if (isset($this->reconciliations[$debtModel->id]['balance']) && $this->reconciliations[$debtModel->id]['balance'] !== '')
                            @php
                                $difference = $this->getReconciliationDifference($debtModel->id);
                            @endphp
                            <div class="p-4 rounded-xl {{ $difference < 0 ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' : ($difference > 0 ? 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' : 'bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800') }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium {{ $difference < 0 ? 'text-emerald-900 dark:text-emerald-200' : ($difference > 0 ? 'text-amber-900 dark:text-amber-200' : 'text-cyan-900 dark:text-cyan-200') }}">
                                        {{ __('app.difference') }}:
                                    </span>
                                    <span class="text-lg font-bold {{ $difference < 0 ? 'text-emerald-900 dark:text-emerald-200' : ($difference > 0 ? 'text-amber-900 dark:text-amber-200' : 'text-cyan-900 dark:text-cyan-200') }}">
                                        {{ $difference >= 0 ? '+' : '' }}{{ number_format($difference, 2, ',', ' ') }} kr
                                    </span>
                                </div>
                                <p class="mt-1 text-xs {{ $difference < 0 ? 'text-emerald-700 dark:text-emerald-300' : ($difference > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-cyan-700 dark:text-cyan-300') }}">
                                    @if (abs($difference) < 0.01)
                                        {{ __('app.balance_correct') }}
                                    @elseif ($difference < 0)
                                        {{ __('app.paid_more_than_registered', ['amount' => number_format(abs($difference), 2, ',', ' ')]) }}
                                    @else
                                        {{ __('app.balance_higher_than_calculated', ['amount' => number_format($difference, 2, ',', ' ')]) }}
                                    @endif
                                </p>
                            </div>
                        @endif

                        {{-- Reconciliation Date --}}
                        <div>
                            <label for="reconciliationDate-{{ $debtModel->id }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('app.reconciliation_date') }} *
                            </label>
                            <input
                                type="text"
                                id="reconciliationDate-{{ $debtModel->id }}"
                                wire:model="reconciliations.{{ $debtModel->id }}.date"
                                placeholder="DD.MM.YYYY"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent @error('reconciliations.' . $debtModel->id . '.date') border-rose-500 dark:border-rose-400 @enderror"
                            >
                            @error('reconciliations.' . $debtModel->id . '.date')
                                <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label for="notes-{{ $debtModel->id }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('app.notes') }} ({{ __('app.optional') }})
                            </label>
                            <textarea
                                id="notes-{{ $debtModel->id }}"
                                wire:model="reconciliations.{{ $debtModel->id }}.notes"
                                rows="3"
                                placeholder="{{ __('app.reconciliation_notes_placeholder') }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent @error('reconciliations.' . $debtModel->id . '.notes') border-rose-500 dark:border-rose-400 @enderror"
                            ></textarea>
                            @error('reconciliations.' . $debtModel->id . '.notes')
                                <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </x-modal.body>

                <x-modal.footer>
                    <x-modal.button-secondary x-on:click="show = false">
                        {{ __('app.cancel') }}
                    </x-modal.button-secondary>
                    <x-modal.button-primary
                        type="submit"
                        :loading="true"
                        loading-target="reconcileDebt"
                    >
                        {{ __('app.reconcile') }}
                    </x-modal.button-primary>
                </x-modal.footer>
            </form>
        </x-modal>
        @endif
    @endforeach

    {{-- Reconciliation History Modal --}}
    @if ($showReconciliationHistory && $viewingReconciliationHistoryForDebtId)
        <x-modal wire:model="showReconciliationHistory" max-width="lg">
            <x-modal.header :title="__('app.reconciliation_history')" on-close="closeReconciliationHistory">
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $this->historyDebtName }}</p>
            </x-modal.header>

            <x-modal.body class="max-h-[60vh] overflow-y-auto">
                <livewire:reconciliation-history :debt-id="$viewingReconciliationHistoryForDebtId" :key="'history-'.$viewingReconciliationHistoryForDebtId" />
            </x-modal.body>
        </x-modal>
    @endif
</div>
