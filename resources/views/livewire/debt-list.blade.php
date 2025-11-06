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
                        wire:click="enableReorderMode"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 focus:ring-offset-2">
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
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 focus:ring-offset-2">
                        {{ __('app.cancel') }}
                    </button>
                    <button
                        type="button"
                        x-on:click="saveOrder"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
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
                                            x-on:click="if (confirm('{{ __('app.confirm_delete_debt') }}')) $wire.deleteDebt({{ $debt['id'] }})"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50"
                                            aria-label="{{ __('app.delete_debt', ['name' => $debt['name']]) }}"
                                            class="flex-1 px-3 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-500 dark:focus:ring-red-400 focus:ring-offset-2">
                                        <span wire:loading.remove wire:target="deleteDebt({{ $debt['id'] }})">
                                            {{ __('app.delete') }}
                                        </span>
                                        <span wire:loading wire:target="deleteDebt({{ $debt['id'] }})" class="inline-flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ __('app.deleting') }}
                                        </span>
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
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
@endpush
