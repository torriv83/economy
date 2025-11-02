<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header Section --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                {{ __('app.payoff_strategies') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('app.strategies_description') }}
            </p>
        </div>

        {{-- Extra Payment Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Recommended Payment (from YNAB data - mock) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('app.recommended_extra_payment') }}
                    </label>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-2xl font-bold text-blue-900 dark:text-blue-100">3 500 kr</span>
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-blue-600 dark:bg-blue-700 text-white">
                                {{ __('app.from_ynab') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('app.recommended_payment_description') }}
                        </p>
                    </div>
                </div>

                {{-- Manual Override Input --}}
                <div>
                    <label for="extraPayment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('app.extra_monthly_payment') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="$set('extraPayment', {{ max(0, $this->extraPayment - 500) }})"
                            class="h-12 w-12 flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                            aria-label="Decrease by 500"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </button>
                        <div class="relative flex-1">
                            <input
                                type="number"
                                id="extraPayment"
                                wire:model.live.debounce.300ms="extraPayment"
                                min="0"
                                max="1000000"
                                step="100"
                                class="w-full px-4 py-3 pr-14 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">kr</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="$set('extraPayment', {{ $this->extraPayment + 500 }})"
                            class="h-12 w-12 flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                            aria-label="Increase by 500"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>
                    @error('extraPayment')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('app.override_recommendation') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Strategy Comparison Columns --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Snowball Method Column --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Header --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800/30 px-6 py-4">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300">
                            {{ __('app.snowball_method') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('app.snowball_description') }}
                    </p>
                </div>

                {{-- Payment Order --}}
                <div class="px-6 py-5">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                        {{ __('app.payment_order') }}
                    </h3>
                    <div class="space-y-3">
                        @foreach ($this->orderedDebts['snowball'] as $index => $debt)
                            <div wire:key="snowball-{{ $index }}" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 text-xs font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $debt['name'] }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ number_format($debt['balance'], 0, ',', ' ') }} kr</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($debt['interestRate'], 1, ',', ' ') }}%</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Summary --}}
                <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 px-6 py-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.time_to_debt_free') }}</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $this->snowballData['months'] }} {{ __('app.months_short') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.total_interest') }}</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($this->snowballData['totalInterest'], 0, ',', ' ') }} kr</span>
                    </div>
                </div>
            </div>

            {{-- Avalanche Method Column --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 border-green-200 dark:border-green-800 overflow-hidden">
                {{-- Header --}}
                <div class="bg-green-50 dark:bg-green-900/20 border-b border-green-100 dark:border-green-800/30 px-6 py-4">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300">
                            {{ __('app.avalanche_method') }}
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-green-600 dark:bg-green-700 text-white">
                            {{ __('app.recommended') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('app.avalanche_description') }}
                    </p>
                </div>

                {{-- Payment Order --}}
                <div class="px-6 py-5">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                        {{ __('app.payment_order') }}
                    </h3>
                    <div class="space-y-3">
                        @foreach ($this->orderedDebts['avalanche'] as $index => $debt)
                            <div wire:key="avalanche-{{ $index }}" class="flex items-center justify-between p-3 rounded-lg bg-green-50 dark:bg-green-900/20">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 text-xs font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $debt['name'] }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ number_format($debt['balance'], 0, ',', ' ') }} kr</div>
                                    <div class="text-sm text-green-700 dark:text-green-400 font-medium">{{ number_format($debt['interestRate'], 1, ',', ' ') }}%</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Summary --}}
                <div class="border-t border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-6 py-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.time_to_debt_free') }}</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $this->avalancheData['months'] }} {{ __('app.months_short') }}</span>
                    </div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.total_interest') }}</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($this->avalancheData['totalInterest'], 0, ',', ' ') }} kr</span>
                    </div>
                    <div class="pt-3 border-t border-green-200 dark:border-green-800">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-green-700 dark:text-green-400">{{ __('app.money_saved') }}</span>
                            <span class="text-lg font-bold text-green-700 dark:text-green-400">{{ number_format($this->avalancheData['savings'], 0, ',', ' ') }} kr {{ __('app.vs_snowball') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
