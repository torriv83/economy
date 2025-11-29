<div>
    {{-- Debt Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $debt->name }}</h1>
                    @if ($debt->type)
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $this->typeBadgeColor }}">
                            {{ __('app.' . $debt->type) }}
                        </span>
                    @endif
                </div>
                @if ($debt->ynab_account_id)
                    <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ __('app.synced_with_ynab') }}
                    </p>
                @endif
            </div>
            <button
                wire:click="$parent.editFromDetail"
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors cursor-pointer">
                {{ __('app.edit') }}
            </button>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {{-- Current Balance --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('app.current_balance') }}</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">
                {{ number_format($debt->balance, 0, ',', ' ') }} kr
            </p>
        </div>

        {{-- Interest Rate --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('app.interest_rate') }}</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">
                {{ number_format($debt->interest_rate, 2, ',', ' ') }}%
            </p>
        </div>

        {{-- Minimum Payment --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('app.minimum_payment') }}</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">
                {{ $debt->minimum_payment ? number_format($debt->minimum_payment, 0, ',', ' ') . ' kr' : '-' }}
            </p>
        </div>

        {{-- Total Paid --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('app.total_paid') }}</p>
            <p class="text-xl font-bold text-green-600 dark:text-green-400">
                {{ number_format($this->totalPaid, 0, ',', ' ') }} kr
            </p>
        </div>
    </div>

    {{-- What-If Calculator --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ __('app.what_if_calculator') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('app.what_if_description') }}</p>

        <div class="flex flex-col sm:flex-row sm:items-end gap-4 mb-6">
            {{-- Payment Type Toggle Switch --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('app.payment_type') }}
                </label>
                <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 p-1 bg-gray-100 dark:bg-gray-700">
                    <button
                        type="button"
                        wire:click="$set('whatIfType', 'monthly')"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-colors cursor-pointer {{ $whatIfType === 'monthly' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                    >
                        {{ __('app.monthly_extra') }}
                    </button>
                    <button
                        type="button"
                        wire:click="$set('whatIfType', 'one_time')"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-colors cursor-pointer {{ $whatIfType === 'one_time' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                    >
                        {{ __('app.one_time_payment') }}
                    </button>
                </div>
            </div>

            {{-- Amount Input --}}
            <div class="flex-1">
                <label for="whatIfAmount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $whatIfType === 'monthly' ? __('app.extra_monthly_amount') : __('app.one_time_amount') }}
                </label>
                <div class="relative">
                    <input
                        type="number"
                        id="whatIfAmount"
                        wire:model.live.debounce.500ms="whatIfAmount"
                        min="0"
                        step="100"
                        placeholder="0"
                        class="w-full px-4 py-2 pr-12 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">kr</span>
                </div>
            </div>
        </div>

        @if ($whatIfResult)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Time Saved --}}
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-green-600 dark:text-green-400">{{ __('app.time_saved') }}</p>
                            <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                                {{ $whatIfResult['months_saved'] }} {{ __('app.months_sooner') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Interest Saved --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-blue-600 dark:text-blue-400">{{ __('app.interest_savings') }}</p>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                                {{ number_format($whatIfResult['interest_saved'], 0, ',', ' ') }} kr
                            </p>
                        </div>
                    </div>
                </div>

                {{-- New Payoff Date --}}
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-purple-600 dark:text-purple-400">{{ __('app.new_payoff_date') }}</p>
                            <p class="text-lg font-bold text-purple-700 dark:text-purple-300">
                                {{ \Carbon\Carbon::parse($whatIfResult['new_payoff_date'])->locale(app()->getLocale())->translatedFormat('F Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                {{ __('app.enter_amount_to_calculate') }}
            </p>
        @endif
    </div>

    {{-- Acceleration Opportunities --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('app.acceleration_opportunities') }}</h2>
        <livewire:ynab.acceleration-opportunities :debt="$debt" />
    </div>

    {{-- Recent Payments --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('app.recent_payments') }}</h2>

        @if ($this->recentPayments->isEmpty())
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('app.no_payments_yet') }}</p>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($this->recentPayments as $payment)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($payment->payment_date)->locale(app()->getLocale())->translatedFormat('j. F Y') }}
                            </p>
                            @if ($payment->notes)
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $payment->notes }}</p>
                            @endif
                        </div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ number_format($payment->actual_amount, 0, ',', ' ') }} kr
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
