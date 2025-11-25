<div class="space-y-8">
    {{-- Settings Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">{{ __('app.payoff_settings') }}</h2>

        <div class="space-y-6">
            {{-- Strategy Selection --}}
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 block">
                    {{ __('app.selected_strategy') }}
                </label>
                <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-1">
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
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    @if ($this->strategy === 'avalanche')
                        {{ __('app.avalanche_description') }}
                    @elseif ($this->strategy === 'snowball')
                        {{ __('app.snowball_description') }}
                    @else
                        {{ __('app.custom_order_description') }}
                    @endif
                </p>
            </div>

            {{-- Extra Payment Input --}}
            <div>
                <label for="extraPayment" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 block">
                    {{ __('app.extra_monthly_payment') }}
                </label>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        wire:click="$set('extraPayment', {{ max(0, $this->extraPayment - 500) }})"
                        class="h-12 w-12 flex items-center justify-center bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
                        aria-label="Decrease by 500"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </button>
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="number"
                            id="extraPayment"
                            wire:model.live.debounce.300ms="extraPayment"
                            min="0"
                            max="1000000"
                            step="100"
                            class="w-full px-4 py-3 pr-12 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white font-bold text-center text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                        >
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium pointer-events-none">
                            kr
                        </span>
                    </div>
                    <button
                        type="button"
                        wire:click="$set('extraPayment', {{ $this->extraPayment + 500 }})"
                        class="h-12 w-12 flex items-center justify-center bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
                        aria-label="Increase by 500"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </button>
                </div>
                @error('extraPayment')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('app.enter_extra_payment_description') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Impact Preview --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-6">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">{{ __('app.settings_impact') }}</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Months to Debt-Free --}}
            <div class="bg-white/80 dark:bg-gray-800/80 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.months_to_debt_free') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->totalMonths }}</p>
                    </div>
                </div>
            </div>

            {{-- Payoff Date --}}
            <div class="bg-white/80 dark:bg-gray-800/80 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.payoff_date') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->payoffDate }}</p>
                    </div>
                </div>
            </div>

            {{-- Total Interest --}}
            <div class="bg-white/80 dark:bg-gray-800/80 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.total_interest') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->totalInterest, 0, ',', ' ') }} kr</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
