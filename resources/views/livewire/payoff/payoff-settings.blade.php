<div class="space-y-8">
    {{-- Settings Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Strategy Selection Card --}}
        <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6">
            <h2 class="font-display text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('app.selected_strategy') }}</h2>
            <div class="inline-flex rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 p-1">
                <button
                    type="button"
                    wire:click="$set('strategy', 'avalanche')"
                    class="px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 {{ $this->strategy === 'avalanche' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}"
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
                    class="px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 {{ $this->strategy === 'snowball' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}"
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
                    class="px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 {{ $this->strategy === 'custom' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}"
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
            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                @if ($this->strategy === 'avalanche')
                    {{ __('app.avalanche_description') }}
                @elseif ($this->strategy === 'snowball')
                    {{ __('app.snowball_description') }}
                @else
                    {{ __('app.custom_order_description') }}
                @endif
            </p>
        </div>

        {{-- Extra Payment Card --}}
        <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6">
            <h2 class="font-display text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('app.extra_monthly_payment') }}</h2>
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
    </div>

    {{-- Impact Preview --}}
    <div class="premium-card rounded-2xl border border-emerald-200 dark:border-emerald-800/50 p-6 bg-gradient-to-r from-emerald-50 to-cyan-50 dark:from-emerald-900/10 dark:to-cyan-900/10">
        <h3 class="font-display text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('app.settings_impact') }}</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Months to Debt-Free --}}
            <div class="premium-card rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('app.months_to_debt_free') }}</p>
                        <p class="font-display text-2xl font-bold text-slate-900 dark:text-white">{{ $this->totalMonths }}</p>
                    </div>
                </div>
            </div>

            {{-- Payoff Date --}}
            <div class="premium-card rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('app.payoff_date') }}</p>
                        <p class="font-display text-2xl font-bold gradient-text">{{ $this->payoffDate }}</p>
                    </div>
                </div>
            </div>

            {{-- Total Interest --}}
            <div class="premium-card rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-rose-100 dark:bg-rose-900/30 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('app.total_interest') }}</p>
                        <p class="font-display text-2xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($this->totalInterest, 0, ',', ' ') }} kr</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Debt Projection Chart --}}
    @if (count($this->debtProjectionData['datasets']) > 0)
        <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6">
            <h2 class="font-display text-lg font-semibold text-slate-900 dark:text-white mb-6">{{ __('app.debt_projection_per_debt') }}</h2>
            <div class="relative h-96"
                wire:key="debt-projection-chart-{{ $this->strategy }}-{{ $this->extraPayment }}"
                x-data="{
                    chart: null,
                    chartData: @js($this->debtProjectionData),
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
                        if (!canvas) {
                            return;
                        }

                        const isDarkMode = document.documentElement.classList.contains('dark');
                        const ctx = canvas.getContext('2d');

                        // Build datasets with consistent styling
                        const datasets = this.chartData.datasets.map((dataset, index) => ({
                            label: dataset.label,
                            data: dataset.data,
                            borderColor: dataset.borderColor,
                            backgroundColor: dataset.borderColor + '20',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 5,
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
                                            font: {
                                                size: 12
                                            }
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
                                            minRotation: 45,
                                            autoSkip: false
                                        }
                                    }
                                }
                            }
                        });
                    }
                }">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    @endif
</div>
