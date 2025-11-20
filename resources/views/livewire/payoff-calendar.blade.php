<div>
    {{-- Countdown Widget (Prominent) --}}
    @if ($this->debtFreeDate && $this->countdown['months'] > 0)
        <div wire:key="countdown-{{ $strategy }}-{{ $extraPayment }}"
             class="bg-gradient-to-r from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-lg shadow-lg border border-green-400 dark:border-green-500 p-8 mb-8"
             x-data="{
                 years: {{ $this->countdown['years'] }},
                 months: {{ $this->countdown['months'] }},
                 days: {{ $this->countdown['days'] }},
                 hours: {{ $this->countdown['hours'] }},
                 minutes: {{ $this->countdown['minutes'] }},
                 seconds: {{ $this->countdown['seconds'] }},
                 totalDays: {{ $this->countdown['totalDays'] }},
                 targetTimestamp: {{ $this->countdown['targetTimestamp'] ?? 'null' }},
                 updateCountdown() {
                     if (!this.targetTimestamp) return;

                     const now = new Date();
                     const target = new Date(this.targetTimestamp);

                     if (target <= now) {
                         this.years = 0;
                         this.months = 0;
                         this.days = 0;
                         this.hours = 0;
                         this.minutes = 0;
                         this.seconds = 0;
                         this.totalDays = 0;
                         return;
                     }

                     // Calculate differences
                     let years = target.getFullYear() - now.getFullYear();
                     let months = target.getMonth() - now.getMonth();
                     let days = target.getDate() - now.getDate();
                     let hours = target.getHours() - now.getHours();
                     let minutes = target.getMinutes() - now.getMinutes();
                     let seconds = target.getSeconds() - now.getSeconds();

                     // Adjust for negative values
                     if (seconds < 0) {
                         seconds += 60;
                         minutes--;
                     }
                     if (minutes < 0) {
                         minutes += 60;
                         hours--;
                     }
                     if (hours < 0) {
                         hours += 24;
                         days--;
                     }
                     if (days < 0) {
                         const prevMonth = new Date(target.getFullYear(), target.getMonth(), 0);
                         days += prevMonth.getDate();
                         months--;
                     }
                     if (months < 0) {
                         months += 12;
                         years--;
                     }

                     this.years = years;
                     this.months = months;
                     this.days = days;
                     this.hours = hours;
                     this.minutes = minutes;
                     this.seconds = seconds;
                     this.totalDays = Math.floor((target - now) / (1000 * 60 * 60 * 24));
                 }
             }"
             x-init="setInterval(() => updateCountdown(), 1000)">
            <div class="text-center">
                <h2 class="text-white text-lg font-medium mb-4">
                    {{ __('app.countdown_to_freedom') }}
                </h2>
                <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-8 mb-4">
                    @if ($this->countdown['years'] > 0)
                        <div class="flex flex-col items-center min-w-0">
                            <div class="text-4xl sm:text-6xl font-bold text-white" x-text="years"></div>
                            <div class="text-green-100 text-xs sm:text-sm font-medium mt-1">{{ trans_choice('app.years', $this->countdown['years']) }}</div>
                        </div>
                    @endif
                    @if ($this->countdown['months'] > 0)
                        <div class="flex flex-col items-center min-w-0">
                            <div class="text-4xl sm:text-6xl font-bold text-white" x-text="months"></div>
                            <div class="text-green-100 text-xs sm:text-sm font-medium mt-1">{{ trans_choice('app.months', $this->countdown['months']) }}</div>
                        </div>
                    @endif
                    <div class="flex flex-col items-center min-w-0">
                        <div class="text-4xl sm:text-6xl font-bold text-white" x-text="days"></div>
                        <div class="text-green-100 text-xs sm:text-sm font-medium mt-1">{{ __('app.days') }}</div>
                    </div>
                    <div class="flex flex-col items-center min-w-0">
                        <div class="text-4xl sm:text-6xl font-bold text-white" x-text="hours.toString().padStart(2, '0')"></div>
                        <div class="text-green-100 text-xs sm:text-sm font-medium mt-1">{{ __('app.hours') }}</div>
                    </div>
                    <div class="flex flex-col items-center min-w-0">
                        <div class="text-4xl sm:text-6xl font-bold text-white" x-text="minutes.toString().padStart(2, '0')"></div>
                        <div class="text-green-100 text-xs sm:text-sm font-medium mt-1">{{ __('app.minutes') }}</div>
                    </div>
                    <div class="flex flex-col items-center min-w-0">
                        <div class="text-4xl sm:text-6xl font-bold text-white" x-text="seconds.toString().padStart(2, '0')"></div>
                        <div class="text-green-100 text-xs sm:text-sm font-medium mt-1">{{ __('app.seconds') }}</div>
                    </div>
                </div>
                <p class="text-green-100 text-sm">
                    {{ __('app.debt_free_by') }} {{ Carbon\Carbon::parse($this->debtFreeDate)->locale(app()->getLocale())->translatedFormat('j. F Y') }}
                    (<span x-text="totalDays"></span> {{ __('app.days') }})
                </p>
            </div>
        </div>
    @endif

    {{-- Controls Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Strategy Selector --}}
            <div>
                <label for="strategy" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    {{ __('app.selected_strategy') }}
                </label>
                <select
                    id="strategy"
                    wire:model.live="strategy"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent"
                >
                    <option value="avalanche">{{ __('app.avalanche_method') }}</option>
                    <option value="snowball">{{ __('app.snowball_method') }}</option>
                    <option value="custom">{{ __('app.custom_order') }}</option>
                </select>
            </div>

            {{-- Extra Payment Input --}}
            <div>
                <label for="extraPayment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    {{ __('app.extra_monthly_payment') }}
                </label>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="$set('extraPayment', {{ max(0, $this->extraPayment - 500) }})"
                        class="h-12 w-12 flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
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
                            class="w-full px-4 py-3 pr-14 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                        >
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">kr</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="$set('extraPayment', {{ $this->extraPayment + 500 }})"
                        class="h-12 w-12 flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
                        aria-label="Increase by 500"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </button>
                </div>
                @error('extraPayment')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Calendar Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Calendar Header with Navigation --}}
        <div class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
            <div class="flex items-center justify-between">
                <button
                    wire:click="previousMonth"
                    type="button"
                    class="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                    aria-label="{{ __('app.previous_month') }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>

                <div class="flex items-center gap-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $this->currentMonthName }}
                    </h2>
                    <select
                        wire:model.live="currentYear"
                        class="px-3 py-1 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                    >
                        @foreach ($this->availableYears as $year)
                            <option value="{{ $year }}">
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                    <button
                        wire:click="goToToday"
                        type="button"
                        class="px-3 py-1 text-sm rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/60 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                    >
                        {{ __('app.today') }}
                    </button>
                </div>

                <button
                    wire:click="nextMonth"
                    type="button"
                    class="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                    aria-label="{{ __('app.next_month') }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="p-6">
            {{-- Day Names Header --}}
            <div class="grid grid-cols-7 gap-2 mb-4">
                @foreach ([__('app.mon'), __('app.tue'), __('app.wed'), __('app.thu'), __('app.fri'), __('app.sat'), __('app.sun')] as $day)
                    <div class="text-center text-xs font-semibold text-gray-600 dark:text-gray-400 py-2">
                        {{ $day }}
                    </div>
                @endforeach
            </div>

            {{-- Calendar Days --}}
            <div class="grid grid-cols-7 gap-2">
                @foreach ($this->calendar as $week)
                    @foreach ($week as $day)
                        @php
                            $dateKey = $day['date']->format('Y-m-d');
                            $hasPayment = isset($this->paymentEvents[$dateKey]);
                            $hasMilestone = isset($this->milestones[$dateKey]);
                            $isDebtFree = $dateKey === $this->debtFreeDate;
                            $hasAnyPaidPayment = $hasPayment && collect($this->paymentEvents[$dateKey]['debts'])->contains('isPaid', true);
                            $hasAnyOverduePayment = $hasPayment && collect($this->paymentEvents[$dateKey]['debts'])->contains('isOverdue', true);
                        @endphp
                        <div
                            class="aspect-square p-2 rounded-lg border transition-all {{ $day['isCurrentMonth'] ? 'border-gray-200 dark:border-gray-700' : 'border-transparent bg-gray-50 dark:bg-gray-900/50' }} {{ $day['isToday'] ? 'ring-2 ring-blue-500 dark:ring-blue-400' : '' }} {{ $isDebtFree ? 'bg-gradient-to-br from-green-400 to-green-500 dark:from-green-600 dark:to-green-700 border-green-500 dark:border-green-400' : ($hasMilestone ? 'bg-purple-50 dark:bg-purple-900/20 border-purple-300 dark:border-purple-700' : ($hasAnyOverduePayment ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700' : ($hasAnyPaidPayment ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700' : ($hasPayment ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : '')))) }}"
                        >
                            <div class="flex flex-col h-full">
                                <div class="text-sm font-medium {{ $day['isCurrentMonth'] ? ($isDebtFree ? 'text-white' : 'text-gray-900 dark:text-white') : 'text-gray-400 dark:text-gray-600' }}">
                                    {{ $day['date']->day }}
                                </div>
                                <div class="flex-1 mt-1 space-y-1">
                                    @if ($isDebtFree)
                                        <div class="text-xs font-bold text-white truncate" title="{{ __('app.debt_free_day') }}">
                                            {{ __('app.debt_free_short') }}
                                        </div>
                                    @endif
                                    @if ($hasMilestone)
                                        @foreach ($this->milestones[$dateKey] as $milestone)
                                            <div class="text-xs font-medium text-purple-700 dark:text-purple-300 truncate" title="{{ __('app.paid_off') }}: {{ $milestone['debtName'] }}">
                                                {{ $milestone['debtName'] }}
                                            </div>
                                        @endforeach
                                    @endif
                                    @if ($hasPayment && !$isDebtFree)
                                        @foreach ($this->paymentEvents[$dateKey]['debts'] as $debt)
                                            @php
                                                $isPaid = $debt['isPaid'] ?? false;
                                                $isOverdue = $debt['isOverdue'] ?? false;
                                                $textColor = $isPaid ? 'text-green-700 dark:text-green-300' : ($isOverdue ? 'text-red-700 dark:text-red-300' : 'text-blue-700 dark:text-blue-300');
                                                $amountColor = $isPaid ? 'text-green-600 dark:text-green-400' : ($isOverdue ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400');
                                                $statusText = $isPaid ? ' - Betalt' : ($isOverdue ? ' - Forfalt' : '');
                                            @endphp
                                            <div class="flex items-center gap-1">
                                                @if ($isPaid)
                                                    <svg class="w-3 h-3 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                @elseif ($isOverdue)
                                                    <svg class="w-3 h-3 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                    </svg>
                                                @endif
                                                <div class="text-xs {{ $textColor }} font-medium truncate" title="{{ $debt['name'] }} ({{ number_format($debt['amount'], 0) }} kr){{ $statusText }}">
                                                    {{ $debt['name'] }}
                                                </div>
                                            </div>
                                            <div class="text-xs {{ $amountColor }}">
                                                {{ number_format($debt['amount'], 0) }} kr
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- Legend --}}
        <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 px-6 py-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                {{ __('app.legend') }}
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded border-2 border-blue-500 dark:border-blue-400 ring-2 ring-blue-500 dark:ring-blue-400"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('app.today') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-blue-50 dark:bg-blue-900/20 border border-blue-300 dark:border-blue-700"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('app.payment_due') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700 flex items-center justify-center">
                        <svg class="w-3 h-3 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('app.payment_paid') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 flex items-center justify-center">
                        <svg class="w-3 h-3 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('app.payment_overdue') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-purple-50 dark:bg-purple-900/20 border border-purple-300 dark:border-purple-700"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('app.debt_paid_off') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-gradient-to-br from-green-400 to-green-500 dark:from-green-600 dark:to-green-700 border border-green-500 dark:border-green-400"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('app.debt_free_day') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- No Debts State --}}
    @if ($this->getDebts()->isEmpty())
        <div class="mt-8 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                {{ __('app.no_debts') }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                {{ __('app.add_debts_to_see_calendar') }}
            </p>
            <a href="{{ route('debts.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                {{ __('app.add_first_debt') }}
            </a>
        </div>
    @endif
</div>
