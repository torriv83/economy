<div wire:init="loadOpportunities" x-data="{ expanded: false, initialCount: 5 }">
    @if (!$ynabEnabled)
        {{-- YNAB disabled - show nothing --}}
    @elseif (!$isConfigured)
        {{-- YNAB not configured - show prompt --}}
        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <p>{{ __('app.ynab_not_configured') }}</p>
        </div>
    @elseif ($isLoading)
        {{-- Loading state --}}
        <div class="space-y-3">
            @for ($i = 0; $i < 3; $i++)
                <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg animate-pulse">
                    <div class="h-10 w-10 bg-gray-200 dark:bg-gray-600 rounded-lg"></div>
                    <div class="flex-1">
                        <div class="h-4 w-32 bg-gray-200 dark:bg-gray-600 rounded mb-2"></div>
                        <div class="h-3 w-24 bg-gray-200 dark:bg-gray-600 rounded"></div>
                    </div>
                    <div class="h-6 w-20 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </div>
            @endfor
        </div>
    @elseif ($hasError)
        {{-- Error state --}}
        <div class="flex items-center gap-3 bg-red-50 dark:bg-red-900/20 rounded-lg px-4 py-4 border border-red-200 dark:border-red-800">
            <div class="h-10 w-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center shrink-0">
                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-red-600 dark:text-red-400">{{ __('app.ynab_connection_error') }}</p>
            </div>
            <button
                wire:click="refresh"
                wire:loading.attr="disabled"
                class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 p-2 rounded transition-colors cursor-pointer"
                title="{{ __('app.refresh') }}"
            >
                <svg class="h-5 w-5" wire:loading.class="animate-spin" wire:target="refresh" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    @elseif ($opportunities->isEmpty())
        {{-- No opportunities --}}
        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="font-medium">{{ __('app.no_opportunities') }}</p>
            <p class="text-sm mt-1">{{ __('app.no_opportunities_description') }}</p>
        </div>
    @else
        {{-- Opportunities list --}}
        <div class="space-y-3">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('app.acceleration_description') }}</p>

            @foreach ($opportunities as $index => $opportunity)
                <div
                    wire:key="opportunity-{{ $index }}-{{ $opportunity['name'] }}"
                    @if ($index >= 5)
                        x-show="expanded"
                        x-cloak
                    @endif
                    class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
                >
                    {{-- Icon based on source --}}
                    <div class="h-10 w-10 rounded-lg flex items-center justify-center shrink-0 {{ $this->getTierColor($opportunity['tier']) }}">
                        @if ($opportunity['source'] === 'ready_to_assign')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif ($opportunity['source'] === 'savings')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $opportunity['name'] }}
                            </h3>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $this->getTierColor($opportunity['tier']) }}">
                                {{ $this->getTierLabel($opportunity['tier']) }}
                            </span>
                        </div>

                        @if ($opportunity['group_name'])
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                {{ __('app.from_category', ['category' => $opportunity['group_name']]) }}
                            </p>
                        @endif

                        @if ($opportunity['warning'])
                            <p class="text-xs text-orange-600 dark:text-orange-400 flex items-center gap-1 mt-1">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ $opportunity['warning'] }}
                            </p>
                        @endif

                        {{-- Impact info --}}
                        @if ($opportunity['impact']['months_saved'] > 0 || $opportunity['impact']['interest_saved'] > 0)
                            <div class="flex items-center gap-4 mt-2 text-xs">
                                @if ($opportunity['impact']['months_saved'] > 0)
                                    <span class="text-green-600 dark:text-green-400">
                                        {{ $opportunity['impact']['months_saved'] }} {{ __('app.months_sooner') }}
                                    </span>
                                @elseif ($opportunity['impact']['weeks_saved'] > 0)
                                    <span class="text-green-600 dark:text-green-400">
                                        {{ $opportunity['impact']['weeks_saved'] }} {{ __('app.weeks_sooner') }}
                                    </span>
                                @endif
                                @if ($opportunity['impact']['interest_saved'] > 0)
                                    <span class="text-blue-600 dark:text-blue-400">
                                        {{ number_format($opportunity['impact']['interest_saved'], 0, ',', ' ') }} kr {{ __('app.interest_saved') }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Amount --}}
                    <div class="text-right shrink-0">
                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ number_format($opportunity['amount'], 0, ',', ' ') }} kr
                        </p>
                    </div>
                </div>
            @endforeach

            {{-- Show more/less button --}}
            @if ($opportunities->count() > 5)
                <button
                    x-on:click="expanded = !expanded"
                    class="w-full py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors cursor-pointer flex items-center justify-center gap-1"
                >
                    <span x-text="expanded ? '{{ __('app.show_less') }}' : '{{ __('app.show_more_count', ['count' => $opportunities->count() - 5]) }}'"></span>
                    <svg
                        class="h-4 w-4 transition-transform"
                        :class="{ 'rotate-180': expanded }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            @endif
        </div>

        {{-- Refresh button --}}
        <div class="mt-4 text-right">
            <button
                wire:click="refresh"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors cursor-pointer"
            >
                <svg class="h-4 w-4" wire:loading.class="animate-spin" wire:target="refresh" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ __('app.refresh') }}
            </button>
        </div>
    @endif
</div>
