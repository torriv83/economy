<div>
    {{-- Period Toggle --}}
    <div class="flex items-center gap-2 mb-6">
        <button
            wire:click="setPeriod('month')"
            type="button"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $period === 'month' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
        >
            {{ __('app.this_month') }}
        </button>
        <button
            wire:click="setPeriod('all')"
            type="button"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $period === 'all' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
        >
            {{ __('app.all_time') }}
        </button>
    </div>

    @if (!$this->hasPayments)
        {{-- Empty State --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/20 mb-4">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('app.no_payments_yet') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.no_payments_yet_description') }}</p>
        </div>
    @else
        {{-- Interest Breakdown Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('app.interest_breakdown') }}</h2>

            {{-- Progress Bar --}}
            <div class="mb-6">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('app.total_paid') }}</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($this->breakdown['total_paid'], 0, ',', ' ') }} kr</span>
                </div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full flex">
                        {{-- Principal portion (green) --}}
                        <div
                            class="bg-green-500 dark:bg-green-600 transition-all duration-500"
                            style="width: {{ $this->principalPercentage }}%"
                        ></div>
                        {{-- Interest portion (red/orange) --}}
                        <div
                            class="bg-amber-500 dark:bg-amber-600 transition-all duration-500"
                            style="width: {{ $this->breakdown['interest_percentage'] }}%"
                        ></div>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-green-500 dark:bg-green-600 rounded"></div>
                        <span>{{ __('app.principal_paid') }} ({{ $this->principalPercentage }}%)</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-amber-500 dark:bg-amber-600 rounded"></div>
                        <span>{{ __('app.interest_paid') }} ({{ $this->breakdown['interest_percentage'] }}%)</span>
                    </div>
                </div>
            </div>

            {{-- Summary Stats --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                    <div class="text-sm text-green-700 dark:text-green-400 mb-1">{{ __('app.principal_paid') }}</div>
                    <div class="text-xl font-bold text-green-800 dark:text-green-300">{{ number_format($this->breakdown['principal_paid'], 0, ',', ' ') }} kr</div>
                    <div class="text-xs text-green-600 dark:text-green-500 mt-1">{{ __('app.going_to_principal') }}</div>
                </div>
                <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                    <div class="text-sm text-amber-700 dark:text-amber-400 mb-1">{{ __('app.interest_paid') }}</div>
                    <div class="text-xl font-bold text-amber-800 dark:text-amber-300">{{ number_format($this->breakdown['interest_paid'], 0, ',', ' ') }} kr</div>
                    <div class="text-xs text-amber-600 dark:text-amber-500 mt-1">{{ __('app.going_to_interest') }}</div>
                </div>
            </div>
        </div>

        {{-- Per Debt Breakdown --}}
        @if ($this->perDebtBreakdown->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('app.per_debt_breakdown') }}</h2>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('app.debt') }}</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('app.interest_paid') }}</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('app.principal_paid') }}</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('app.total_paid') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->perDebtBreakdown as $index => $debt)
                                <tr wire:key="debt-breakdown-{{ $debt['debt_id'] }}" class="border-b border-gray-100 dark:border-gray-700/50 last:border-b-0 {{ $index === 0 && $debt['interest_paid'] > 0 ? 'bg-amber-50/50 dark:bg-amber-900/10' : '' }}">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $debt['debt_name'] }}</span>
                                            @if ($index === 0 && $debt['interest_paid'] > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">
                                                    {{ __('app.most_expensive_debt') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="text-amber-600 dark:text-amber-400 font-medium">{{ number_format($debt['interest_paid'], 0, ',', ' ') }} kr</span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="text-green-600 dark:text-green-400 font-medium">{{ number_format($debt['principal_paid'], 0, ',', ' ') }} kr</span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="text-gray-900 dark:text-white font-semibold">{{ number_format($debt['total_paid'], 0, ',', ' ') }} kr</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Extra Payment Optimizer --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('app.extra_payment_optimizer') }}</h2>

            {{-- Current Extra Payment --}}
            <div class="flex items-center justify-between p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 mb-4">
                <div>
                    <div class="text-sm text-blue-700 dark:text-blue-400">{{ __('app.current_extra_payment') }}</div>
                    <div class="text-xl font-bold text-blue-800 dark:text-blue-300">{{ number_format($this->currentExtraPayment, 0, ',', ' ') }} kr{{ __('app.per_month') }}</div>
                </div>
                <svg class="h-8 w-8 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                </svg>
            </div>

            {{-- Scenarios Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('app.if_you_add') }}</th>
                            <th class="text-right py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('app.you_save') }}</th>
                            <th class="text-right py-3 px-4 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('app.months_earlier') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->scenarios as $scenario)
                            <tr wire:key="scenario-{{ $scenario['increment'] }}" class="border-b border-gray-100 dark:border-gray-700/50 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="py-3 px-4">
                                    <span class="font-medium text-gray-900 dark:text-white">+{{ number_format($scenario['increment'], 0, ',', ' ') }} kr{{ __('app.per_month') }}</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    @if ($scenario['savings'] > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            {{ number_format($scenario['savings'], 0, ',', ' ') }} kr
                                        </span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    @if ($scenario['months_saved'] > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                            {{ $scenario['months_saved'] }} {{ trans_choice('app.months', $scenario['months_saved']) }}
                                        </span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
