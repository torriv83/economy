<div class="animate-fade-in-up">
    {{-- Debt Header --}}
    <div class="premium-card rounded-2xl p-6 mb-6">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="font-display text-2xl font-bold text-slate-900 dark:text-white">{{ $debt->name }}</h1>
                    @if ($debt->type)
                        <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $this->typeBadgeColor }}">
                            {{ __('app.' . $debt->type) }}
                        </span>
                    @endif
                </div>
                @if ($debt->ynab_account_id)
                    <p class="text-sm text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ __('app.synced_with_ynab') }}
                    </p>
                @endif
            </div>
            <button
                wire:click="$parent.editFromDetail"
                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-all duration-200 cursor-pointer">
                {{ __('app.edit') }}
            </button>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 stagger-children">
        {{-- Current Balance --}}
        <div class="premium-card rounded-2xl p-5 stat-card">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('app.current_balance') }}</p>
            <p class="font-display text-xl font-bold text-slate-900 dark:text-white">
                {{ number_format($debt->balance, 0, ',', ' ') }} kr
            </p>
        </div>

        {{-- Interest Rate --}}
        <div class="premium-card rounded-2xl p-5 stat-card">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('app.interest_rate') }}</p>
            <p class="font-display text-xl font-bold text-slate-900 dark:text-white">
                {{ number_format($debt->interest_rate, 2, ',', ' ') }}%
            </p>
        </div>

        {{-- Minimum Payment --}}
        <div class="premium-card rounded-2xl p-5 stat-card">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('app.minimum_payment') }}</p>
            <p class="font-display text-xl font-bold text-slate-900 dark:text-white">
                {{ $debt->minimum_payment ? number_format($debt->minimum_payment, 0, ',', ' ') . ' kr' : '-' }}
            </p>
        </div>

        {{-- Total Paid --}}
        <div class="premium-card rounded-2xl p-5 stat-card">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">{{ __('app.total_paid') }}</p>
            <p class="font-display text-xl font-bold gradient-text">
                {{ number_format($this->totalPaid, 0, ',', ' ') }} kr
            </p>
        </div>
    </div>

    {{-- What-If Calculator --}}
    <div class="premium-card rounded-2xl p-6 mb-6">
        <h2 class="font-display text-lg font-bold text-slate-900 dark:text-white mb-2">{{ __('app.what_if_calculator') }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">{{ __('app.what_if_description') }}</p>

        <div class="flex flex-col sm:flex-row sm:items-end gap-4 mb-6">
            {{-- Payment Type Toggle Switch --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    {{ __('app.payment_type') }}
                </label>
                <div class="inline-flex rounded-xl border border-slate-200 dark:border-slate-700 p-1 bg-slate-100 dark:bg-slate-800">
                    <button
                        type="button"
                        wire:click="$set('whatIfType', 'monthly')"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 cursor-pointer {{ $whatIfType === 'monthly' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                    >
                        {{ __('app.monthly_extra') }}
                    </button>
                    <button
                        type="button"
                        wire:click="$set('whatIfType', 'one_time')"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 cursor-pointer {{ $whatIfType === 'one_time' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                    >
                        {{ __('app.one_time_payment') }}
                    </button>
                </div>
            </div>

            {{-- Amount Input --}}
            <div class="flex-1">
                <label for="whatIfAmount" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
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
                        class="w-full px-4 py-3 pr-14 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                    >
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 dark:text-slate-400 text-sm font-medium">kr</span>
                </div>
            </div>
        </div>

        @if ($whatIfResult)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 stagger-children">
                {{-- Time Saved --}}
                <div class="rounded-xl p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ __('app.time_saved') }}</p>
                            <p class="font-display text-xl font-bold text-emerald-800 dark:text-emerald-300">
                                {{ $whatIfResult['months_saved'] }} {{ __('app.months_sooner') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Interest Saved --}}
                <div class="rounded-xl p-4 bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800/50">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-cyan-100 dark:bg-cyan-900/40 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-cyan-700 dark:text-cyan-400">{{ __('app.interest_savings') }}</p>
                            <p class="font-display text-xl font-bold text-cyan-800 dark:text-cyan-300">
                                {{ number_format($whatIfResult['interest_saved'], 0, ',', ' ') }} kr
                            </p>
                        </div>
                    </div>
                </div>

                {{-- New Payoff Date --}}
                <div class="rounded-xl p-4 bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800/50">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-violet-100 dark:bg-violet-900/40 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-violet-700 dark:text-violet-400">{{ __('app.new_payoff_date') }}</p>
                            <p class="font-display text-lg font-bold text-violet-800 dark:text-violet-300">
                                {{ \Carbon\Carbon::parse($whatIfResult['new_payoff_date'])->locale(app()->getLocale())->translatedFormat('F Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <p class="text-sm text-slate-500 dark:text-slate-400 italic">
                {{ __('app.enter_amount_to_calculate') }}
            </p>
        @endif
    </div>

    {{-- Acceleration Opportunities --}}
    @if (app(\App\Services\SettingsService::class)->isYnabEnabled())
        <div class="premium-card rounded-2xl p-6 mb-6">
            <h2 class="font-display text-lg font-bold text-slate-900 dark:text-white mb-4">{{ __('app.acceleration_opportunities') }}</h2>
            <livewire:ynab.acceleration-opportunities :debt="$debt" />
        </div>
    @endif

    {{-- Recent Payments --}}
    <div class="premium-card rounded-2xl p-6">
        <h2 class="font-display text-lg font-bold text-slate-900 dark:text-white mb-4">{{ __('app.recent_payments') }}</h2>

        @if ($this->recentPayments->isEmpty())
            <div class="text-center py-8">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 mb-3">
                    <svg class="h-6 w-6 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
                <p class="text-slate-500 dark:text-slate-400">{{ __('app.no_payments_yet') }}</p>
            </div>
        @else
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach ($this->recentPayments as $payment)
                    <div wire:key="payment-{{ $payment->id }}" class="py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($payment->payment_date)->locale(app()->getLocale())->translatedFormat('j. F Y') }}
                            </p>
                            @if ($payment->notes)
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $payment->notes }}</p>
                            @endif
                        </div>
                        <p class="font-display text-sm font-bold text-slate-900 dark:text-white">
                            {{ number_format($payment->actual_amount, 0, ',', ' ') }} kr
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
