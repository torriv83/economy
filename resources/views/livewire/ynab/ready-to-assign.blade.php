<div>
    @if (!$ynabEnabled)
        {{-- YNAB disabled - show nothing --}}
    @elseif (!$isConfigured)
        {{-- YNAB not configured - show nothing --}}
    @elseif ($isLoading)
        {{-- Loading state --}}
        <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg px-4 py-3 border border-gray-200 dark:border-gray-700">
            <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-lg animate-pulse"></div>
            <div class="flex-1">
                <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-1"></div>
                <div class="h-5 w-16 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
            </div>
        </div>
    @elseif ($hasError)
        {{-- Error state --}}
        <div class="flex items-center gap-3 bg-red-50 dark:bg-red-900/20 rounded-lg px-4 py-3 border border-red-200 dark:border-red-800">
            <div class="h-8 w-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center shrink-0">
                <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-red-600 dark:text-red-400">{{ __('app.ynab_connection_error') }}</p>
            </div>
            <button
                wire:click="refresh"
                wire:loading.attr="disabled"
                class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 p-1 rounded transition-colors cursor-pointer"
                title="{{ __('app.refresh') }}"
            >
                <svg class="h-4 w-4" wire:loading.class="animate-spin" wire:target="refresh" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    @else
        {{-- Success state - show the amount --}}
        <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg px-4 py-3 border border-emerald-200 dark:border-emerald-800">
            <div class="h-8 w-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center shrink-0">
                <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('app.ready_to_assign') }}</p>
                <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300 truncate">
                    {{ number_format($amount, 0, ',', ' ') }} kr
                </p>
            </div>
            <button
                wire:click="refresh"
                wire:loading.attr="disabled"
                class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300 p-1 rounded transition-colors cursor-pointer"
                title="{{ __('app.refresh') }}"
            >
                <svg class="h-4 w-4" wire:loading.class="animate-spin" wire:target="refresh" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    @endif
</div>
