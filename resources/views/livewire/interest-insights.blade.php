<div wire:init="loadData">
    @if ($isLoading)
        <div class="animate-pulse space-y-6">
            {{-- Period toggle skeleton --}}
            <div class="flex items-center gap-2 mb-8">
                <div class="h-10 w-28 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
                <div class="h-10 w-28 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
            </div>
            {{-- Main card skeleton --}}
            <div class="h-32 bg-slate-200 dark:bg-slate-700 rounded-2xl"></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="h-48 bg-slate-200 dark:bg-slate-700 rounded-2xl"></div>
                <div class="h-48 bg-slate-200 dark:bg-slate-700 rounded-2xl"></div>
            </div>
        </div>
    @else
        {{-- Period Toggle --}}
        <div class="flex items-center gap-2 mb-8">
            <button
                wire:click="setPeriod('month')"
                type="button"
                class="px-5 py-2.5 text-sm font-medium rounded-xl transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 {{ $period === 'month' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700' }}"
            >
                {{ __('app.this_month') }}
            </button>
            <button
                wire:click="setPeriod('all')"
                type="button"
                class="px-5 py-2.5 text-sm font-medium rounded-xl transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 {{ $period === 'all' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700' }}"
            >
                {{ __('app.all_time') }}
            </button>
        </div>

        @if (!$this->hasPayments)
            {{-- Empty State --}}
            <div class="premium-card rounded-2xl p-12 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-2xl bg-slate-100 dark:bg-slate-800 mb-6">
                    <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="font-display text-xl font-semibold text-slate-900 dark:text-white mb-3">{{ __('app.no_payments_yet') }}</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">{{ __('app.no_payments_yet_description') }}</p>
            </div>
        @else
            {{-- Interest Breakdown Card --}}
            <div class="premium-card rounded-2xl p-6 mb-6">
                <h2 class="font-display text-lg font-semibold text-slate-900 dark:text-white mb-6">{{ __('app.interest_breakdown') }}</h2>

                {{-- Progress Bar --}}
                <div class="mb-8">
                    <div class="flex items-center justify-between text-sm mb-3">
                        <span class="text-slate-600 dark:text-slate-400">{{ __('app.total_paid') }}</span>
                        <span class="font-display font-bold text-slate-900 dark:text-white">{{ number_format($this->breakdown['total_paid'], 0, ',', ' ') }} kr</span>
                    </div>
                    <div class="h-4 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full flex">
                            <div
                                class="bg-emerald-500 transition-all duration-500"
                                style="width: {{ $this->principalPercentage }}%"
                            ></div>
                            <div
                                class="bg-rose-400 transition-all duration-500"
                                style="width: {{ $this->breakdown['interest_percentage'] }}%"
                            ></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-3 text-xs text-slate-500 dark:text-slate-400">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-emerald-500 rounded-sm"></div>
                            <span>{{ __('app.principal_paid') }} ({{ $this->principalPercentage }}%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-rose-400 rounded-sm"></div>
                            <span>{{ __('app.interest_paid') }} ({{ $this->breakdown['interest_percentage'] }}%)</span>
                        </div>
                    </div>
                </div>

                {{-- Summary Stats --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-5">
                        <div class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('app.principal_paid') }}</div>
                        <div class="font-display text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($this->breakdown['principal_paid'], 0, ',', ' ') }} kr</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('app.going_to_principal') }}</div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-5">
                        <div class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('app.interest_paid') }}</div>
                        <div class="font-display text-2xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($this->breakdown['interest_paid'], 0, ',', ' ') }} kr</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('app.going_to_interest') }}</div>
                    </div>
                </div>
            </div>

            {{-- Per Debt Breakdown --}}
            @if ($this->perDebtBreakdown->isNotEmpty())
                <div class="premium-card rounded-2xl p-6 mb-6">
                    <h2 class="font-display text-lg font-semibold text-slate-900 dark:text-white mb-6">{{ __('app.per_debt_breakdown') }}</h2>

                    <div class="overflow-x-auto -mx-6">
                        <table class="w-full min-w-[500px]">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-700">
                                    <th class="text-left py-3 px-6 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('app.debt') }}</th>
                                    <th class="text-right py-3 px-6 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('app.interest_paid') }}</th>
                                    <th class="text-right py-3 px-6 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('app.principal_paid') }}</th>
                                    <th class="text-right py-3 px-6 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('app.total_paid') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                @foreach ($this->perDebtBreakdown as $index => $debt)
                                    <tr wire:key="debt-breakdown-{{ $debt['debt_id'] }}" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class="py-4 px-6">
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $debt['debt_name'] }}</span>
                                            @if ($index === 0 && $debt['interest_paid'] > 0)
                                                <span class="ml-2 text-xs text-rose-600 dark:text-rose-400">{{ __('app.most_expensive_debt') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <span class="font-medium text-rose-600 dark:text-rose-400">{{ number_format($debt['interest_paid'], 0, ',', ' ') }} kr</span>
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($debt['principal_paid'], 0, ',', ' ') }} kr</span>
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <span class="font-display font-bold text-slate-900 dark:text-white">{{ number_format($debt['total_paid'], 0, ',', ' ') }} kr</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Extra Payment Optimizer --}}
            <div class="premium-card rounded-2xl p-6">
                <h2 class="font-display text-lg font-semibold text-slate-900 dark:text-white mb-6">{{ __('app.extra_payment_optimizer') }}</h2>

                {{-- Current Extra Payment --}}
                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-5 mb-6">
                    <div class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('app.current_extra_payment') }}</div>
                    <div class="font-display text-2xl font-bold text-slate-900 dark:text-white">
                        {{ number_format($this->currentExtraPayment, 0, ',', ' ') }} kr
                        <span class="text-base font-normal text-slate-400 dark:text-slate-500">{{ __('app.per_month') }}</span>
                    </div>
                </div>

                {{-- Scenarios Table --}}
                <div class="overflow-x-auto -mx-6">
                    <table class="w-full min-w-[500px]">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="text-left py-3 px-6 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('app.if_you_add') }}</th>
                                <th class="text-right py-3 px-6 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('app.you_save') }}</th>
                                <th class="text-right py-3 px-6 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('app.months_earlier') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            @foreach ($this->scenarios as $scenario)
                                <tr wire:key="scenario-{{ $scenario['increment'] }}" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="py-4 px-6">
                                        <span class="font-display font-semibold text-slate-900 dark:text-white">
                                            +{{ number_format($scenario['increment'], 0, ',', ' ') }} kr
                                            <span class="text-sm font-normal text-slate-400 dark:text-slate-500">{{ __('app.per_month') }}</span>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        @if ($scenario['savings'] > 0)
                                            <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($scenario['savings'], 0, ',', ' ') }} kr</span>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-500">-</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        @if ($scenario['months_saved'] > 0)
                                            <span class="font-medium text-cyan-600 dark:text-cyan-400">{{ $scenario['months_saved'] }} {{ trans_choice('app.months', $scenario['months_saved']) }}</span>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-500">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</div>
