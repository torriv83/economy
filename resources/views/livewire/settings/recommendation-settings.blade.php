<div>
    <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50">
        {{-- Header --}}
        <div class="p-6 border-b border-slate-200/50 dark:border-slate-700/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-display font-semibold text-slate-900 dark:text-white">
                        {{ __('app.recommendation_settings') }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('app.recommendation_settings_description') }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- Info Box --}}
            <div
                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200/50 dark:border-blue-800/50 rounded-xl p-4">
                <div class="flex gap-3">
                    <div class="shrink-0">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                            <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200">
                            {{ __('app.recommendation_thresholds_info_title') }}</h3>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            {{ __('app.recommendation_thresholds_info_description') }}</p>
                    </div>
                </div>
            </div>

            {{-- High Interest Threshold --}}
            <div class="space-y-2">
                <label for="highInterestThreshold" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ __('app.high_interest_threshold') }}
                </label>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.high_interest_threshold_help') }}</p>
                <div class="mt-2 relative">
                    <input type="number" id="highInterestThreshold"
                        wire:model.live.debounce.500ms="highInterestThreshold" min="0" max="100" step="0.1"
                        class="w-full px-4 py-3 pr-12 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-slate-400 dark:text-slate-500 text-sm font-medium">%</span>
                    </div>
                </div>
                @error('highInterestThreshold')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Low Interest Threshold --}}
            <div class="space-y-2">
                <label for="lowInterestThreshold" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ __('app.low_interest_threshold') }}
                </label>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.low_interest_threshold_help') }}</p>
                <div class="mt-2 relative">
                    <input type="number" id="lowInterestThreshold" wire:model.live.debounce.500ms="lowInterestThreshold"
                        min="0" max="100" step="0.1"
                        class="w-full px-4 py-3 pr-12 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-slate-400 dark:text-slate-500 text-sm font-medium">%</span>
                    </div>
                </div>
                @error('lowInterestThreshold')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Buffer Target Months --}}
            <div class="space-y-2">
                <label for="bufferTargetMonths" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ __('app.buffer_target_months') }}
                </label>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.buffer_target_months_help') }}</p>
                <div class="mt-2 relative">
                    <input type="number" id="bufferTargetMonths" wire:model.live.debounce.500ms="bufferTargetMonths"
                        min="1" max="12" step="1"
                        class="w-full px-4 py-3 pr-20 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span
                            class="text-slate-400 dark:text-slate-500 text-sm font-medium">{{ __('app.months_short') }}</span>
                    </div>
                </div>
                @error('bufferTargetMonths')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Minimum Interest Savings --}}
            <div class="space-y-2">
                <label for="minInterestSavings" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ __('app.min_interest_savings') }}
                </label>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.min_interest_savings_help') }}</p>
                <div class="mt-2 relative">
                    <input type="number" id="minInterestSavings" wire:model.live.debounce.500ms="minInterestSavings"
                        min="0" step="100"
                        class="w-full px-4 py-3 pr-14 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-slate-400 dark:text-slate-500 text-sm font-medium">NOK</span>
                    </div>
                </div>
                @error('minInterestSavings')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Reset to Defaults Button --}}
            <div class="pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                <button type="button" wire:click="resetToDefaults" wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-all cursor-pointer">
                    <svg wire:loading.remove wire:target="resetToDefaults" class="w-4 h-4 mr-2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <svg wire:loading wire:target="resetToDefaults" class="animate-spin w-4 h-4 mr-2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    {{ __('app.reset_to_defaults') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Success Flash Message --}}
    <div x-data="{ show: false }"
        x-on:recommendation-settings-saved.window="show = true; setTimeout(() => show = false, 3000)"
        x-on:recommendation-settings-reset.window="show = true; setTimeout(() => show = false, 3000)" x-show="show"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-4 right-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-xl shadow-lg"
        style="display: none;">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ __('app.settings_saved') }}</span>
        </div>
    </div>
</div>