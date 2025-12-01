<div>
    @if(empty($this->progressData['datasets']))
        <div class="premium-card rounded-2xl p-12 text-center animate-fade-in-up">
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-2xl bg-slate-100 dark:bg-slate-800 mb-4">
                <svg class="h-7 w-7 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </div>
            <h3 class="font-display text-lg font-semibold text-slate-900 dark:text-white mb-2">{{ __('app.no_progress_data') }}</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">{{ __('app.no_progress_data_description') }}</p>
        </div>
    @else
        {{-- Summary Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8 stagger-children">
            {{-- Total Paid --}}
            <div class="premium-card rounded-2xl p-6 stat-card">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30">
                            <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">{{ __('app.total_paid') }}</dt>
                        <dd class="flex flex-col">
                            <div class="font-display text-2xl font-bold gradient-text">{{ number_format($this->totalPaid, 0, ',', ' ') }} kr</div>
                            @if ($this->netDebtChange >= 0)
                                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">{{ __('app.debt_decreased_by') }} {{ number_format($this->netDebtChange, 0, ',', ' ') }} kr</span>
                            @else
                                <span class="text-xs font-medium text-amber-600 dark:text-amber-400">{{ __('app.debt_increased_by') }} {{ number_format(abs($this->netDebtChange), 0, ',', ' ') }} kr</span>
                            @endif
                        </dd>
                    </div>
                </div>
            </div>

            {{-- Total Interest Paid --}}
            <div class="premium-card rounded-2xl p-6 stat-card">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-rose-100 dark:bg-rose-900/30">
                            <svg class="h-6 w-6 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">{{ __('app.total_interest_paid') }}</dt>
                        <dd class="flex items-baseline">
                            <div class="font-display text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($this->totalInterestPaid, 0, ',', ' ') }} kr</div>
                        </dd>
                    </div>
                </div>
            </div>

            {{-- Average Monthly Payment --}}
            <div class="premium-card rounded-2xl p-6 stat-card">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-sky-100 dark:bg-sky-900/30">
                            <svg class="h-6 w-6 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">{{ __('app.average_monthly_payment') }}</dt>
                        <dd class="flex flex-col">
                            <div class="font-display text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($this->averageMonthlyPayment, 0, ',', ' ') }} kr</div>
                            @if ($this->averageNetFlow >= 0)
                                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">{{ __('app.avg_net_flow') }}: {{ number_format($this->averageNetFlow, 0, ',', ' ') }} kr</span>
                            @else
                                <span class="text-xs font-medium text-amber-600 dark:text-amber-400">{{ __('app.avg_net_flow') }}: {{ number_format($this->averageNetFlow, 0, ',', ' ') }} kr</span>
                            @endif
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        {{-- Projection Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8 stagger-children">
            {{-- Months to Debt Free --}}
            <div class="premium-card rounded-2xl p-6 stat-card">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-cyan-100 dark:bg-cyan-900/30">
                            <svg class="h-6 w-6 text-cyan-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">{{ __('app.months_to_debt_free') }}</dt>
                        <dd class="flex items-baseline">
                            <div class="font-display text-2xl font-bold text-slate-900 dark:text-white">{{ $this->monthsToDebtFree }} {{ trans_choice('app.months', $this->monthsToDebtFree) }}</div>
                        </dd>
                    </div>
                </div>
            </div>

            {{-- Payoff Date --}}
            <div class="premium-card rounded-2xl p-6 stat-card">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30">
                            <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">{{ __('app.payoff_date') }}</dt>
                        <dd class="flex items-baseline">
                            <div class="font-display text-2xl font-bold gradient-text">{{ $this->projectedPayoffDate }}</div>
                        </dd>
                    </div>
                </div>
            </div>

            {{-- Total Interest --}}
            <div class="premium-card rounded-2xl p-6 stat-card">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-amber-100 dark:bg-amber-900/30">
                            <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">{{ __('app.total_interest') }}</dt>
                        <dd class="flex items-baseline">
                            <div class="font-display text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($this->projectedTotalInterest, 0, ',', ' ') }} kr</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        {{-- Progress Chart --}}
        <div class="premium-card rounded-2xl p-6 animate-fade-in-up">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-display text-lg font-semibold text-slate-900 dark:text-white">{{ __('app.debt_reduction_over_time') }}</h2>
                {{-- Legend for historical vs projected --}}
                <div class="flex items-center gap-4 text-xs">
                    <div class="flex items-center gap-1.5">
                        <div class="w-6 h-0.5 bg-emerald-500 dark:bg-emerald-400"></div>
                        <span class="text-slate-600 dark:text-slate-400">{{ __('app.historical') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-6 h-0.5 bg-emerald-500/50 dark:bg-emerald-400/50 border-dashed" style="border-top: 2px dashed currentColor; height: 0;"></div>
                        <span class="text-slate-600 dark:text-slate-400">{{ __('app.projected') }}</span>
                    </div>
                </div>
            </div>
            <div class="relative h-96"
                x-data="{
                    chart: null,
                    chartData: @js($this->progressData),
                    historicalEndIndex: @js($this->progressData['historicalEndIndex'] ?? -1),
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
                        const historicalEndIndex = this.historicalEndIndex;

                        // Create gradient for total line (emerald/cyan momentum gradient)
                        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                        if (isDarkMode) {
                            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
                            gradient.addColorStop(1, 'rgba(6, 182, 212, 0.0)');
                        } else {
                            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.15)');
                            gradient.addColorStop(1, 'rgba(6, 182, 212, 0.0)');
                        }

                        // Build datasets from chartData with segment styling for projections
                        const datasets = this.chartData.datasets.map((dataset, index) => {
                            const isTotal = dataset.isTotal === true;
                            const baseColor = isTotal
                                ? (isDarkMode ? 'rgb(52, 211, 153)' : 'rgb(16, 185, 129)')
                                : dataset.borderColor;

                            // Parse hex color to get rgba version for transparency
                            const hexToRgba = (hex, alpha) => {
                                const r = parseInt(hex.slice(1, 3), 16);
                                const g = parseInt(hex.slice(3, 5), 16);
                                const b = parseInt(hex.slice(5, 7), 16);
                                return `rgba(${r}, ${g}, ${b}, ${alpha})`;
                            };

                            // Get the color for projected segments (more transparent)
                            const getProjectedColor = (color) => {
                                if (color.startsWith('#')) {
                                    return hexToRgba(color, 0.5);
                                } else if (color.startsWith('rgb(')) {
                                    return color.replace('rgb(', 'rgba(').replace(')', ', 0.5)');
                                }
                                return color;
                            };

                            if (isTotal) {
                                // Total line with emerald/cyan gradient fill
                                return {
                                    label: dataset.label,
                                    data: dataset.data,
                                    borderColor: baseColor,
                                    backgroundColor: gradient,
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4,
                                    pointRadius: (ctx) => {
                                        // Smaller points for projected data
                                        return ctx.dataIndex > historicalEndIndex ? 2 : 4;
                                    },
                                    pointHoverRadius: 6,
                                    pointBackgroundColor: baseColor,
                                    pointBorderColor: isDarkMode ? 'rgb(30, 41, 59)' : 'rgb(255, 255, 255)',
                                    pointBorderWidth: 2,
                                    order: 1,
                                    segment: {
                                        borderDash: (ctx) => ctx.p0DataIndex >= historicalEndIndex ? [5, 5] : [],
                                        borderColor: (ctx) => {
                                            if (ctx.p0DataIndex >= historicalEndIndex) {
                                                return getProjectedColor(baseColor);
                                            }
                                            return baseColor;
                                        },
                                    },
                                };
                            } else {
                                // Individual debt lines with segment styling
                                return {
                                    label: dataset.label,
                                    data: dataset.data,
                                    borderColor: baseColor,
                                    backgroundColor: dataset.borderColor + '20',
                                    borderWidth: 2,
                                    fill: false,
                                    tension: 0.4,
                                    pointRadius: (ctx) => {
                                        // Smaller points for projected data
                                        return ctx.dataIndex > historicalEndIndex ? 2 : 3;
                                    },
                                    pointHoverRadius: 5,
                                    pointBackgroundColor: baseColor,
                                    pointBorderColor: isDarkMode ? 'rgb(30, 41, 59)' : 'rgb(255, 255, 255)',
                                    pointBorderWidth: 2,
                                    order: 0,
                                    segment: {
                                        borderDash: (ctx) => ctx.p0DataIndex >= historicalEndIndex ? [5, 5] : [],
                                        borderColor: (ctx) => {
                                            if (ctx.p0DataIndex >= historicalEndIndex) {
                                                return getProjectedColor(baseColor);
                                            }
                                            return baseColor;
                                        },
                                    },
                                };
                            }
                        });

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
                                            color: isDarkMode ? 'rgb(148, 163, 184)' : 'rgb(71, 85, 105)',
                                            usePointStyle: true,
                                            pointStyle: 'circle',
                                            padding: 20,
                                            font: {
                                                size: 12,
                                                family: 'Source Sans 3, ui-sans-serif, system-ui, sans-serif'
                                            }
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: isDarkMode ? 'rgb(30, 41, 59)' : 'rgb(255, 255, 255)',
                                        titleColor: isDarkMode ? 'rgb(248, 250, 252)' : 'rgb(15, 23, 42)',
                                        bodyColor: isDarkMode ? 'rgb(148, 163, 184)' : 'rgb(71, 85, 105)',
                                        borderColor: isDarkMode ? 'rgb(51, 65, 85)' : 'rgb(226, 232, 240)',
                                        borderWidth: 1,
                                        padding: 12,
                                        cornerRadius: 8,
                                        titleFont: {
                                            size: 13,
                                            weight: '600',
                                            family: 'Sora, ui-sans-serif, system-ui, sans-serif'
                                        },
                                        bodyFont: {
                                            size: 12,
                                            family: 'Source Sans 3, ui-sans-serif, system-ui, sans-serif'
                                        },
                                        callbacks: {
                                            title: (items) => {
                                                const index = items[0].dataIndex;
                                                const label = items[0].label;
                                                const isProjected = index > historicalEndIndex;
                                                return isProjected ? label + ' ({{ __('app.projected') }})' : label;
                                            },
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
                                            color: isDarkMode ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.6)',
                                            drawBorder: false
                                        },
                                        ticks: {
                                            color: isDarkMode ? 'rgb(148, 163, 184)' : 'rgb(100, 116, 139)',
                                            font: {
                                                family: 'Source Sans 3, ui-sans-serif, system-ui, sans-serif'
                                            },
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
                                            color: isDarkMode ? 'rgb(148, 163, 184)' : 'rgb(100, 116, 139)',
                                            maxRotation: 45,
                                            minRotation: 45,
                                            font: {
                                                family: 'Source Sans 3, ui-sans-serif, system-ui, sans-serif'
                                            }
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
