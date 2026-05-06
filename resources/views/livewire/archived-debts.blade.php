<div wire:init="loadData">
    @if ($isLoading)
        <div class="animate-pulse space-y-6">
            <div class="space-y-2">
                <div class="h-8 w-48 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                <div class="h-4 w-64 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="h-32 bg-slate-200 dark:bg-slate-700 rounded-2xl"></div>
                <div class="h-32 bg-slate-200 dark:bg-slate-700 rounded-2xl"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div class="h-56 bg-slate-200 dark:bg-slate-700 rounded-2xl"></div>
                <div class="h-56 bg-slate-200 dark:bg-slate-700 rounded-2xl"></div>
                <div class="h-56 bg-slate-200 dark:bg-slate-700 rounded-2xl"></div>
            </div>
        </div>
    @else
        <x-page-header
            :title="__('app.archived_debts')"
            :subtitle="__('app.archived_debts_description')"
        />

        @if (session()->has('message'))
            <div class="mb-6 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                {{ session('message') }}
            </div>
        @endif

        @if (count($this->debts) === 0)
            <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-12 text-center">
                <div class="mx-auto mb-4 w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <h3 class="font-display text-xl font-bold text-slate-900 dark:text-white mb-2">
                    {{ __('app.no_archived_debts') }}
                </h3>
                <p class="text-slate-600 dark:text-slate-400">
                    {{ __('app.no_archived_debts_description') }}
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
                <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6">
                    <div class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">
                        {{ __('app.archived_count') }}
                    </div>
                    <div class="font-display text-3xl font-bold text-slate-900 dark:text-white">
                        {{ $this->archivedCount }}
                    </div>
                </div>
                <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6">
                    <div class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">
                        {{ __('app.total_paid_off') }}
                    </div>
                    <div class="font-display text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ number_format($this->totalPaidOff, 0, ',', ' ') }} kr
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach ($this->debts as $debt)
                    <div
                        wire:key="archived-{{ $debt['id'] }}"
                        class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6 opacity-75 hover:opacity-100 transition-opacity"
                    >
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-display text-lg font-bold text-slate-700 dark:text-slate-300 truncate">
                                    {{ $debt['name'] }}
                                </h3>
                                @if ($debt['paidOffAt'])
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                        {{ __('app.paid_off_on') }}: {{ $debt['paidOffAt'] }}
                                    </p>
                                @endif
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-emerald-500 text-white">
                                {{ __('app.paid_off') }}
                            </span>
                        </div>

                        <dl class="space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <dt class="text-slate-500 dark:text-slate-400">{{ __('app.original_balance') }}</dt>
                                <dd class="font-medium text-slate-700 dark:text-slate-300">
                                    {{ number_format($debt['originalBalance'], 0, ',', ' ') }} kr
                                </dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-slate-500 dark:text-slate-400">{{ __('app.total_paid') }}</dt>
                                <dd class="font-medium text-slate-700 dark:text-slate-300">
                                    {{ number_format($debt['totalPaid'], 0, ',', ' ') }} kr
                                </dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-slate-500 dark:text-slate-400">{{ __('app.total_interest_paid') }}</dt>
                                <dd class="font-medium text-amber-600 dark:text-amber-400">
                                    {{ number_format($debt['totalInterest'], 0, ',', ' ') }} kr
                                </dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-slate-500 dark:text-slate-400">{{ __('app.months_to_payoff') }}</dt>
                                <dd class="font-medium text-slate-700 dark:text-slate-300">
                                    {{ $debt['monthsCount'] }} {{ trans_choice('app.months', $debt['monthsCount']) }}
                                </dd>
                            </div>
                        </dl>

                        <div class="pt-4 border-t border-slate-200 dark:border-slate-700/50">
                            <label class="flex items-center justify-between cursor-pointer">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ __('app.show_in_charts_and_plans') }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="toggleIncludeInCharts({{ $debt['id'] }})"
                                    role="switch"
                                    aria-checked="{{ $debt['includeInCharts'] ? 'true' : 'false' }}"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 cursor-pointer {{ $debt['includeInCharts'] ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}"
                                >
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $debt['includeInCharts'] ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
