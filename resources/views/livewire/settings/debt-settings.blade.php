<div>
    <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50">
        {{-- Header --}}
        <div class="p-6 border-b border-slate-200/50 dark:border-slate-700/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-display font-semibold text-slate-900 dark:text-white">{{ __('app.debt_settings') }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.debt_settings_description') }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- Info Box about Norwegian Regulations --}}
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200/50 dark:border-amber-800/50 rounded-xl p-4">
                <div class="flex gap-3">
                    <div class="shrink-0">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                            <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">{{ __('app.utlansforskriften_title') }}</h3>
                        <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">{{ __('app.utlansforskriften_description') }}</p>
                    </div>
                </div>
            </div>

            {{-- Credit Card Minimum Percentage --}}
            <div class="space-y-2">
                <label for="kredittkortPercentage" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ __('app.kredittkort_min_percentage') }}
                </label>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.kredittkort_min_percentage_help') }}</p>
                <div class="mt-2 relative">
                    <input
                        type="number"
                        id="kredittkortPercentage"
                        wire:model.live.debounce.500ms="kredittkortPercentage"
                        min="1"
                        max="100"
                        step="0.1"
                        class="w-full px-4 py-3 pr-12 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-slate-400 dark:text-slate-500 text-sm font-medium">%</span>
                    </div>
                </div>
                @error('kredittkortPercentage')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Credit Card Minimum Amount --}}
            <div class="space-y-2">
                <label for="kredittkortMinimum" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ __('app.kredittkort_min_amount') }}
                </label>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.kredittkort_min_amount_help') }}</p>
                <div class="mt-2 relative">
                    <input
                        type="number"
                        id="kredittkortMinimum"
                        wire:model.live.debounce.500ms="kredittkortMinimum"
                        min="0"
                        step="1"
                        class="w-full px-4 py-3 pr-14 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-slate-400 dark:text-slate-500 text-sm font-medium">NOK</span>
                    </div>
                </div>
                @error('kredittkortMinimum')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Consumer Loan Payoff Months --}}
            <div class="space-y-2">
                <label for="forbrukslånPayoffMonths" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ __('app.forbrukslan_payoff_months') }}
                </label>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.forbrukslan_payoff_months_help') }}</p>
                <div class="mt-2 relative">
                    <input
                        type="number"
                        id="forbrukslånPayoffMonths"
                        wire:model.live.debounce.500ms="forbrukslånPayoffMonths"
                        min="1"
                        max="120"
                        step="1"
                        class="w-full px-4 py-3 pr-20 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-slate-400 dark:text-slate-500 text-sm font-medium">{{ __('app.months_short') }}</span>
                    </div>
                </div>
                @error('forbrukslånPayoffMonths')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Reset to Defaults Button --}}
            <div class="pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                <button
                    type="button"
                    wire:click="resetToDefaults"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-all cursor-pointer"
                >
                    <svg wire:loading.remove wire:target="resetToDefaults" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
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
        class="fixed bottom-4 right-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-xl shadow-lg"
        style="display: none;"
    >
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ __('app.settings_saved') }}</span>
        </div>
    </div>
</div>
