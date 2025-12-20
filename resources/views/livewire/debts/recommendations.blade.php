<div wire:init="loadData">
    @if ($isLoading)
        {{-- Loading Skeleton --}}
        <div class="animate-pulse space-y-6">
            {{-- Preparedness card skeleton --}}
            <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
                    <div class="flex-1">
                        <div class="h-5 w-32 bg-slate-200 dark:bg-slate-700 rounded mb-1"></div>
                        <div class="h-4 w-48 bg-slate-200 dark:bg-slate-700 rounded"></div>
                    </div>
                    <div class="h-6 w-16 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                </div>
                <div class="space-y-6">
                    {{-- Emergency buffer skeleton --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="h-4 w-36 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div class="h-4 w-28 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        </div>
                        <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                    </div>
                    {{-- Dedicated categories skeleton --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="h-4 w-32 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div class="h-4 w-24 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        </div>
                        <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                    </div>
                    {{-- Pay period skeleton --}}
                    <div class="pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                        <div class="flex items-center justify-between">
                            <div class="h-4 w-48 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div class="h-5 w-20 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recommendations card skeleton --}}
            <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
                    <div class="flex-1">
                        <div class="h-5 w-28 bg-slate-200 dark:bg-slate-700 rounded mb-1"></div>
                        <div class="h-4 w-56 bg-slate-200 dark:bg-slate-700 rounded"></div>
                    </div>
                </div>
                <div class="space-y-4">
                    @for ($i = 0; $i < 2; $i++)
                        <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                            <div class="w-10 h-10 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                            <div class="flex-1">
                                <div class="h-4 w-40 bg-slate-200 dark:bg-slate-700 rounded mb-2"></div>
                                <div class="h-4 w-full bg-slate-200 dark:bg-slate-700 rounded"></div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @elseif (!$this->isYnabConfigured)
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
        {{-- Preparedness Card --}}
        <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500/10 to-indigo-500/10 dark:from-blue-500/20 dark:to-indigo-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-display font-semibold text-lg text-slate-900 dark:text-white">
                        {{ __('app.preparedness') }}
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('app.preparedness_description') }}
                    </p>
                </div>
                {{-- Status Badge --}}
                <div class="ml-auto self-start">
                    @if ($this->bufferStatus['status'] === 'healthy')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                            {{ __('app.buffer_status_healthy') }}
                        </span>
                    @elseif ($this->bufferStatus['status'] === 'warning')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            {{ __('app.buffer_status_warning') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                            {{ __('app.buffer_status_critical') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="space-y-5">
                {{-- Emergency Buffer (Savings Account) --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ __('app.emergency_buffer') }}
                        </span>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">
                            {{ number_format($this->bufferStatus['emergency_buffer']['amount'], 0, ',', ' ') }} / {{ number_format($this->bufferStatus['emergency_buffer']['target'], 0, ',', ' ') }} kr
                        </span>
                    </div>
                    <div class="relative h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        @php
                            $emergencyPercentage = min($this->bufferStatus['emergency_buffer']['percentage'], 100);
                            $emergencyColor = $emergencyPercentage >= 100 ? 'bg-emerald-500' : ($emergencyPercentage >= 50 ? 'bg-blue-500' : 'bg-amber-500');
                        @endphp
                        <div
                            class="absolute inset-y-0 left-0 {{ $emergencyColor }} rounded-full transition-all duration-500"
                            style="width: {{ $emergencyPercentage }}%"
                        ></div>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('app.savings_accounts') }} - {{ number_format($this->bufferStatus['emergency_buffer']['percentage'], 0) }}%
                    </p>
                </div>

                {{-- Dedicated Categories --}}
                @if (!empty($this->bufferStatus['dedicated_categories']))
                    @foreach ($this->bufferStatus['dedicated_categories'] as $category)
                        <div wire:key="category-{{ $loop->index }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ $category['name'] }}
                                </span>
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ number_format($category['balance'], 0, ',', ' ') }} / {{ number_format($category['target'], 0, ',', ' ') }} kr
                                </span>
                            </div>
                            <div class="relative h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                @php
                                    $categoryPercentage = min($category['percentage'], 100);
                                    $categoryColor = $categoryPercentage >= 100 ? 'bg-emerald-500' : ($categoryPercentage >= 50 ? 'bg-indigo-500' : 'bg-amber-500');
                                @endphp
                                <div
                                    class="absolute inset-y-0 left-0 {{ $categoryColor }} rounded-full transition-all duration-500"
                                    style="width: {{ $categoryPercentage }}%"
                                ></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ __('app.dedicated_category') }} - {{ number_format($category['percentage'], 0) }}%
                            </p>
                        </div>
                    @endforeach
                @endif

                {{-- Pay Period Status --}}
                <div class="pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ __('app.pay_period') }}
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                ({{ $this->bufferStatus['pay_period']['start_date'] }} - {{ $this->bufferStatus['pay_period']['end_date'] }})
                            </span>
                        </div>
                        @if ($this->bufferStatus['pay_period']['is_covered'])
                            <span class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ __('app.covered') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-600 dark:text-amber-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                {{ __('app.not_covered') }}
                            </span>
                        @endif
                    </div>
                    @if (!$this->bufferStatus['pay_period']['is_covered'])
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{ __('app.pay_period_funded') }}: {{ number_format($this->bufferStatus['pay_period']['funded'], 0, ',', ' ') }} / {{ number_format($this->bufferStatus['pay_period']['needed'], 0, ',', ' ') }} kr
                        </p>
                    @endif
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
                                <div class="shrink-0 w-10 h-10 rounded-lg flex items-center justify-center {{ $recommendation['status'] === 'action' ? 'bg-amber-100 dark:bg-amber-900/30' : ($recommendation['status'] === 'success' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-200 dark:bg-slate-700') }}">
<x-recommendation-icon :icon="$recommendation['icon']" :status="$recommendation['status']" />
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
                                                        {{ __('app.buffer.buffer_impact', ['amount' => number_format($option['impact']['amount_added'], 0, ',', ' '), 'percentage' => number_format($option['impact']['new_percentage'], 0)]) }}
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
