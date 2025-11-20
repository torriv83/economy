<div x-data="{
    sortable: null,
    init() {
        if (@js($reorderMode)) {
            this.initSortable();
        }
        this.$watch('$wire.reorderMode', value => {
            if (value) {
                this.initSortable();
            } else if (this.sortable) {
                this.sortable.destroy();
                this.sortable = null;
            }
        });
    },
    initSortable() {
        this.$nextTick(() => {
            const el = document.getElementById('debt-grid');
            if (el && !this.sortable) {
                this.sortable = Sortable.create(el, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'opacity-50'
                });
            }
        });
    },
    saveOrder() {
        if (this.sortable) {
            const order = this.sortable.toArray();
            $wire.updateOrder(order);
        }
    }
}">
    {{-- Header --}}
    <div class="flex items-start justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ __('app.debts_overview') }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('app.debts_overview_description') }}
                </p>
            </div>
            <div class="flex gap-2">
                @if (!$reorderMode)
                    <button
                        type="button"
                        wire:click="checkYnab"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-700 dark:text-purple-300 font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:ring-offset-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Sjekk YNAB
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
                    <a href="/debts/create"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('app.add_new_debt') }}
                    </a>
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
            </div>
        </div>

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
                            @if ($this->payoffEstimate)
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                    @if ($this->payoffEstimate['years'] > 0)
                                        {{ $this->payoffEstimate['years'] }} {{ trans_choice('app.years', $this->payoffEstimate['years']) }}
                                    @endif
                                    @if ($this->payoffEstimate['months'] > 0)
                                        {{ $this->payoffEstimate['months'] }} {{ trans_choice('app.months', $this->payoffEstimate['months']) }}
                                    @endif
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    {{ __('app.with_minimum_payments') }}
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="debt-grid">
                @foreach ($this->debts as $index => $debt)
                    <div wire:key="debt-{{ $debt['id'] }}"
                         data-id="{{ $debt['id'] }}"
                         class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 {{ $reorderMode ? 'cursor-move' : 'hover:border-gray-300 dark:hover:border-gray-600' }} transition-colors relative">
                        {{-- Priority Number Badge --}}
                        <div class="absolute top-2 right-2 w-8 h-8 {{ $reorderMode ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-lg' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} rounded-full flex items-center justify-center text-sm font-bold transition-colors z-10">
                            {{ $debt['customPriority'] ?? $index + 1 }}
                        </div>

                        <div class="p-6">
                            {{-- Debt Name --}}
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-2 flex-1">
                                    @if ($reorderMode)
                                        <div class="drag-handle cursor-grab active:cursor-grabbing">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                            </svg>
                                        </div>
                                    @endif
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
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.balance') }}</span>
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
                                    <a href="/debts/{{ $debt['id'] }}/edit"
                                       class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors text-center focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                                        {{ __('app.edit') }}
                                    </a>
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
        <div class="fixed inset-0 bg-black/50 transition-opacity z-50" wire:click="closeSyncModal"></div>
        <div class="fixed inset-0 z-50 overflow-y-auto pointer-events-none">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto pointer-events-auto" @click.stop>
                    {{-- Header --}}
                    <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            YNAB Synkronisering
                        </h3>
                        <button wire:click="closeSyncModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 cursor-pointer">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-6">
                        @if (count($ynabDiscrepancies['new']) === 0 && count($ynabDiscrepancies['closed']) === 0 && count($ynabDiscrepancies['potential_matches']) === 0)
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Alt er synkronisert!</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Ingen forskjeller funnet mellom YNAB og din app.</p>
                            </div>
                        @else
                            {{-- Potential Matches Section --}}
                            @if (count($ynabDiscrepancies['potential_matches']) > 0)
                                <div class="mb-6">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <svg class="h-5 w-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Mulige matches ({{ count($ynabDiscrepancies['potential_matches']) }})
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach ($ynabDiscrepancies['potential_matches'] as $match)
                                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                                <div class="space-y-3">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">YNAB:</p>
                                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $match['ynab']['name'] }}</p>
                                                            <div class="mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                                <p>{{ number_format($match['ynab']['balance'], 0, ',', ' ') }} kr • {{ number_format($match['ynab']['interest_rate'], 1, ',', ' ') }}%</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="border-t border-yellow-200 dark:border-yellow-800 pt-3">
                                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Din app:</p>
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $match['local']['name'] }}</p>
                                                        <div class="mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                            <p>{{ number_format($match['local']['balance'], 0, ',', ' ') }} kr • {{ number_format($match['local']['interest_rate'], 1, ',', ' ') }}%</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex gap-2 pt-2">
                                                        <button
                                                            wire:click="ignorePotentialMatch('{{ $match['ynab']['name'] }}')"
                                                            class="flex-1 px-3 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                            Dette er ikke samme gjeld
                                                        </button>
                                                        <button
                                                            wire:click="openLinkConfirmation({{ $match['local']['id'] }}, {{ json_encode($match['ynab']) }})"
                                                            class="flex-1 px-3 py-2 bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-500 dark:hover:bg-yellow-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                            Dette er samme gjeld
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
                                            Ny gjeld i YNAB ({{ count($ynabDiscrepancies['new']) }})
                                        </h4>
                                        <button
                                            wire:click="importAllYnabDebts"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer flex items-center gap-2">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Importer alle
                                        </button>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach ($ynabDiscrepancies['new'] as $debt)
                                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $debt['name'] }}</p>
                                                        <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                            <p>Saldo: <span class="font-medium">{{ number_format($debt['balance'], 0, ',', ' ') }} kr</span></p>
                                                            <p>Rente: <span class="font-medium">{{ number_format($debt['interest_rate'], 1, ',', ' ') }}%</span></p>
                                                            @if ($debt['minimum_payment'])
                                                                <p>Min. betaling: <span class="font-medium">{{ number_format($debt['minimum_payment'], 0, ',', ' ') }} kr</span></p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <button
                                                        wire:click="importYnabDebt({{ json_encode($debt) }})"
                                                        class="ml-4 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                        Importer
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
                                        Lukket i YNAB ({{ count($ynabDiscrepancies['closed']) }})
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach ($ynabDiscrepancies['closed'] as $debt)
                                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $debt['name'] }}</p>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                            Lukket i YNAB, men eksisterer fortsatt her
                                                        </p>
                                                    </div>
                                                    <button
                                                        wire:click="deleteClosedDebt({{ $debt['id'] }}, '{{ $debt['name'] }}')"
                                                        class="ml-4 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                        Slett
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Link Confirmation Modal --}}
    @if ($showLinkConfirmation)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="link-confirmation-modal">
            <div class="flex items-center justify-center min-h-screen px-4">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/50 pointer-events-none"></div>

                {{-- Modal --}}
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full pointer-events-auto" @click.stop>
                    {{-- Header --}}
                    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Koble til YNAB
                        </h3>
                        <button wire:click="closeLinkConfirmation" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 cursor-pointer">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Content --}}
                    <div class="p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Velg hvilke felter du vil oppdatere fra YNAB:
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
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Navn</p>
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
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Saldo</p>
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
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Rente</p>
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
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Minimum betaling</p>
                                    @if (!empty($linkingYnabDebt) && $linkingYnabDebt['minimum_payment'])
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($linkingYnabDebt['minimum_payment'], 0, ',', ' ') }} kr/måned</p>
                                    @endif
                                </div>
                            </label>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mb-6">
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <strong>Viktig:</strong> YNAB-konto-ID vil alltid bli lagret for å koble gjeldene sammen.
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <button
                                wire:click="closeLinkConfirmation"
                                class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                Avbryt
                            </button>
                            <button
                                wire:click="confirmLinkToExistingDebt"
                                class="flex-1 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-500 dark:hover:bg-yellow-600 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                Koble til YNAB
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    <x-delete-confirmation-modal
        wire:model="showDeleteModal"
        :title="__('app.confirm_delete_debt', ['name' => $debtNameToDelete])"
        :message="__('app.delete_debt_warning')"
        on-confirm="deleteDebt"
    />
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
@endpush
