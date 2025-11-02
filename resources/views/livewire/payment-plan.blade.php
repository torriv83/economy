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
                {{ $strategy }} {{ __('app.avalanche_method') }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-blue-900 dark:text-blue-300">{{ __('app.extra_monthly_payment') }}:</span>
            <span class="text-blue-900 dark:text-blue-300 font-bold">{{ number_format($extraPayment, 0, ',', ' ') }} kr</span>
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
                        {{ $totalMonths }}
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
                        {{ $payoffDate }}
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
                        {{ number_format($totalInterest, 0, ',', ' ') }} kr
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
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($paymentSchedule[count($paymentSchedule) - 1]['progress'], 1) }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
            <div class="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full transition-all duration-500" style="width: {{ $paymentSchedule[count($paymentSchedule) - 1]['progress'] }}%"></div>
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
                        @foreach ($paymentSchedule as $month)
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
                                        {{ __('app.minimum_payment') }}: 500 kr + {{ __('app.extra_payment') }}: {{ number_format($extraPayment, 0, ',', ' ') }} kr
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
            @foreach ($paymentSchedule as $month)
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
                                {{ __('app.minimum_payment') }}: 500 kr + {{ __('app.extra_payment') }}: {{ number_format($extraPayment, 0, ',', ' ') }} kr
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

    {{-- Complete Repayment Schedule - Every Debt, Every Month --}}
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
                        {{-- Month 1 --}}
                        <tr class="bg-gray-50 dark:bg-gray-700/30">
                            <td rowspan="3" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white align-top border-r border-gray-300 dark:border-gray-600">
                                1<br><span class="text-xs font-normal text-gray-500 dark:text-gray-400">Jan 2025</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Kredittkort</td>
                            <td class="px-4 py-2 text-sm text-right font-semibold text-green-600 dark:text-green-400">2 500 kr</td>
                            <td class="px-4 py-2 text-sm text-right font-medium text-gray-900 dark:text-white">47 500 kr</td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-gray-700/30">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Studielån</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">0 kr</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">200 000 kr</td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-gray-700/30 border-b-2 border-gray-300 dark:border-gray-600">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Billån</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">1 200 kr</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">73 800 kr</td>
                        </tr>

                        {{-- Month 2 --}}
                        <tr>
                            <td rowspan="3" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white align-top border-r border-gray-300 dark:border-gray-600">
                                2<br><span class="text-xs font-normal text-gray-500 dark:text-gray-400">Feb 2025</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Kredittkort</td>
                            <td class="px-4 py-2 text-sm text-right font-semibold text-green-600 dark:text-green-400">2 500 kr</td>
                            <td class="px-4 py-2 text-sm text-right font-medium text-gray-900 dark:text-white">45 000 kr</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Studielån</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">0 kr</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">200 000 kr</td>
                        </tr>
                        <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Billån</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">1 200 kr</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">72 600 kr</td>
                        </tr>

                        {{-- Month 3-6 showing progression --}}
                        <tr class="bg-gray-50 dark:bg-gray-700/30">
                            <td rowspan="3" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white align-top border-r border-gray-300 dark:border-gray-600">
                                3<br><span class="text-xs font-normal text-gray-500 dark:text-gray-400">Mar 2025</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Kredittkort</td>
                            <td class="px-4 py-2 text-sm text-right font-semibold text-green-600 dark:text-green-400">2 500 kr</td>
                            <td class="px-4 py-2 text-sm text-right font-medium text-gray-900 dark:text-white">42 500 kr</td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-gray-700/30">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Studielån</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">0 kr</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">200 000 kr</td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-gray-700/30 border-b-2 border-gray-300 dark:border-gray-600">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Billån</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">1 200 kr</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">71 400 kr</td>
                        </tr>

                        {{-- Month 20 - Kredittkort paid off --}}
                        <tr class="bg-green-50 dark:bg-green-900/20">
                            <td rowspan="3" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white align-top border-r border-gray-300 dark:border-gray-600">
                                20<br><span class="text-xs font-normal text-gray-500 dark:text-gray-400">Aug 2026</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">
                                Kredittkort
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-600 dark:bg-green-700 text-white">
                                    {{ __('app.paid_off') }}!
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-right font-semibold text-green-600 dark:text-green-400">2 500 kr</td>
                            <td class="px-4 py-2 text-sm text-right font-bold text-green-600 dark:text-green-400">0 kr</td>
                        </tr>
                        <tr class="bg-green-50 dark:bg-green-900/20">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Studielån</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">0 kr</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">200 000 kr</td>
                        </tr>
                        <tr class="bg-green-50 dark:bg-green-900/20 border-b-2 border-gray-300 dark:border-gray-600">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Billån</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">1 200 kr</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">45 000 kr</td>
                        </tr>

                        {{-- Month 21 - Payment rolls over to next debt (SNOWBALL EFFECT) --}}
                        <tr class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 dark:border-blue-400">
                            <td rowspan="3" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white align-top border-r border-gray-300 dark:border-gray-600">
                                21<br><span class="text-xs font-normal text-gray-500 dark:text-gray-400">Sep 2026</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 line-through">
                                Kredittkort
                            </td>
                            <td class="px-4 py-2 text-sm text-right text-gray-400 dark:text-gray-500 line-through">0 kr</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-400 dark:text-gray-500">0 kr</td>
                        </tr>
                        <tr class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 dark:border-blue-400">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">Studielån</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">0 kr</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-400">200 000 kr</td>
                        </tr>
                        <tr class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 dark:border-blue-400 border-b-2 border-gray-300 dark:border-gray-600">
                            <td class="px-4 py-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-900 dark:text-white font-medium">Billån</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-600 dark:bg-blue-500 text-white">
                                        {{ __('app.now_priority') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm text-right">
                                <div class="flex flex-col items-end gap-1">
                                    <span class="font-bold text-blue-600 dark:text-blue-400 text-base">3 700 kr</span>
                                    <div class="flex items-center gap-1 text-xs">
                                        <svg class="h-3 w-3 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                        </svg>
                                        <span class="text-green-600 dark:text-green-400 font-semibold">+2 500 kr</span>
                                        <span class="text-gray-500 dark:text-gray-400">{{ __('app.from_debt', ['debt' => 'Kredittkort']) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm text-right font-medium text-gray-900 dark:text-white">41 300 kr</td>
                        </tr>

                        {{-- Continuing pattern with ... indicator --}}
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400 italic">
                                ... {{ __('app.continues_for_months', ['months' => 4]) }} ...
                            </td>
                        </tr>

                        {{-- Final Month (Month 27) --}}
                        <tr class="bg-green-50 dark:bg-green-900/20">
                            <td rowspan="3" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white align-top border-r border-gray-300 dark:border-gray-600">
                                27<br><span class="text-xs font-normal text-gray-500 dark:text-gray-400">Mar 2027</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">
                                Kredittkort
                                <span class="ml-2 text-xs text-green-600 dark:text-green-400 font-semibold">{{ __('app.paid_off') }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-right text-gray-400 dark:text-gray-500 line-through">0 kr</td>
                            <td class="px-4 py-2 text-sm text-right font-bold text-green-600 dark:text-green-400">0 kr</td>
                        </tr>
                        <tr class="bg-green-50 dark:bg-green-900/20">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">
                                Studielån
                                <span class="ml-2 text-xs text-green-600 dark:text-green-400 font-semibold">{{ __('app.paid_off') }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-right text-gray-400 dark:text-gray-500 line-through">0 kr</td>
                            <td class="px-4 py-2 text-sm text-right font-bold text-green-600 dark:text-green-400">0 kr</td>
                        </tr>
                        <tr class="bg-green-50 dark:bg-green-900/20">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">
                                Billån
                                <span class="ml-2 text-xs text-green-600 dark:text-green-400 font-semibold">{{ __('app.paid_off') }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-right text-gray-400 dark:text-gray-500 line-through">0 kr</td>
                            <td class="px-4 py-2 text-sm text-right font-bold text-green-600 dark:text-green-400">0 kr</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Card View --}}
        <div class="md:hidden space-y-6">
            {{-- Month 1-3 samples --}}
            @foreach ([1, 2, 3] as $monthNum)
                <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                        <div class="font-bold text-gray-900 dark:text-white">{{ __('app.month') }} {{ $monthNum }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            @if($monthNum == 1) Januar 2025
                            @elseif($monthNum == 2) Februar 2025
                            @else Mars 2025 @endif
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div class="p-4">
                            <div class="font-medium text-gray-900 dark:text-white mb-2">Kredittkort</div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('app.payment') }}:</span>
                                <span class="font-semibold text-green-600 dark:text-green-400">2 500 kr</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('app.remaining_balance') }}:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ number_format(50000 - ($monthNum * 2500), 0, ',', ' ') }} kr</span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="font-medium text-gray-900 dark:text-white mb-2">Studielån</div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('app.payment') }}:</span>
                                <span class="text-gray-600 dark:text-gray-400">0 kr</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('app.remaining_balance') }}:</span>
                                <span class="text-gray-600 dark:text-gray-400">200 000 kr</span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="font-medium text-gray-900 dark:text-white mb-2">Billån</div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('app.payment') }}:</span>
                                <span class="text-gray-600 dark:text-gray-400">1 200 kr</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('app.remaining_balance') }}:</span>
                                <span class="text-gray-600 dark:text-gray-400">{{ number_format(75000 - ($monthNum * 1200), 0, ',', ' ') }} kr</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Indicator for more months --}}
            <div class="text-center py-4">
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                    ... {{ __('app.continues_for_months', ['months' => 24]) }} ...
                </p>
            </div>
        </div>
    </div>

    {{-- Info Notice --}}
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex gap-3">
            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-900 dark:text-blue-300">
                <p class="font-medium mb-1">Mock Data for UI Design</p>
                <p>This payment plan shows sample data for visualization purposes. The actual calculations will be implemented when the debt payoff logic is added.</p>
            </div>
        </div>
    </div>
</div>
