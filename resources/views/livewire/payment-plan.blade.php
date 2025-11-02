<div>
    {{-- Header Section --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            {{ __('app.payment_plan') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            {{ __('app.payment_plan_description') }}
        </p>
    </div>

    {{-- Strategy Info Bar --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-blue-900 dark:text-blue-300">{{ __('app.selected_strategy') }}:</span>
            <span class="px-3 py-1 bg-blue-600 dark:bg-blue-500 text-white text-sm font-semibold rounded-full">
                {{ $this->strategy }} {{ __('app.avalanche_method') }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-blue-900 dark:text-blue-300">{{ __('app.extra_monthly_payment') }}:</span>
            <span class="text-blue-900 dark:text-blue-300 font-bold">{{ number_format($this->extraPayment, 0, ',', ' ') }} kr</span>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        {{-- Months to Debt-Free --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                        {{ __('app.months_to_debt_free') }}
                    </p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $this->totalMonths }}
                    </p>
                </div>
                <div class="h-12 w-12 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Payoff Date --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                        {{ __('app.payoff_date') }}
                    </p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $this->payoffDate }}
                    </p>
                </div>
                <div class="h-12 w-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Interest --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                        {{ __('app.total_interest') }}
                    </p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($this->totalInterest, 0, ',', ' ') }} kr
                    </p>
                </div>
                <div class="h-12 w-12 bg-orange-100 dark:bg-orange-900/20 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Overall Progress Bar --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Overall Progress</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($this->paymentSchedule[count($this->paymentSchedule) - 1]['progress'], 1) }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
            <div class="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full transition-all duration-500" style="width: {{ $this->paymentSchedule[count($this->paymentSchedule) - 1]['progress'] }}%"></div>
        </div>
    </div>

    {{-- Payment Schedule Timeline --}}
    <div class="space-y-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Monthly Payment Timeline</h2>

        {{-- Desktop Table View --}}
        <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.month') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.priority_debt') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.payment') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.remaining_balance') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.total_paid') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($this->paymentSchedule as $month)
                            <tr wire:key="month-{{ $month['month'] }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="shrink-0 h-10 w-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-blue-600 dark:text-blue-400 font-bold text-sm">{{ $month['month'] }}</span>
                                        </div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $month['monthName'] }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm font-semibold rounded-full">
                                        {{ $month['priorityDebt'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($month['payments'][0]['amount'], 0, ',', ' ') }} kr
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('app.minimum_payment') }}: {{ number_format($month['payments'][0]['minimum'], 0, ',', ' ') }} kr + {{ __('app.extra_payment') }}: {{ number_format($month['payments'][0]['extra'], 0, ',', ' ') }} kr
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($month['payments'][0]['remaining'], 0, ',', ' ') }} kr
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($month['totalPaid'], 0, ',', ' ') }} kr
                                    </div>
                                </td>
                            </tr>
                            {{-- Show other debts detail row --}}
                            <tr wire:key="month-{{ $month['month'] }}-details" class="bg-gray-50 dark:bg-gray-700/30">
                                <td colspan="5" class="px-6 py-3">
                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                        <span class="font-semibold">{{ __('app.other_debts') }}:</span>
                                        @foreach ($month['payments'] as $payment)
                                            @if (!$payment['isPriority'])
                                                <span class="ml-4">
                                                    {{ $payment['name'] }}: {{ number_format($payment['amount'], 0, ',', ' ') }} kr
                                                    ({{ __('app.remaining_balance') }}: {{ number_format($payment['remaining'], 0, ',', ' ') }} kr)
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Card View --}}
        <div class="lg:hidden space-y-4">
            @foreach ($this->paymentSchedule as $month)
                <div wire:key="month-card-{{ $month['month'] }}" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {{-- Month Header --}}
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 bg-white/20 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold text-sm">{{ $month['month'] }}</span>
                                </div>
                                <h3 class="text-white font-bold">{{ $month['monthName'] }}</h3>
                            </div>
                            <div class="text-white text-sm font-medium">
                                {{ number_format($month['progress'], 1) }}%
                            </div>
                        </div>
                    </div>

                    {{-- Priority Debt Section --}}
                    <div class="p-4 bg-green-50 dark:bg-green-900/10 border-b border-green-100 dark:border-green-900/30">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span class="text-sm font-bold text-green-900 dark:text-green-300">{{ __('app.priority_debt') }}</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Debt:</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ $month['priorityDebt'] }}</span>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.payment') }}:</span>
                                <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($month['payments'][0]['amount'], 0, ',', ' ') }} kr</span>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.remaining_balance') }}:</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ number_format($month['payments'][0]['remaining'], 0, ',', ' ') }} kr</span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 pt-1">
                                {{ __('app.minimum_payment') }}: {{ number_format($month['payments'][0]['minimum'], 0, ',', ' ') }} kr + {{ __('app.extra_payment') }}: {{ number_format($month['payments'][0]['extra'], 0, ',', ' ') }} kr
                            </div>
                        </div>
                    </div>

                    {{-- Other Debts Section --}}
                    <div class="p-4">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">{{ __('app.other_debts') }}</h4>
                        <div class="space-y-3">
                            @foreach ($month['payments'] as $payment)
                                @if (!$payment['isPriority'])
                                    <div wire:key="month-{{ $month['month'] }}-payment-{{ $loop->index }}" class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $payment['name'] }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($payment['remaining'], 0, ',', ' ') }} kr remaining</div>
                                        </div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ number_format($payment['amount'], 0, ',', ' ') }} kr
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Total Paid This Month --}}
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('app.total_paid') }}</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($month['totalPaid'], 0, ',', ' ') }} kr</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Detailed Repayment Schedule - Every Debt, Every Month --}}
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
            {{ __('app.complete_repayment_schedule') }}
        </h2>

        {{-- Desktop Table View --}}
        <div class="hidden md:block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.month') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.debt_name') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.payment') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.remaining_balance') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($this->detailedSchedule as $month)
                            @php
                                $rowCount = count($month['payments']);
                                $allPaidOff = collect($month['payments'])->every(fn($p) => $p['remaining'] <= 0.01);
                                $rowClass = $allPaidOff ? 'bg-green-50 dark:bg-green-900/20' : ($month['month'] % 2 == 1 ? 'bg-gray-50 dark:bg-gray-700/30' : '');
                            @endphp

                            @foreach ($month['payments'] as $index => $payment)
                                <tr wire:key="detail-{{ $month['month'] }}-{{ $index }}" class="{{ $rowClass }}">
                                    @if ($index === 0)
                                        <td rowspan="{{ $rowCount }}" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white align-top border-r border-gray-300 dark:border-gray-600">
                                            {{ $month['month'] }}<br>
                                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($month['date'])->locale('nb')->translatedFormat('M Y') }}</span>
                                        </td>
                                    @endif
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white {{ $payment['remaining'] <= 0.01 ? 'font-medium' : '' }}">
                                        {{ $payment['name'] }}
                                        @if ($payment['remaining'] <= 0.01)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-600 dark:bg-green-700 text-white">
                                                {{ __('app.paid_off') }}!
                                            </span>
                                        @elseif ($payment['isPriority'])
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-600 dark:bg-blue-500 text-white">
                                                {{ __('app.now_priority') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right {{ $payment['isPriority'] ? 'font-semibold text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}">
                                        {{ number_format($payment['amount'], 0, ',', ' ') }} kr
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right {{ $payment['remaining'] <= 0.01 ? 'font-bold text-green-600 dark:text-green-400' : 'font-medium text-gray-900 dark:text-white' }}">
                                        {{ number_format(max(0, $payment['remaining']), 0, ',', ' ') }} kr
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Separator between months --}}
                            @if (!$loop->last)
                                <tr>
                                    <td colspan="4" class="border-b-2 border-gray-300 dark:border-gray-600"></td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Card View --}}
        <div class="md:hidden space-y-6">
            @foreach ($this->detailedSchedule as $month)
                <div wire:key="detail-mobile-{{ $month['month'] }}" class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                        <div class="font-bold text-gray-900 dark:text-white">{{ __('app.month') }} {{ $month['month'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($month['date'])->locale('nb')->translatedFormat('F Y') }}
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($month['payments'] as $payment)
                            <div class="p-4">
                                <div class="font-medium text-gray-900 dark:text-white mb-2">
                                    {{ $payment['name'] }}
                                    @if ($payment['remaining'] <= 0.01)
                                        <span class="ml-2 text-xs text-green-600 dark:text-green-400 font-semibold">{{ __('app.paid_off') }}</span>
                                    @elseif ($payment['isPriority'])
                                        <span class="ml-2 text-xs text-blue-600 dark:text-blue-400 font-semibold">{{ __('app.now_priority') }}</span>
                                    @endif
                                </div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('app.payment') }}:</span>
                                    <span class="{{ $payment['isPriority'] ? 'font-semibold text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}">
                                        {{ number_format($payment['amount'], 0, ',', ' ') }} kr
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('app.remaining_balance') }}:</span>
                                    <span class="font-medium {{ $payment['remaining'] <= 0.01 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                                        {{ number_format(max(0, $payment['remaining']), 0, ',', ' ') }} kr
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Load More / Show All Buttons --}}
        @if ($this->visibleMonths < $this->totalMonths)
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <button
                    type="button"
                    wire:click="loadMoreMonths"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="loadMoreMonths">
                        {{ __('app.load_more') }} ({{ min(12, $this->totalMonths - $this->visibleMonths) }} {{ trans_choice('app.months', min(12, $this->totalMonths - $this->visibleMonths)) }})
                    </span>
                    <span wire:loading wire:target="loadMoreMonths" class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('app.loading') }}...
                    </span>
                </button>
                <button
                    type="button"
                    wire:click="showAllMonths"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="showAllMonths">
                        {{ __('app.show_all') }} ({{ $this->totalMonths }} {{ trans_choice('app.months', $this->totalMonths) }})
                    </span>
                    <span wire:loading wire:target="showAllMonths" class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('app.loading') }}...
                    </span>
                </button>
            </div>
        @else
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('app.showing_all_months') }} ({{ $this->totalMonths }} {{ trans_choice('app.months', $this->totalMonths) }})
                </p>
            </div>
        @endif
    </div>

</div>
