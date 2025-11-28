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
                <button
                    type="button"
                    wire:click="checkYnab"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ __('app.check_ynab') }}
                </button>
                <button
                    type="button"
                    wire:click="enableReorderMode"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 focus:ring-offset-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    {{ __('app.reorder_debts') }}
                </button>
            @else
                <button
                    type="button"
                    wire:click="cancelReorder"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 focus:ring-offset-2">
                    {{ __('app.cancel') }}
                </button>
                <button
                    type="button"
                    x-on:click="saveOrder"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                    {{ __('app.save_order') }}
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (count($this->debts) > 0)
            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                {{-- Total Debt Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                {{ __('app.total_debt') }}
                            </p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($this->totalDebt, 0, ',', ' ') }} kr
                            </p>
                            @if ($this->lastUpdated)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    {{ __('app.last_updated') }}: {{ $this->lastUpdated }}
                                </p>
                            @endif
                        </div>
                        <div class="h-16 w-16 bg-red-100 dark:bg-red-900/20 rounded-lg flex items-center justify-center">
                            <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Estimated Payoff Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                {{ __('app.debt_free_in') }}
                            </p>
                            @if ($this->strategyEstimate)
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                    @if ($this->strategyEstimate['years'] > 0)
                                        {{ $this->strategyEstimate['years'] }} {{ trans_choice('app.years', $this->strategyEstimate['years']) }}
                                    @endif
                                    @if ($this->strategyEstimate['months'] > 0)
                                        {{ $this->strategyEstimate['months'] }} {{ trans_choice('app.months', $this->strategyEstimate['months']) }}
                                    @endif
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    <span>{{ __('app.with_chosen_strategy') }}</span>
                                    @if ($this->payoffEstimate)
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">({{ $this->payoffEstimate['years'] > 0 ? $this->payoffEstimate['years'] . ' ' . trans_choice('app.years', $this->payoffEstimate['years']) . ' ' : '' }}{{ $this->payoffEstimate['months'] > 0 ? $this->payoffEstimate['months'] . ' ' . trans_choice('app.months', $this->payoffEstimate['months']) . ' ' : '' }}{{ __('app.with_minimum_payments') }})</span>
                                    @endif
                                </p>
                            @else
                                <p class="text-2xl font-bold text-gray-500 dark:text-gray-400">
                                    {{ __('app.unable_to_calculate') }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    {{ __('app.add_minimum_payments') }}
                                </p>
                            @endif
                        </div>
                        <div class="h-16 w-16 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                            <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Debt Cards Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                 @if($reorderMode) x-sort.ghost="updatePosition($item, $position)" x-sort:config="{{ json_encode(['animation' => 150]) }}" @endif>
                @foreach ($this->debts as $index => $debt)
                    <div wire:key="debt-{{ $debt['id'] }}"
                         @if($reorderMode) x-sort:item="{{ $debt['id'] }}" @endif
                         class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 {{ $reorderMode ? 'cursor-grab active:cursor-grabbing' : 'hover:border-gray-300 dark:hover:border-gray-600' }} transition-colors relative">
                        {{-- Priority Number Badge --}}
                        <div class="absolute top-2 right-2 w-8 h-8 {{ $reorderMode ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-lg' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} rounded-full flex items-center justify-center text-sm font-bold transition-colors z-10">
                            {{ $debt['customPriority'] ?? $index + 1 }}
                        </div>

                        <div class="p-6">
                            {{-- Debt Name --}}
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-2 flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $debt['name'] }}
                                    </h3>
                                </div>
                                @if (!$debt['isCompliant'] && !$reorderMode)
                                    <div class="relative group">
                                        <div class="flex items-center gap-1 px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 text-xs font-medium rounded">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <!-- Tooltip -->
                                        <div class="absolute right-0 top-full mt-2 w-64 p-3 bg-gray-900 dark:bg-gray-700 text-white text-sm rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-10">
                                            <div class="absolute -top-1 right-4 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45"></div>
                                            <p class="font-medium mb-1">{{ __('app.non_compliant_minimum') }}</p>
                                            <p class="text-xs text-gray-300 dark:text-gray-400">{{ $debt['warning'] }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Progress Bar --}}
                            @if ($debt['progressPercentage'] > 0)
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                            {{ __('app.paid_off_progress') }}
                                        </span>
                                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400">
                                            {{ number_format($debt['progressPercentage'], 1, ',', ' ') }}%
                                        </span>
                                    </div>
                                    <div class="relative h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div
                                            class="absolute inset-y-0 left-0 bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-full transition-all duration-500"
                                            style="width: {{ $debt['progressPercentage'] }}%"
                                        ></div>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format($debt['amountPaid'], 0, ',', ' ') }} kr {{ __('app.of') }} {{ number_format($debt['originalBalance'], 0, ',', ' ') }} kr {{ __('app.paid') }}
                                    </div>
                                </div>
                            @endif

                            {{-- Debt Details --}}
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between items-baseline">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('app.balance') }}
                                        @if (!$reorderMode)
                                            <button
                                                wire:click="openReconciliationModal({{ $debt['id'] }})"
                                                type="button"
                                                class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:underline ml-1 cursor-pointer"
                                            >({{ __('app.reconcile') }})</button>
                                            @php
                                                $reconciliationCount = $this->getReconciliationCountForDebt($debt['id']);
                                            @endphp
                                            @if ($reconciliationCount > 0)
                                                <button
                                                    wire:click="openReconciliationHistory({{ $debt['id'] }})"
                                                    type="button"
                                                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:underline ml-1 cursor-pointer"
                                                >({{ __('app.view_history') }} {{ $reconciliationCount }})</button>
                                            @endif
                                        @endif
                                    </span>
                                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ number_format($debt['balance'], 0, ',', ' ') }} kr
                                    </span>
                                </div>
                                <div class="flex justify-between items-baseline">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.original_balance') }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ number_format($debt['originalBalance'], 0, ',', ' ') }} kr
                                    </span>
                                </div>
                                <div class="flex justify-between items-baseline">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.interest_rate') }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ number_format($debt['interestRate'], 1, ',', ' ') }} %
                                    </span>
                                </div>
                                @if ($debt['minimumPayment'])
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.minimum_payment') }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ number_format($debt['minimumPayment'], 0, ',', ' ') }} kr{{ __('app.per_month') }}
                                        </span>
                                    </div>
                                @endif
                                @if ($debt['dueDay'])
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.payment_due') }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ __('app.due_on_day', ['day' => $debt['dueDay']]) }}
                                        </span>
                                    </div>
                                @endif
                                <div class="flex justify-between items-baseline pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('app.added_on') }}</span>
                                    <span class="text-xs text-gray-600 dark:text-gray-300">
                                        {{ $debt['createdAt'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Actions --}}
                            @if (!$reorderMode)
                                <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <button
                                        type="button"
                                        wire:click="$parent.editDebt({{ $debt['id'] }})"
                                        class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors text-center cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                                        {{ __('app.edit') }}
                                    </button>
                                    <button type="button"
                                            wire:click="confirmDelete({{ $debt['id'] }}, '{{ $debt['name'] }}')"
                                            aria-label="{{ __('app.delete_debt', ['name' => $debt['name']]) }}"
                                            class="flex-1 px-3 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-500 dark:focus:ring-red-400 focus:ring-offset-2">
                                        {{ __('app.delete') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Add New Debt Placeholder --}}
                @if (!$reorderMode)
                    <a href="/debts/create"
                       class="bg-white dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all p-6 flex flex-col items-center justify-center min-h-[300px] group focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                        <div class="h-16 w-16 bg-gray-100 dark:bg-gray-700 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30 rounded-full flex items-center justify-center mb-4 transition-colors">
                            <svg class="h-8 w-8 text-gray-400 dark:text-gray-500 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                            {{ __('app.add_new_debt') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-2 text-center">
                            {{ __('app.click_to_add_debt') }}
                        </p>
                    </a>
                @endif
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="max-w-sm mx-auto">
                    <div class="h-16 w-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        {{ __('app.no_debts') }}
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        {{ __('app.no_debts_message') }}
                    </p>
                    <a href="/debts/create"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                        {{ __('app.add_first_debt') }}
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </a>
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
                                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">{{ __('app.ynab_all_synced') }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('app.ynab_no_differences') }}</p>
                            </div>
                        @else
                            {{-- Potential Matches Section --}}
                            @if (count($ynabDiscrepancies['potential_matches']) > 0)
                                <div class="mb-6">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <svg class="h-5 w-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ __('app.ynab_potential_matches') }} ({{ count($ynabDiscrepancies['potential_matches']) }})
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach ($ynabDiscrepancies['potential_matches'] as $match)
                                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                                <div class="space-y-3">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('app.ynab_source') }}</p>
                                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $match['ynab']['name'] }}</p>
                                                            <div class="mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                                <p>{{ number_format($match['ynab']['balance'], 0, ',', ' ') }} kr • {{ number_format($match['ynab']['interest_rate'], 1, ',', ' ') }}%</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="border-t border-yellow-200 dark:border-yellow-800 pt-3">
                                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('app.your_app') }}</p>
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $match['local']['name'] }}</p>
                                                        <div class="mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                            <p>{{ number_format($match['local']['balance'], 0, ',', ' ') }} kr • {{ number_format($match['local']['interest_rate'], 1, ',', ' ') }}%</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex gap-2 pt-2">
                                                        <button
                                                            wire:click="ignorePotentialMatch('{{ $match['ynab']['name'] }}')"
                                                            class="flex-1 px-3 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                            {{ __('app.not_same_debt') }}
                                                        </button>
                                                        <button
                                                            wire:click="openLinkConfirmation({{ $match['local']['id'] }}, {{ json_encode($match['ynab']) }})"
                                                            class="flex-1 px-3 py-2 bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-500 dark:hover:bg-yellow-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
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
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                            <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            {{ __('app.new_debts_in_ynab') }} ({{ count($ynabDiscrepancies['new']) }})
                                        </h4>
                                        <button
                                            wire:click="importAllYnabDebts"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer flex items-center gap-2">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            {{ __('app.import_all') }}
                                        </button>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach ($ynabDiscrepancies['new'] as $debt)
                                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $debt['name'] }}</p>
                                                        <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                            <p>{{ __('app.balance_label') }} <span class="font-medium">{{ number_format($debt['balance'], 0, ',', ' ') }} kr</span></p>
                                                            <p>{{ __('app.interest_label') }} <span class="font-medium">{{ number_format($debt['interest_rate'], 1, ',', ' ') }}%</span></p>
                                                            @if ($debt['minimum_payment'])
                                                                <p>{{ __('app.min_payment_label') }} <span class="font-medium">{{ number_format($debt['minimum_payment'], 0, ',', ' ') }} kr</span></p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <button
                                                        wire:click="importYnabDebt({{ json_encode($debt) }})"
                                                        class="ml-4 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
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
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        {{ __('app.balance_mismatch') }} ({{ count($ynabDiscrepancies['balance_mismatch']) }})
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach ($ynabDiscrepancies['balance_mismatch'] as $mismatch)
                                            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $mismatch['local_debt']->name }}</p>
                                                        <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                            <p>{{ __('app.in_app') }} <span class="font-medium">{{ number_format($mismatch['local_balance'], 0, ',', ' ') }} kr</span></p>
                                                            <p>{{ __('app.in_ynab') }} <span class="font-medium">{{ number_format($mismatch['ynab_balance'], 0, ',', ' ') }} kr</span></p>
                                                            <p class="{{ $mismatch['difference'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
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
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ __('app.closed_in_ynab') }} ({{ count($ynabDiscrepancies['closed']) }})
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach ($ynabDiscrepancies['closed'] as $debt)
                                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $debt['name'] }}</p>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                            {{ __('app.closed_in_ynab_exists_here') }}
                                                        </p>
                                                    </div>
                                                    <button
                                                        wire:click="deleteClosedDebt({{ $debt['id'] }}, '{{ $debt['name'] }}')"
                                                        class="ml-4 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
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
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('app.select_fields_to_update') }}
                </p>

                <div class="space-y-3 mb-6">
                    {{-- Name checkbox --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="selectedFieldsToUpdate"
                            value="name"
                            class="mt-1 h-4 w-4 text-yellow-600 rounded border-gray-300 focus:ring-yellow-500 dark:border-gray-600 dark:bg-gray-700 cursor-pointer">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('app.name') }}</p>
                            @if (!empty($linkingYnabDebt))
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $linkingYnabDebt['name'] }}</p>
                            @endif
                        </div>
                    </label>

                    {{-- Balance checkbox --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="selectedFieldsToUpdate"
                            value="balance"
                            class="mt-1 h-4 w-4 text-yellow-600 rounded border-gray-300 focus:ring-yellow-500 dark:border-gray-600 dark:bg-gray-700 cursor-pointer">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('app.balance') }}</p>
                            @if (!empty($linkingYnabDebt))
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($linkingYnabDebt['balance'], 0, ',', ' ') }} kr</p>
                            @endif
                        </div>
                    </label>

                    {{-- Interest rate checkbox --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="selectedFieldsToUpdate"
                            value="interest_rate"
                            class="mt-1 h-4 w-4 text-yellow-600 rounded border-gray-300 focus:ring-yellow-500 dark:border-gray-600 dark:bg-gray-700 cursor-pointer">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('app.interest_rate') }}</p>
                            @if (!empty($linkingYnabDebt))
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($linkingYnabDebt['interest_rate'], 1, ',', ' ') }}%</p>
                            @endif
                        </div>
                    </label>

                    {{-- Minimum payment checkbox --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="selectedFieldsToUpdate"
                            value="minimum_payment"
                            class="mt-1 h-4 w-4 text-yellow-600 rounded border-gray-300 focus:ring-yellow-500 dark:border-gray-600 dark:bg-gray-700 cursor-pointer">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('app.minimum_payment') }}</p>
                            @if (!empty($linkingYnabDebt) && $linkingYnabDebt['minimum_payment'])
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($linkingYnabDebt['minimum_payment'], 0, ',', ' ') }} kr{{ __('app.per_month') }}</p>
                            @endif
                        </div>
                    </label>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-xs text-gray-600 dark:text-gray-400">
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
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $debtModel->name }}</p>
                </x-modal.header>

                <x-modal.body>
                    <div class="space-y-5">
                        {{-- Current Calculated Balance --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('app.calculated_balance') }}
                            </label>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($debtModel->balance, 2, ',', ' ') }} kr
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('app.based_on_original_balance') }}
                            </p>
                        </div>

                        {{-- Actual Balance Input --}}
                        <div>
                            <label for="actualBalance-{{ $debtModel->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
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
                                    class="w-full px-4 py-2.5 pr-14 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none @error('reconciliations.' . $debtModel->id . '.balance') border-red-500 dark:border-red-400 @enderror"
                                >
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">NOK</span>
                                </div>
                            </div>
                            @error('reconciliations.' . $debtModel->id . '.balance')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Difference Display --}}
                        @if (isset($this->reconciliations[$debtModel->id]['balance']) && $this->reconciliations[$debtModel->id]['balance'] !== '')
                            @php
                                $difference = $this->getReconciliationDifference($debtModel->id);
                            @endphp
                            <div class="p-4 rounded-lg {{ $difference < 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : ($difference > 0 ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' : 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800') }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium {{ $difference < 0 ? 'text-green-900 dark:text-green-200' : ($difference > 0 ? 'text-yellow-900 dark:text-yellow-200' : 'text-blue-900 dark:text-blue-200') }}">
                                        {{ __('app.difference') }}:
                                    </span>
                                    <span class="text-lg font-bold {{ $difference < 0 ? 'text-green-900 dark:text-green-200' : ($difference > 0 ? 'text-yellow-900 dark:text-yellow-200' : 'text-blue-900 dark:text-blue-200') }}">
                                        {{ $difference >= 0 ? '+' : '' }}{{ number_format($difference, 2, ',', ' ') }} kr
                                    </span>
                                </div>
                                <p class="mt-1 text-xs {{ $difference < 0 ? 'text-green-700 dark:text-green-300' : ($difference > 0 ? 'text-yellow-700 dark:text-yellow-300' : 'text-blue-700 dark:text-blue-300') }}">
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
                            <label for="reconciliationDate-{{ $debtModel->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('app.reconciliation_date') }} *
                            </label>
                            <input
                                type="text"
                                id="reconciliationDate-{{ $debtModel->id }}"
                                wire:model="reconciliations.{{ $debtModel->id }}.date"
                                placeholder="DD.MM.YYYY"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent @error('reconciliations.' . $debtModel->id . '.date') border-red-500 dark:border-red-400 @enderror"
                            >
                            @error('reconciliations.' . $debtModel->id . '.date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label for="notes-{{ $debtModel->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('app.notes') }} ({{ __('app.optional') }})
                            </label>
                            <textarea
                                id="notes-{{ $debtModel->id }}"
                                wire:model="reconciliations.{{ $debtModel->id }}.notes"
                                rows="3"
                                placeholder="{{ __('app.reconciliation_notes_placeholder') }}"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent @error('reconciliations.' . $debtModel->id . '.notes') border-red-500 dark:border-red-400 @enderror"
                            ></textarea>
                            @error('reconciliations.' . $debtModel->id . '.notes')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
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
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $this->historyDebtName }}</p>
            </x-modal.header>

            <x-modal.body class="max-h-[60vh] overflow-y-auto">
                <livewire:reconciliation-history :debt-id="$viewingReconciliationHistoryForDebtId" :key="'history-'.$viewingReconciliationHistoryForDebtId" />
            </x-modal.body>
        </x-modal>
    @endif
</div>

