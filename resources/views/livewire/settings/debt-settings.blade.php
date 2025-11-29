<div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('app.debt_settings') }}</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('app.debt_settings_description') }}</p>
        </div>

        <div class="p-4 sm:p-6 space-y-6">
            {{-- Info Box about Norwegian Regulations --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-blue-400 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ __('app.utlansforskriften_title') }}</h3>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">{{ __('app.utlansforskriften_description') }}</p>
                    </div>
                </div>
            </div>

            {{-- Credit Card Minimum Percentage --}}
            <div>
                <label for="kredittkortPercentage" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('app.kredittkort_min_percentage') }}
                </label>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('app.kredittkort_min_percentage_help') }}</p>
                <div class="mt-2 relative">
                    <input
                        type="number"
                        id="kredittkortPercentage"
                        wire:model.live.debounce.500ms="kredittkortPercentage"
                        min="1"
                        max="100"
                        step="0.1"
                        class="w-full px-4 py-3 pr-12 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">%</span>
                    </div>
                </div>
                @error('kredittkortPercentage')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Credit Card Minimum Amount --}}
            <div>
                <label for="kredittkortMinimum" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('app.kredittkort_min_amount') }}
                </label>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('app.kredittkort_min_amount_help') }}</p>
                <div class="mt-2 relative">
                    <input
                        type="number"
                        id="kredittkortMinimum"
                        wire:model.live.debounce.500ms="kredittkortMinimum"
                        min="0"
                        step="1"
                        class="w-full px-4 py-3 pr-14 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">NOK</span>
                    </div>
                </div>
                @error('kredittkortMinimum')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Consumer Loan Payoff Months --}}
            <div>
                <label for="forbrukslånPayoffMonths" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('app.forbrukslan_payoff_months') }}
                </label>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('app.forbrukslan_payoff_months_help') }}</p>
                <div class="mt-2 relative">
                    <input
                        type="number"
                        id="forbrukslånPayoffMonths"
                        wire:model.live.debounce.500ms="forbrukslånPayoffMonths"
                        min="1"
                        max="120"
                        step="1"
                        class="w-full px-4 py-3 pr-20 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">{{ __('app.months_short') }}</span>
                    </div>
                </div>
                @error('forbrukslånPayoffMonths')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Reset to Defaults Button --}}
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                    type="button"
                    wire:click="resetToDefaults"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors cursor-pointer"
                >
                    <svg wire:loading.remove wire:target="resetToDefaults" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <svg wire:loading wire:target="resetToDefaults" class="animate-spin w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('app.reset_to_defaults') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Success Flash Message --}}
    <div
        x-data="{ show: false }"
        x-on:debt-settings-saved.window="show = true; setTimeout(() => show = false, 3000)"
        x-on:debt-settings-reset.window="show = true; setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-4 right-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg shadow-lg"
        style="display: none;"
    >
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm font-medium">{{ __('app.settings_saved') }}</span>
        </div>
    </div>
</div>
