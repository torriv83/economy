<div>
    @if (!$this->isYnabConfigured)
        {{-- YNAB Not Configured State --}}
        <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-12 text-center">
            <div class="max-w-sm mx-auto">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-amber-500/10 to-orange-500/10 dark:from-amber-500/20 dark:to-orange-500/20 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                    </svg>
                </div>
                <h2 class="font-display font-semibold text-xl text-slate-900 dark:text-white mb-2">
                    {{ __('app.ynab_required_for_recommendations') }}
                </h2>
                <p class="text-slate-500 dark:text-slate-400 mb-6">
                    {{ __('app.ynab_required_for_recommendations_description') }}
                </p>
                <a href="{{ route('settings', ['view' => 'ynab']) }}" wire:navigate class="inline-flex items-center gap-2 btn-momentum px-6 py-3 rounded-xl font-medium transition-all">
                    {{ __('app.configure_ynab') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>
    @elseif ($this->bufferStatus === null)
        {{-- Loading/Error State --}}
        <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-12 text-center">
            <div class="max-w-sm mx-auto">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-slate-500/10 to-slate-500/10 dark:from-slate-500/20 dark:to-slate-500/20 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-slate-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h2 class="font-display font-semibold text-xl text-slate-900 dark:text-white mb-2">
                    {{ __('app.loading_recommendations') }}
                </h2>
                <p class="text-slate-500 dark:text-slate-400">
                    {{ __('app.loading_recommendations_description') }}
                </p>
            </div>
        </div>
    @else
        {{-- Security Buffer Card --}}
        <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500/10 to-indigo-500/10 dark:from-blue-500/20 dark:to-indigo-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-display font-semibold text-lg text-slate-900 dark:text-white">
                        {{ __('app.security_buffer') }}
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('app.months_of_security_count', ['count' => $this->bufferStatus['months_of_security']]) }}
                    </p>
                </div>
                {{-- Status Badge --}}
                <div class="ml-auto">
                    @if ($this->bufferStatus['status'] === 'healthy')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                            {{ __('app.buffer_status_healthy') }}
                        </span>
                    @elseif ($this->bufferStatus['status'] === 'warning')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            {{ __('app.buffer_status_warning') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                            {{ __('app.buffer_status_critical') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                {{-- Layer 1: Operational Buffer --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ __('app.layer1_operational_buffer') }}
                        </span>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">
                            {{ number_format($this->bufferStatus['layer1']['amount'], 0, ',', ' ') }} kr
                        </span>
                    </div>
                    <div class="relative h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div
                            class="absolute inset-y-0 left-0 {{ $this->bufferStatus['layer1']['is_month_ahead'] ? 'bg-emerald-500' : 'bg-amber-500' }} rounded-full transition-all duration-500"
                            style="width: {{ min($this->bufferStatus['layer1']['percentage'], 100) }}%"
                        ></div>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('app.assigned_next_month') }} -
                        @if ($this->bufferStatus['layer1']['is_month_ahead'])
                            <span class="text-emerald-600 dark:text-emerald-400">{{ __('app.month_ahead') }}</span>
                        @else
                            {{ $this->bufferStatus['layer1']['percentage'] }}%
                        @endif
                    </p>
                </div>

                {{-- Layer 2: Emergency Buffer --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ __('app.layer2_emergency_buffer') }}
                        </span>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">
                            {{ number_format($this->bufferStatus['layer2']['amount'], 0, ',', ' ') }} kr
                        </span>
                    </div>
                    <div class="relative h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        @php
                            $layer2Percentage = $this->bufferStatus['layer2']['target_months'] > 0
                                ? min(100, ($this->bufferStatus['layer2']['months'] / $this->bufferStatus['layer2']['target_months']) * 100)
                                : 0;
                        @endphp
                        <div
                            class="absolute inset-y-0 left-0 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full transition-all duration-500"
                            style="width: {{ $layer2Percentage }}%"
                        ></div>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('app.savings_accounts') }} -
                        {{ __('app.of_target', ['months' => $this->bufferStatus['layer2']['months'], 'target' => $this->bufferStatus['layer2']['target_months']]) }}
                    </p>
                </div>

                {{-- Total --}}
                <div class="pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ __('app.total_buffer') }}
                        </span>
                        <span class="font-display font-bold text-xl text-slate-900 dark:text-white">
                            {{ number_format($this->bufferStatus['total_buffer'], 0, ',', ' ') }} kr
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recommendations Card --}}
        @if (count($this->recommendations) > 0)
            <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500/10 to-orange-500/10 dark:from-amber-500/20 dark:to-orange-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-display font-semibold text-lg text-slate-900 dark:text-white">
                            {{ __('app.buffer.recommendations_title') }}
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ __('app.buffer.recommendations_description') }}
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach ($this->recommendations as $recommendation)
                        <div wire:key="rec-{{ $loop->index }}" class="flex items-start gap-4 p-4 rounded-xl {{ $recommendation['status'] === 'action' ? 'bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/50' : ($recommendation['status'] === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800/50' : 'bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700') }}">
                            {{-- Icon --}}
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center {{ $recommendation['status'] === 'action' ? 'bg-amber-100 dark:bg-amber-900/30' : ($recommendation['status'] === 'success' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-200 dark:bg-slate-700') }}">
                                @if ($recommendation['icon'] === 'check-circle')
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @elseif ($recommendation['icon'] === 'arrow-right')
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                @elseif ($recommendation['icon'] === 'banknotes')
                                    <svg class="w-5 h-5 {{ $recommendation['status'] === 'action' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                    </svg>
                                @elseif ($recommendation['icon'] === 'shield')
                                    <svg class="w-5 h-5 {{ $recommendation['status'] === 'action' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                    </svg>
                                @elseif ($recommendation['icon'] === 'shield-check')
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                    </svg>
                                @elseif ($recommendation['icon'] === 'scale')
                                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-sm {{ $recommendation['status'] === 'action' ? 'text-amber-900 dark:text-amber-200' : ($recommendation['status'] === 'success' ? 'text-emerald-900 dark:text-emerald-200' : 'text-slate-900 dark:text-white') }}">
                                    {{ __('app.'.$recommendation['title'], $recommendation['params']) }}
                                </h3>
                                <p class="mt-1 text-sm {{ $recommendation['status'] === 'action' ? 'text-amber-700 dark:text-amber-300' : ($recommendation['status'] === 'success' ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-400') }}">
                                    {{ __('app.'.$recommendation['description'], $recommendation['params']) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Scenario Comparison Toggle --}}
                <div class="mt-6 pt-6 border-t border-slate-200/50 dark:border-slate-700/50">
                    <button
                        wire:click="toggleScenarioComparison"
                        class="flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4 transition-transform {{ $showScenarioComparison ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                        {{ $showScenarioComparison ? __('app.buffer.hide_options') : __('app.buffer.compare_options') }}
                    </button>

                    {{-- Scenario Comparison Panel --}}
                    @if ($showScenarioComparison && $this->scenarioComparison)
                        <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <h4 class="font-semibold text-sm text-slate-900 dark:text-white mb-4">
                                {{ __('app.buffer.scenario_comparison_title', ['amount' => number_format($scenarioAmount, 0, ',', ' ').' kr']) }}
                            </h4>

                            {{-- Amount Input --}}
                            <div class="mb-4">
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    {{ __('app.amount') }}
                                </label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="number"
                                        wire:model.live.debounce.500ms="scenarioAmount"
                                        min="100"
                                        step="100"
                                        class="flex-1 px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent"
                                    >
                                    <span class="text-sm text-slate-500 dark:text-slate-400">kr</span>
                                </div>
                            </div>

                            {{-- Options --}}
                            <div class="space-y-3">
                                @foreach ($this->scenarioComparison['options'] as $option)
                                    <div wire:key="option-{{ $loop->index }}" class="flex items-center justify-between p-3 bg-white dark:bg-slate-700/50 rounded-lg border {{ $this->scenarioComparison['recommendation']['target'] === $option['target'] || ($option['target'] === 'debt' && str_starts_with($this->scenarioComparison['recommendation']['target'], 'debt')) ? 'border-emerald-300 dark:border-emerald-700' : 'border-slate-200 dark:border-slate-600' }}">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                        @if ($option['target'] === 'buffer')
                                                            {{ __('app.buffer.scenario_buffer') }}
                                                        @else
                                                            {{ __('app.buffer.scenario_debt', ['debt_name' => $option['debt_name'] ?? '']) }}
                                                        @endif
                                                    </span>
                                                    @if ($this->scenarioComparison['recommendation']['target'] === $option['target'] || ($option['target'] === 'debt' && isset($option['debt_id']) && $this->scenarioComparison['recommendation']['target'] === 'debt'))
                                                        <span class="px-1.5 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded">
                                                            {{ __('app.buffer.recommended') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    @if ($option['target'] === 'buffer')
                                                        {{ __('app.buffer.days_security', ['days' => $option['impact']['days_of_security_added']]) }}
                                                        ({{ $this->bufferStatus['months_of_security'] }} → {{ $option['impact']['new_buffer_months'] }} {{ __('app.months_short') }})
                                                    @else
                                                        {{ __('app.buffer.interest_savings', ['amount' => number_format($option['impact']['interest_saved'] ?? 0, 0, ',', ' ')]) }},
                                                        {{ __('app.buffer.months_earlier', ['months' => $option['impact']['months_saved'] ?? 0]) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                @endforeach
                            </div>

                            {{-- Recommendation Reason --}}
                            <div class="mt-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800/50">
                                <p class="text-sm text-emerald-700 dark:text-emerald-300">
                                    <span class="font-medium">{{ __('app.buffer.recommended') }}:</span>
                                    {{ __('app.'.$this->scenarioComparison['recommendation']['reason']) }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- No Recommendations Available --}}
            <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-12 text-center">
                <div class="max-w-sm mx-auto">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-500/10 to-cyan-500/10 dark:from-emerald-500/20 dark:to-cyan-500/20 flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="font-display font-semibold text-xl text-slate-900 dark:text-white mb-2">
                        {{ __('app.no_recommendations_available') }}
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400">
                        {{ __('app.no_recommendations_available_description') }}
                    </p>
                </div>
            </div>
        @endif
    @endif
</div>
