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
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <div class="flex flex-col sm:flex-row flex-wrap items-start sm:items-center gap-4">
            {{-- Strategy Toggle Buttons --}}
            <div class="flex-1">
                <label class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2 block">
                    {{ __('app.selected_strategy') }}
                </label>
                <div class="inline-flex rounded-lg border border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 p-1">
                    <button
                        type="button"
                        wire:click="$set('strategy', 'avalanche')"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 {{ $this->strategy === 'avalanche' ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-sm' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >
                        <span class="flex items-center gap-2">
                            @if ($this->strategy === 'avalanche')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @endif
                            {{ __('app.avalanche_method') }}
                        </span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('strategy', 'snowball')"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 {{ $this->strategy === 'snowball' ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-sm' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >
                        <span class="flex items-center gap-2">
                            @if ($this->strategy === 'snowball')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @endif
                            {{ __('app.snowball_method') }}
                        </span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('strategy', 'custom')"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 {{ $this->strategy === 'custom' ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-sm' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >
                        <span class="flex items-center gap-2">
                            @if ($this->strategy === 'custom')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @endif
                            {{ __('app.custom_order') }}
                        </span>
                    </button>
                </div>
            </div>

            {{-- Extra Monthly Payment Input --}}
            <div class="flex-1">
                <label for="extraPayment" class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2 block">
                    {{ __('app.extra_monthly_payment') }}
                </label>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="$set('extraPayment', {{ max(0, $this->extraPayment - 500) }})"
                        class="h-10 w-10 flex items-center justify-center bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 rounded-lg text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
                        aria-label="Decrease by 500"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </button>
                    <div class="relative flex-1 min-w-[160px]">
                        <input
                            type="number"
                            id="extraPayment"
                            wire:model.live.debounce.300ms="extraPayment"
                            min="0"
                            max="1000000"
                            step="100"
                            class="w-full px-4 py-2 pr-10 bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 rounded-lg text-blue-900 dark:text-blue-100 font-bold text-center focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                        >
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-blue-600 dark:text-blue-400 font-medium pointer-events-none">
                            kr
                        </span>
                    </div>
                    <button
                        type="button"
                        wire:click="$set('extraPayment', {{ $this->extraPayment + 500 }})"
                        class="h-10 w-10 flex items-center justify-center bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 rounded-lg text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
                        aria-label="Increase by 500"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-blue-700 dark:text-blue-300">
                    {{ number_format($this->extraPayment, 0, ',', ' ') }} kr
                </p>
                @error('extraPayment')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
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
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('app.overall_progress') }}</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($this->overallProgress, 1) }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
            <div class="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full transition-all duration-500" style="width: {{ $this->overallProgress }}%"></div>
        </div>
    </div>

    {{-- Debt Payoff Overview --}}
    <div class="space-y-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ __('app.debt_payoff_overview') }}</h2>

        {{-- Desktop Table View --}}
        <div class="hidden md:block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.debt_name') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.current_balance') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.payoff_date') }}
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Handlinger
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($this->debtPayoffSchedule as $debt)
                            <tr wire:key="payoff-{{ $loop->index }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $debt['name'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($debt['balance'], 0, ',', ' ') }} kr
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    @if ($debt['payoff_date'])
                                        <div class="text-sm font-medium text-green-600 dark:text-green-400">
                                            {{ $debt['payoff_date'] }}
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            -
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if (isset($this->debts[$debt['name']]))
                                        <button
                                            wire:click="openReconciliationModal({{ $this->debts[$debt['name']]->id }})"
                                            type="button"
                                            class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                        >
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                            </svg>
                                            Avstem
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Card View --}}
        <div class="md:hidden space-y-4">
            @foreach ($this->debtPayoffSchedule as $debt)
                <div wire:key="payoff-card-{{ $loop->index }}" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex justify-between items-start mb-2">
                        <div class="font-bold text-gray-900 dark:text-white">{{ $debt['name'] }}</div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-baseline">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.current_balance') }}:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($debt['balance'], 0, ',', ' ') }} kr</span>
                        </div>
                        <div class="flex justify-between items-baseline">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.payoff_date') }}:</span>
                            @if ($debt['payoff_date'])
                                <span class="font-medium text-green-600 dark:text-green-400">{{ $debt['payoff_date'] }}</span>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                            @endif
                        </div>
                    </div>
                    @if (isset($this->debts[$debt['name']]))
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <button
                                wire:click="openReconciliationModal({{ $this->debts[$debt['name']]->id }})"
                                type="button"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                Avstem
                            </button>
                        </div>
                    @endif
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
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.notes') }}
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('app.paid') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @php
                            $paidOffDebts = []; // Track which debts have been fully paid off
                        @endphp
                        @foreach ($this->detailedSchedule as $month)
                            @php
                                $rowCount = count($month['payments']);
                                $isHistorical = isset($month['isHistorical']) && $month['isHistorical'];
                                $allPaidOff = collect($month['payments'])->every(fn($p) => $p['remaining'] <= 0.01);

                                if ($isHistorical) {
                                    $rowClass = 'bg-blue-50 dark:bg-blue-900/10';
                                } elseif ($allPaidOff) {
                                    $rowClass = 'bg-green-50 dark:bg-green-900/20';
                                } else {
                                    $rowClass = $month['month'] % 2 == 1 ? 'bg-gray-50 dark:bg-gray-700/30' : '';
                                }
                            @endphp

                            @foreach ($month['payments'] as $index => $payment)
                                @php
                                    $debt = $this->debts->get($payment['name']);
                                    $debtId = $debt ? $debt->id : 0;
                                    $paymentKey = $month['month'] . '_' . $debtId;
                                    $isPaid = $debt ? $this->paymentService->paymentExists($debtId, $month['month']) : false;
                                @endphp
                                <tr wire:key="detail-{{ $month['month'] }}-{{ $index }}" class="{{ $rowClass }}">
                                    @if ($index === 0)
                                        <td rowspan="{{ $rowCount }}" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white align-top border-r border-gray-300 dark:border-gray-600">
                                            <div class="flex flex-col gap-2">
                                                <div>
                                                    {{ $month['month'] }}<br>
                                                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($month['date'])->locale('nb')->translatedFormat('M Y') }}</span>
                                                    @if ($isHistorical)
                                                        <span class="block mt-1 text-xs font-semibold text-blue-600 dark:text-blue-400">{{ __('app.historical') }}</span>
                                                    @endif
                                                </div>
                                                @if (!$isHistorical)
                                                    <button
                                                        type="button"
                                                        wire:click="markMonthAsPaid({{ $month['month'] }})"
                                                        aria-label="{{ $this->isMonthFullyPaid($month['month']) ? __('app.unmark_all_as_paid') : __('app.mark_all_as_paid') }}"
                                                        class="text-xs px-2 py-1 {{ $this->isMonthFullyPaid($month['month']) ? 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600' : 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600' }} text-white rounded transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
                                                    >
                                                        {{ $this->isMonthFullyPaid($month['month']) ? __('app.unmark_all_as_paid') : __('app.mark_all_as_paid') }}
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white {{ $payment['remaining'] <= 0.01 ? 'font-medium' : '' }}">
                                        {{ $payment['name'] }}
                                        @php
                                            // Only show "Nedbetalt" the FIRST time debt reaches 0
                                            $justPaidOff = $payment['remaining'] <= 0.01 && !in_array($payment['name'], $paidOffDebts);
                                            if ($justPaidOff) {
                                                $paidOffDebts[] = $payment['name'];
                                            }
                                        @endphp
                                        @if ($justPaidOff)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-600 dark:bg-green-700 text-white">
                                                {{ __('app.paid_off') }}!
                                            </span>
                                        @elseif ($payment['remaining'] > 0.01 && $payment['isPriority'])
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-600 dark:bg-blue-500 text-white">
                                                {{ __('app.now_priority') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right {{ $payment['isPriority'] ? 'font-semibold text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}">
                                        @if ($isPaid && $debt)
                                            @php
                                                $paymentRecord = $this->paymentService->getPayment($debtId, $month['month']);
                                                $actualAmount = $paymentRecord ? $paymentRecord->actual_amount : $payment['amount'];
                                                $isReconciliation = $paymentRecord && $paymentRecord->is_reconciliation_adjustment;
                                                $key = $month['month'] . '_' . $debtId;
                                                if (!isset($this->editingPayments[$key])) {
                                                    $this->editingPayments[$key] = $actualAmount;
                                                }
                                            @endphp
                                            <div class="flex items-center gap-2 justify-end">
                                                @if ($isReconciliation)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 border border-yellow-300 dark:border-yellow-700" title="{{ __('app.reconciliation_adjustment') }}">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        {{ __('app.reconciliation') }}
                                                    </span>
                                                @endif
                                                <input
                                                    type="number"
                                                    wire:model.live.debounce.500ms="editingPayments.{{ $key }}"
                                                    wire:blur="updatePaymentAmount({{ $month['month'] }}, {{ $debtId }})"
                                                    class="w-24 px-2 py-1 text-sm text-right {{ $isReconciliation ? 'bg-yellow-50 dark:bg-yellow-900/10 border-yellow-300 dark:border-yellow-700' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' }} border rounded focus:ring-blue-500 dark:focus:ring-blue-400 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                >
                                                <span class="text-xs">kr</span>
                                            </div>
                                        @else
                                            {{ number_format(abs($payment['amount']), 0, ',', ' ') }} kr
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right {{ $payment['remaining'] <= 0.01 ? 'font-bold text-green-600 dark:text-green-400' : 'font-medium text-gray-900 dark:text-white' }}">
                                        {{ number_format(max(0, $payment['remaining']), 0, ',', ' ') }} kr
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        @if ($isPaid && $debt)
                                            @php
                                                $paymentRecord = $this->paymentService->getPayment($debtId, $month['month']);
                                                $hasNote = $paymentRecord && $paymentRecord->notes;
                                                $key = $month['month'] . '_' . $debtId;
                                            @endphp
                                            @if ($hasNote || (isset($this->showNoteInput[$key]) && $this->showNoteInput[$key]))
                                                <div class="flex items-center justify-center gap-1">
                                                    <button
                                                        type="button"
                                                        wire:click="toggleNoteInput({{ $month['month'] }}, {{ $debtId }})"
                                                        class="p-1 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors cursor-pointer"
                                                        title="{{ $hasNote ? __('app.view_note') : __('app.add_note') }}"
                                                    >
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="toggleNoteInput({{ $month['month'] }}, {{ $debtId }})"
                                                    class="p-1 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors cursor-pointer"
                                                    title="{{ __('app.add_note') }}"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        @if ($payment['amount'] > 0 && $debt && !$isHistorical)
                                            <input
                                                type="checkbox"
                                                wire:click="togglePayment({{ $month['month'] }}, {{ $debtId }})"
                                                @if($isPaid) checked @endif
                                                class="h-4 w-4 text-blue-600 dark:text-blue-500 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:focus:ring-blue-400 cursor-pointer"
                                            >
                                        @elseif ($isHistorical && $isPaid)
                                            <svg class="h-4 w-4 text-green-600 dark:text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </td>
                                </tr>
                                @if ($isPaid && $debt && isset($this->showNoteInput[$key]) && $this->showNoteInput[$key])
                                    <tr wire:key="note-{{ $month['month'] }}-{{ $index }}" class="{{ $rowClass }}">
                                        <td colspan="7" class="px-4 py-3">
                                            <div class="max-w-2xl">
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    {{ __('app.payment_notes') }}
                                                </label>
                                                <div class="flex items-start gap-2">
                                                    <textarea
                                                        wire:model.live="editingNotes.{{ $key }}"
                                                        rows="2"
                                                        placeholder="{{ __('app.note_placeholder') }}"
                                                        class="flex-1 px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 placeholder:text-gray-500 dark:placeholder:text-gray-400"
                                                    ></textarea>
                                                    <div class="flex flex-col gap-1">
                                                        <button
                                                            type="button"
                                                            wire:click="saveNote({{ $month['month'] }}, {{ $debtId }})"
                                                            class="px-3 py-1 text-xs bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded transition-colors cursor-pointer"
                                                        >
                                                            {{ __('app.save_note') }}
                                                        </button>
                                                        @if ($hasNote)
                                                            <button
                                                                type="button"
                                                                wire:click="deleteNote({{ $month['month'] }}, {{ $debtId }})"
                                                                class="px-3 py-1 text-xs bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded transition-colors cursor-pointer"
                                                            >
                                                                {{ __('app.delete_note') }}
                                                            </button>
                                                        @endif
                                                        <button
                                                            type="button"
                                                            wire:click="toggleNoteInput({{ $month['month'] }}, {{ $debtId }})"
                                                            class="px-3 py-1 text-xs bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded transition-colors cursor-pointer"
                                                        >
                                                            {{ __('app.cancel') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach

                            {{-- Separator between months --}}
                            @if (!$loop->last)
                                <tr>
                                    <td colspan="6" class="border-b-2 border-gray-300 dark:border-gray-600"></td>
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
                @php
                    $isHistorical = isset($month['isHistorical']) && $month['isHistorical'];
                @endphp
                <div wire:key="detail-mobile-{{ $month['month'] }}" class="bg-white dark:bg-gray-800 rounded-lg border-2 {{ $isHistorical ? 'border-blue-300 dark:border-blue-700' : 'border-gray-200 dark:border-gray-700' }} overflow-hidden">
                    <div class="{{ $isHistorical ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-gray-100 dark:bg-gray-700' }} px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-bold text-gray-900 dark:text-white">{{ __('app.month') }} {{ $month['month'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($month['date'])->locale('nb')->translatedFormat('F Y') }}
                                </div>
                                @if ($isHistorical)
                                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">{{ __('app.historical') }}</span>
                                @endif
                            </div>
                            @if (!$isHistorical)
                                <button
                                    type="button"
                                    wire:click="markMonthAsPaid({{ $month['month'] }})"
                                    aria-label="{{ $this->isMonthFullyPaid($month['month']) ? __('app.unmark_all_as_paid') : __('app.mark_all_as_paid') }}"
                                    class="text-xs px-3 py-1.5 {{ $this->isMonthFullyPaid($month['month']) ? 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600' : 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600' }} text-white rounded transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
                                >
                                    {{ $this->isMonthFullyPaid($month['month']) ? __('app.unmark_all_as_paid') : __('app.mark_all_as_paid') }}
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($month['payments'] as $payment)
                            @php
                                $debt = $this->debts->get($payment['name']);
                                $debtId = $debt ? $debt->id : 0;
                                $paymentKey = $month['month'] . '_' . $debtId;
                                $isPaid = $debt ? $this->paymentService->paymentExists($debtId, $month['month']) : false;
                            @endphp
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $payment['name'] }}
                                            @if ($payment['remaining'] <= 0.01)
                                                <span class="ml-2 text-xs text-green-600 dark:text-green-400 font-semibold">{{ __('app.paid_off') }}</span>
                                            @elseif ($payment['isPriority'])
                                                <span class="ml-2 text-xs text-blue-600 dark:text-blue-400 font-semibold">{{ __('app.now_priority') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($payment['amount'] > 0 && $debt && !$isHistorical)
                                        <input
                                            type="checkbox"
                                            wire:click="togglePayment({{ $month['month'] }}, {{ $debtId }})"
                                            @if($isPaid) checked @endif
                                            class="h-4 w-4 mt-0.5 text-blue-600 dark:text-blue-500 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:focus:ring-blue-400 cursor-pointer"
                                        >
                                    @elseif ($isHistorical && $isPaid)
                                        <svg class="h-4 w-4 mt-0.5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('app.payment') }}:</span>
                                    @if ($isPaid && $debt)
                                        @php
                                            $paymentRecord = $this->paymentService->getPayment($debtId, $month['month']);
                                            $actualAmount = $paymentRecord ? $paymentRecord->actual_amount : $payment['amount'];
                                            $isReconciliation = $paymentRecord && $paymentRecord->is_reconciliation_adjustment;
                                            $key = $month['month'] . '_' . $debtId;
                                            if (!isset($this->editingPayments[$key])) {
                                                $this->editingPayments[$key] = $actualAmount;
                                            }
                                        @endphp
                                        <div class="flex flex-col items-end gap-1">
                                            @if ($isReconciliation)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 border border-yellow-300 dark:border-yellow-700" title="{{ __('app.reconciliation_adjustment') }}">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    {{ __('app.reconciliation') }}
                                                </span>
                                            @endif
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="number"
                                                    wire:model.live.debounce.500ms="editingPayments.{{ $key }}"
                                                    wire:blur="updatePaymentAmount({{ $month['month'] }}, {{ $debtId }})"
                                                    class="w-24 px-2 py-1 text-sm text-right {{ $isReconciliation ? 'bg-yellow-50 dark:bg-yellow-900/10 border-yellow-300 dark:border-yellow-700' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' }} border rounded focus:ring-blue-500 dark:focus:ring-blue-400 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                >
                                                <span class="text-xs">kr</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="{{ $payment['isPriority'] ? 'font-semibold text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}">
                                            {{ number_format($payment['amount'], 0, ',', ' ') }} kr
                                        </span>
                                    @endif
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('app.remaining_balance') }}:</span>
                                    <span class="font-medium {{ $payment['remaining'] <= 0.01 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                                        {{ number_format(max(0, $payment['remaining']), 0, ',', ' ') }} kr
                                    </span>
                                </div>
                                @if ($isPaid && $debt)
                                    @php
                                        $paymentRecord = $this->paymentService->getPayment($debtId, $month['month']);
                                        $hasNote = $paymentRecord && $paymentRecord->notes;
                                        $key = $month['month'] . '_' . $debtId;
                                    @endphp
                                    <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                                        @if ($hasNote || (isset($this->showNoteInput[$key]) && $this->showNoteInput[$key]))
                                            @if (isset($this->showNoteInput[$key]) && $this->showNoteInput[$key])
                                                <div class="space-y-2">
                                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                                        {{ __('app.payment_notes') }}
                                                    </label>
                                                    <textarea
                                                        wire:model.live="editingNotes.{{ $key }}"
                                                        rows="2"
                                                        placeholder="{{ __('app.note_placeholder') }}"
                                                        class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 placeholder:text-gray-500 dark:placeholder:text-gray-400"
                                                    ></textarea>
                                                    <div class="flex gap-2">
                                                        <button
                                                            type="button"
                                                            wire:click="saveNote({{ $month['month'] }}, {{ $debtId }})"
                                                            class="flex-1 px-3 py-1.5 text-xs bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded transition-colors cursor-pointer"
                                                        >
                                                            {{ __('app.save_note') }}
                                                        </button>
                                                        @if ($hasNote)
                                                            <button
                                                                type="button"
                                                                wire:click="deleteNote({{ $month['month'] }}, {{ $debtId }})"
                                                                class="px-3 py-1.5 text-xs bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded transition-colors cursor-pointer"
                                                            >
                                                                {{ __('app.delete_note') }}
                                                            </button>
                                                        @endif
                                                        <button
                                                            type="button"
                                                            wire:click="toggleNoteInput({{ $month['month'] }}, {{ $debtId }})"
                                                            class="px-3 py-1.5 text-xs bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded transition-colors cursor-pointer"
                                                        >
                                                            {{ __('app.cancel') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="toggleNoteInput({{ $month['month'] }}, {{ $debtId }})"
                                                    class="flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                    {{ __('app.view_note') }}
                                                </button>
                                            @endif
                                        @else
                                            <button
                                                type="button"
                                                wire:click="toggleNoteInput({{ $month['month'] }}, {{ $debtId }})"
                                                class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                {{ __('app.add_note') }}
                                            </button>
                                        @endif
                                    </div>
                                @endif
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
                    class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-colors disabled:opacity-50 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
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
                    class="inline-flex items-center justify-center px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors disabled:opacity-50 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
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

    {{-- Reconciliation Modals --}}
    @foreach ($this->debts as $debt)
        <div
            x-data="{ show: $wire.entangle('reconciliationModals.{{ $debt->id }}') }"
            x-show="show"
            x-cloak
            @keydown.escape.window="show = false"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
            style="display: none;"
        >
            <!-- Background overlay -->
            <div
                class="fixed inset-0 z-40 bg-black/50 transition-opacity"
                aria-hidden="true"
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="show = false"
            ></div>

            <div class="relative z-50 flex items-center justify-center min-h-screen p-4">
                <div
                    class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all"
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    @click.away="show = false"
                >
                        <form wire:submit.prevent="reconcileDebt({{ $debt->id }})">
                            <!-- Header -->
                            <div class="bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white" id="modal-title">
                                        Avstem gjeld
                                    </h3>
                                    <button
                                        type="button"
                                        @click="show = false"
                                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 rounded-lg p-1 transition-colors"
                                    >
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $debt->name }}</p>
                            </div>

                            <!-- Content -->
                            <div class="bg-white dark:bg-gray-800 px-6 py-5">
                                <div class="space-y-5">
                                            <!-- Current Calculated Balance -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Beregnet saldo
                                                </label>
                                                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                                    {{ number_format($debt->balance, 2, ',', ' ') }} kr
                                                </div>
                                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    Basert på opprinnelig saldo og innbetalinger
                                                </p>
                                            </div>

                                            <!-- Actual Balance Input -->
                                            <div>
                                                <label for="actualBalance-{{ $debt->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Faktisk saldo (fra kontoutskrift)
                                                    <span class="text-red-500">*</span>
                                                </label>
                                                <div class="relative">
                                                    <input
                                                        type="number"
                                                        id="actualBalance-{{ $debt->id }}"
                                                        wire:model.live="reconciliationBalances.{{ $debt->id }}"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="Skriv inn faktisk saldo"
                                                        class="w-full px-4 py-3 pr-14 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-colors duration-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none @error('reconciliationBalances.' . $debt->id) border-red-500 dark:border-red-400 @enderror"
                                                    >
                                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                                        <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">NOK</span>
                                                    </div>
                                                </div>
                                                @error('reconciliationBalances.' . $debt->id)
                                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <!-- Difference Display -->
                                            @if (isset($this->reconciliationBalances[$debt->id]) && $this->reconciliationBalances[$debt->id] !== '')
                                                @php
                                                    $difference = $this->getReconciliationDifference($debt->id);
                                                @endphp
                                                <div class="p-4 rounded-lg {{ $difference < 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : ($difference > 0 ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' : 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800') }}">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-sm font-medium {{ $difference < 0 ? 'text-green-900 dark:text-green-200' : ($difference > 0 ? 'text-yellow-900 dark:text-yellow-200' : 'text-blue-900 dark:text-blue-200') }}">
                                                            Differanse:
                                                        </span>
                                                        <span class="text-lg font-bold {{ $difference < 0 ? 'text-green-900 dark:text-green-200' : ($difference > 0 ? 'text-yellow-900 dark:text-yellow-200' : 'text-blue-900 dark:text-blue-200') }}">
                                                            {{ $difference >= 0 ? '+' : '' }}{{ number_format($difference, 2, ',', ' ') }} kr
                                                        </span>
                                                    </div>
                                                    <p class="mt-1 text-xs {{ $difference < 0 ? 'text-green-700 dark:text-green-300' : ($difference > 0 ? 'text-yellow-700 dark:text-yellow-300' : 'text-blue-700 dark:text-blue-300') }}">
                                                        @if (abs($difference) < 0.01)
                                                            Saldo er korrekt - ingen justering nødvendig
                                                        @elseif ($difference < 0)
                                                            Du har betalt {{ number_format(abs($difference), 2, ',', ' ') }} kr mer enn registrert
                                                        @else
                                                            Saldo er {{ number_format($difference, 2, ',', ' ') }} kr høyere enn beregnet
                                                        @endif
                                                    </p>
                                                </div>
                                            @endif

                                            <!-- Reconciliation Date -->
                                            <div>
                                                <label for="reconciliationDate-{{ $debt->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Avstemmingsdato
                                                    <span class="text-red-500">*</span>
                                                </label>
                                                <input
                                                    type="date"
                                                    id="reconciliationDate-{{ $debt->id }}"
                                                    wire:model="reconciliationDates.{{ $debt->id }}"
                                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-colors duration-200 @error('reconciliationDates.' . $debt->id) border-red-500 dark:border-red-400 @enderror"
                                                >
                                                @error('reconciliationDates.' . $debt->id)
                                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <!-- Notes -->
                                            <div>
                                                <label for="notes-{{ $debt->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Notater (valgfritt)
                                                </label>
                                                <textarea
                                                    id="notes-{{ $debt->id }}"
                                                    wire:model="reconciliationNotes.{{ $debt->id }}"
                                                    rows="3"
                                                    placeholder="F.eks. 'Gebyr på 300 kr ikke registrert' eller 'Rentejustering fra bank'"
                                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-colors duration-200 @error('reconciliationNotes.' . $debt->id) border-red-500 dark:border-red-400 @enderror"
                                                ></textarea>
                                                @error('reconciliationNotes.' . $debt->id)
                                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                            <!-- Footer -->
                            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="show = false"
                                    class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors"
                                >
                                    Avbryt
                                </button>
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    <span wire:loading.remove>Avstem</span>
                                    <span wire:loading class="inline-flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Avstemmer...
                                    </span>
                                </button>
                            </div>
                                        </form>
                                    </div>
                                </div>
                        </div>
                    @endforeach

</div>
