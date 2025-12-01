<div>
    {{-- Extra Payment Section --}}
    <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6 mb-8">
        <label for="extraPayment" class="block font-display font-medium text-slate-700 dark:text-slate-300 mb-3">
            {{ __('app.extra_monthly_payment') }}
        </label>
        <div class="flex items-center gap-3">
            <button
                type="button"
                wire:click="$set('extraPayment', {{ max(0, $this->extraPayment - 500) }})"
                class="h-12 w-12 flex items-center justify-center bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
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
                    class="w-full px-4 py-3 pr-12 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-display font-bold text-center text-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                >
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 dark:text-slate-400 font-medium pointer-events-none">
                    kr
                </span>
            </div>
            <button
                type="button"
                wire:click="$set('extraPayment', {{ $this->extraPayment + 500 }})"
                class="h-12 w-12 flex items-center justify-center bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                aria-label="Increase by 500"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </button>
        </div>
        @error('extraPayment')
            <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
        @enderror
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
            {{ __('app.enter_extra_payment_description') }}
        </p>
    </div>

    {{-- Strategy Comparison Columns --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Snowball Method Column --}}
        <div class="premium-card rounded-2xl {{ $this->bestStrategy === 'snowball' ? 'ring-2 ring-cyan-500 border-cyan-300 dark:border-cyan-700' : 'border border-slate-200 dark:border-slate-700/50' }} overflow-hidden">
            {{-- Header --}}
            <div class="bg-cyan-50 dark:bg-cyan-900/20 border-b border-cyan-100 dark:border-cyan-800/30 px-6 py-4">
                <div class="flex items-center gap-3 mb-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-xl text-sm font-semibold bg-cyan-100 dark:bg-cyan-900/40 text-cyan-800 dark:text-cyan-300">
                        {{ __('app.snowball_method') }}
                    </span>
                    @if ($this->bestStrategy === 'snowball')
                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-cyan-500 text-white">
                            {{ __('app.recommended') }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    {{ __('app.snowball_description') }}
                </p>
            </div>

            {{-- Payment Order --}}
            <div class="px-6 py-5">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">
                    {{ __('app.payment_order') }}
                </h3>
                <div class="space-y-3">
                    @foreach ($this->orderedDebts['snowball'] as $index => $debt)
                        <div wire:key="snowball-{{ $index }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-300 text-xs font-bold">
                                    {{ $index + 1 }}
                                </span>
                                <span class="font-medium text-slate-900 dark:text-white">{{ $debt['name'] }}</span>
                            </div>
                            <div class="text-right">
                                <div class="font-display font-semibold text-slate-900 dark:text-white">{{ number_format($debt['balance'], 0, ',', ' ') }} kr</div>
                                <div class="text-sm text-slate-500 dark:text-slate-400">{{ number_format($debt['interestRate'], 1, ',', ' ') }}%</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Summary --}}
            <div class="border-t border-slate-200 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-800/30 px-6 py-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('app.time_to_debt_free') }}</span>
                    <span class="font-display font-bold text-slate-900 dark:text-white">{{ $this->snowballData['months'] }} {{ __('app.months_short') }}</span>
                </div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('app.total_interest') }}</span>
                    <span class="font-display font-bold text-rose-600 dark:text-rose-400">{{ number_format($this->snowballData['totalInterest'], 0, ',', ' ') }} kr</span>
                </div>
                @if ($this->minimumPaymentMonths > 0 && $this->snowballSavings['monthsSaved'] > 0)
                    <div class="flex items-center justify-between text-cyan-600 dark:text-cyan-400">
                        <span class="text-sm font-medium">{{ __('app.faster_than_minimum') }}</span>
                        <span class="text-sm font-bold">
                            @if ($this->snowballSavings['yearsSaved'] > 0)
                                {{ $this->snowballSavings['yearsSaved'] }} {{ trans_choice('app.years', $this->snowballSavings['yearsSaved']) }}
                            @endif
                            @if ($this->snowballSavings['remainingMonths'] > 0)
                                {{ $this->snowballSavings['remainingMonths'] }} {{ trans_choice('app.months', $this->snowballSavings['remainingMonths']) }}
                            @endif
                        </span>
                    </div>
                @endif
                @if ($this->snowballData['savings'] > 0)
                    <div class="pt-3 mt-3 border-t border-slate-200 dark:border-slate-700/50">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ __('app.money_saved') }}</span>
                            <span class="font-display text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($this->snowballData['savings'], 0, ',', ' ') }} kr {{ __('app.vs_minimum') }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Avalanche Method Column --}}
        <div class="premium-card rounded-2xl {{ $this->bestStrategy === 'avalanche' ? 'ring-2 ring-emerald-500 border-emerald-300 dark:border-emerald-700' : 'border border-slate-200 dark:border-slate-700/50' }} overflow-hidden">
            {{-- Header --}}
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-100 dark:border-emerald-800/30 px-6 py-4">
                <div class="flex items-center gap-3 mb-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-xl text-sm font-semibold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">
                        {{ __('app.avalanche_method') }}
                    </span>
                    @if ($this->bestStrategy === 'avalanche')
                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-emerald-500 text-white">
                            {{ __('app.recommended') }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    {{ __('app.avalanche_description') }}
                </p>
            </div>

            {{-- Payment Order --}}
            <div class="px-6 py-5">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">
                    {{ __('app.payment_order') }}
                </h3>
                <div class="space-y-3">
                    @foreach ($this->orderedDebts['avalanche'] as $index => $debt)
                        <div wire:key="avalanche-{{ $index }}" class="flex items-center justify-between p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold">
                                    {{ $index + 1 }}
                                </span>
                                <span class="font-medium text-slate-900 dark:text-white">{{ $debt['name'] }}</span>
                            </div>
                            <div class="text-right">
                                <div class="font-display font-semibold text-slate-900 dark:text-white">{{ number_format($debt['balance'], 0, ',', ' ') }} kr</div>
                                <div class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">{{ number_format($debt['interestRate'], 1, ',', ' ') }}%</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Summary --}}
            <div class="border-t border-emerald-200 dark:border-emerald-800/50 bg-emerald-50 dark:bg-emerald-900/20 px-6 py-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('app.time_to_debt_free') }}</span>
                    <span class="font-display font-bold text-slate-900 dark:text-white">{{ $this->avalancheData['months'] }} {{ __('app.months_short') }}</span>
                </div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('app.total_interest') }}</span>
                    <span class="font-display font-bold text-rose-600 dark:text-rose-400">{{ number_format($this->avalancheData['totalInterest'], 0, ',', ' ') }} kr</span>
                </div>
                @if ($this->minimumPaymentMonths > 0 && $this->avalancheSavings['monthsSaved'] > 0)
                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                        <span class="text-sm font-medium">{{ __('app.faster_than_minimum') }}</span>
                        <span class="text-sm font-bold">
                            @if ($this->avalancheSavings['yearsSaved'] > 0)
                                {{ $this->avalancheSavings['yearsSaved'] }} {{ trans_choice('app.years', $this->avalancheSavings['yearsSaved']) }}
                            @endif
                            @if ($this->avalancheSavings['remainingMonths'] > 0)
                                {{ $this->avalancheSavings['remainingMonths'] }} {{ trans_choice('app.months', $this->avalancheSavings['remainingMonths']) }}
                            @endif
                        </span>
                    </div>
                @endif
                <div class="pt-3 mt-3 border-t border-emerald-200 dark:border-emerald-800/50">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ __('app.money_saved') }}</span>
                        <span class="font-display text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($this->avalancheData['savings'], 0, ',', ' ') }} kr {{ __('app.vs_minimum') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Custom Method Column --}}
        <div class="premium-card rounded-2xl {{ $this->bestStrategy === 'custom' ? 'ring-2 ring-amber-500 border-amber-300 dark:border-amber-700' : 'border border-slate-200 dark:border-slate-700/50' }} overflow-hidden">
            {{-- Header --}}
            <div class="bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800/30 px-6 py-4">
                <div class="flex items-center gap-3 mb-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-xl text-sm font-semibold bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                        {{ __('app.custom_order') }}
                    </span>
                    @if ($this->bestStrategy === 'custom')
                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-amber-500 text-white">
                            {{ __('app.recommended') }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    {{ __('app.custom_order_description') }}
                </p>
            </div>

            {{-- Payment Order --}}
            <div class="px-6 py-5">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">
                    {{ __('app.payment_order') }}
                </h3>
                <div class="space-y-3">
                    @foreach ($this->orderedDebts['custom'] as $index => $debt)
                        <div wire:key="custom-{{ $index }}" class="flex items-center justify-between p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-xs font-bold">
                                    {{ $index + 1 }}
                                </span>
                                <span class="font-medium text-slate-900 dark:text-white">{{ $debt['name'] }}</span>
                            </div>
                            <div class="text-right">
                                <div class="font-display font-semibold text-slate-900 dark:text-white">{{ number_format($debt['balance'], 0, ',', ' ') }} kr</div>
                                <div class="text-sm text-amber-600 dark:text-amber-400 font-medium">{{ number_format($debt['interestRate'], 1, ',', ' ') }}%</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Summary --}}
            <div class="border-t border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-900/20 px-6 py-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('app.time_to_debt_free') }}</span>
                    <span class="font-display font-bold text-slate-900 dark:text-white">{{ $this->customData['months'] }} {{ __('app.months_short') }}</span>
                </div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('app.total_interest') }}</span>
                    <span class="font-display font-bold text-rose-600 dark:text-rose-400">{{ number_format($this->customData['totalInterest'], 0, ',', ' ') }} kr</span>
                </div>
                @if ($this->minimumPaymentMonths > 0 && $this->customSavings['monthsSaved'] > 0)
                    <div class="flex items-center justify-between text-amber-600 dark:text-amber-400">
                        <span class="text-sm font-medium">{{ __('app.faster_than_minimum') }}</span>
                        <span class="text-sm font-bold">
                            @if ($this->customSavings['yearsSaved'] > 0)
                                {{ $this->customSavings['yearsSaved'] }} {{ trans_choice('app.years', $this->customSavings['yearsSaved']) }}
                            @endif
                            @if ($this->customSavings['remainingMonths'] > 0)
                                {{ $this->customSavings['remainingMonths'] }} {{ trans_choice('app.months', $this->customSavings['remainingMonths']) }}
                            @endif
                        </span>
                    </div>
                @endif
                <div class="pt-3 mt-3 border-t border-amber-200 dark:border-amber-800/50">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-amber-600 dark:text-amber-400">{{ __('app.money_saved') }}</span>
                        <span class="font-display text-lg font-bold text-amber-600 dark:text-amber-400">{{ number_format($this->customData['savings'], 0, ',', ' ') }} kr {{ __('app.vs_minimum') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Strategy Projection Chart --}}
    @if ($this->minimumPaymentMonths > 0 && count($this->getDebts()) > 0)
        <div class="mt-8 premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6">
            <h2 class="font-display text-xl font-bold text-slate-900 dark:text-white mb-6">
                {{ __('app.debt_projection_comparison') }}
            </h2>

            {{-- Chart Container --}}
            <div class="relative h-96"
                x-data="{
                    chart: null,
                    chartData: @js($this->strategyChartData),
                    init() {
                        this.loadChartJs();
                    },
                    loadChartJs() {
                        if (typeof Chart !== 'undefined') {
                            this.initChart();
                            return;
                        }

                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
                        script.onload = () => this.initChart();
                        document.head.appendChild(script);
                    },
                    initChart() {
                        const canvas = this.$refs.canvas;
                        if (!canvas) return;

                        const isDarkMode = document.documentElement.classList.contains('dark');
                        const ctx = canvas.getContext('2d');

                        const datasets = this.chartData.datasets.map((dataset) => ({
                            label: dataset.label,
                            data: dataset.data,
                            borderColor: dataset.borderColor,
                            backgroundColor: dataset.backgroundColor,
                            borderWidth: 2,
                            borderDash: dataset.borderDash || [],
                            fill: false,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: dataset.borderColor,
                            pointBorderColor: isDarkMode ? 'rgb(30, 41, 59)' : 'rgb(255, 255, 255)',
                            pointBorderWidth: 2,
                        }));

                        this.chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: this.chartData.labels,
                                datasets: datasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    intersect: false,
                                    mode: 'index'
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top',
                                        labels: {
                                            color: isDarkMode ? 'rgb(203, 213, 225)' : 'rgb(75, 85, 99)',
                                            usePointStyle: true,
                                            pointStyle: 'circle',
                                            padding: 20,
                                            font: { size: 12 }
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: isDarkMode ? 'rgb(30, 41, 59)' : 'rgb(255, 255, 255)',
                                        titleColor: isDarkMode ? 'rgb(248, 250, 252)' : 'rgb(17, 24, 39)',
                                        bodyColor: isDarkMode ? 'rgb(203, 213, 225)' : 'rgb(75, 85, 99)',
                                        borderColor: isDarkMode ? 'rgb(51, 65, 85)' : 'rgb(229, 231, 235)',
                                        borderWidth: 1,
                                        padding: 12,
                                        callbacks: {
                                            label: function(context) {
                                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString('nb-NO') + ' kr';
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: isDarkMode ? 'rgba(51, 65, 85, 0.5)' : 'rgba(229, 231, 235, 0.5)',
                                            drawBorder: false
                                        },
                                        ticks: {
                                            color: isDarkMode ? 'rgb(148, 163, 184)' : 'rgb(107, 114, 128)',
                                            callback: function(value) {
                                                return value.toLocaleString('nb-NO') + ' kr';
                                            }
                                        }
                                    },
                                    x: {
                                        grid: {
                                            display: false,
                                            drawBorder: false
                                        },
                                        ticks: {
                                            color: isDarkMode ? 'rgb(148, 163, 184)' : 'rgb(107, 114, 128)',
                                            maxRotation: 45,
                                            minRotation: 45
                                        }
                                    }
                                }
                            }
                        });
                    }
                }"
                wire:key="strategy-chart-{{ $this->extraPayment }}">
                <canvas x-ref="canvas"></canvas>
            </div>

            {{-- Summary Statistics Grid --}}
            <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Minimum Payments --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-3 h-3 rounded-full bg-slate-500"></div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('app.minimum_payments_only') }}</span>
                    </div>
                    <div class="font-display text-lg font-bold text-slate-900 dark:text-white">
                        {{ $this->minimumPaymentMonths }} {{ __('app.months_short') }}
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                        {{ number_format($this->minimumPaymentInterest, 0, ',', ' ') }} kr {{ __('app.interest_paid') }}
                    </div>
                </div>

                {{-- Snowball --}}
                <div class="p-4 rounded-xl bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800/50">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-3 h-3 rounded-full bg-cyan-500"></div>
                        <span class="text-sm font-medium text-cyan-700 dark:text-cyan-300">{{ __('app.snowball_method') }}</span>
                    </div>
                    <div class="font-display text-lg font-bold text-slate-900 dark:text-white">
                        {{ $this->snowballData['months'] }} {{ __('app.months_short') }}
                    </div>
                    <div class="text-sm text-emerald-600 dark:text-emerald-400">
                        {{ __('app.saves') }} {{ number_format($this->snowballData['savings'], 0, ',', ' ') }} kr
                    </div>
                </div>

                {{-- Avalanche --}}
                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ __('app.avalanche_method') }}</span>
                    </div>
                    <div class="font-display text-lg font-bold text-slate-900 dark:text-white">
                        {{ $this->avalancheData['months'] }} {{ __('app.months_short') }}
                    </div>
                    <div class="text-sm text-emerald-600 dark:text-emerald-400">
                        {{ __('app.saves') }} {{ number_format($this->avalancheData['savings'], 0, ',', ' ') }} kr
                    </div>
                </div>

                {{-- Custom --}}
                <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <span class="text-sm font-medium text-amber-700 dark:text-amber-300">{{ __('app.custom_order') }}</span>
                    </div>
                    <div class="font-display text-lg font-bold text-slate-900 dark:text-white">
                        {{ $this->customData['months'] }} {{ __('app.months_short') }}
                    </div>
                    <div class="text-sm text-emerald-600 dark:text-emerald-400">
                        {{ __('app.saves') }} {{ number_format($this->customData['savings'], 0, ',', ' ') }} kr
                    </div>
                </div>
            </div>

            {{-- Milestones Section --}}
            <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700/50">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">
                    {{ __('app.debt_payoff_milestones') }}
                </h3>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    {{-- Snowball Milestones --}}
                    @if (count($this->snowballMilestones) > 0)
                        <div>
                            <div class="text-xs font-medium text-cyan-600 dark:text-cyan-400 mb-2">{{ __('app.snowball_method') }}</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->snowballMilestones as $milestone)
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800/50">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $milestone['name'] }} ({{ __('app.month') }} {{ $milestone['month'] }})
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Avalanche Milestones --}}
                    @if (count($this->avalancheMilestones) > 0)
                        <div>
                            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400 mb-2">{{ __('app.avalanche_method') }}</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->avalancheMilestones as $milestone)
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $milestone['name'] }} ({{ __('app.month') }} {{ $milestone['month'] }})
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Custom Milestones --}}
                    @if (count($this->customMilestones) > 0)
                        <div>
                            <div class="text-xs font-medium text-amber-600 dark:text-amber-400 mb-2">{{ __('app.custom_order') }}</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->customMilestones as $milestone)
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $milestone['name'] }} ({{ __('app.month') }} {{ $milestone['month'] }})
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
