<div>
    {{-- Success Message --}}
    @if (session('payment_recorded'))
        <div class="mb-6 premium-card rounded-xl border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-200 px-4 py-3 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ session('payment_recorded') }}
        </div>
    @endif

    {{-- Countdown Widget (Prominent) --}}
    @if ($this->debtFreeDate && $this->countdown['months'] > 0)
        <div wire:key="countdown-{{ $strategy }}-{{ $extraPayment }}"
             class="relative premium-card rounded-2xl border border-emerald-200 dark:border-emerald-800/50 mb-6 sm:mb-8 transition-all duration-200 overflow-hidden"
             :class="showCountdown ? 'p-4 sm:p-6 md:p-8' : 'px-4 py-2'"
             x-data="{
                 showCountdown: $persist(true).as('countdown-expanded'),
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
            {{-- Gradient Header Bar --}}
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-cyan-500" :class="showCountdown ? '' : 'hidden'"></div>

            <div class="text-center">
                <button
                    type="button"
                    @click="showCountdown = !showCountdown"
                    class="w-full flex items-center justify-center gap-2 cursor-pointer group"
                    :class="showCountdown ? 'mb-4' : 'mb-0'"
                >
                    <h2 class="font-display text-lg font-semibold text-slate-900 dark:text-white">
                        {{ __('app.countdown_to_freedom') }}
                    </h2>
                    <svg
                        class="w-5 h-5 text-slate-400 transition-transform duration-200 group-hover:text-slate-600 dark:group-hover:text-slate-300"
                        :class="{ 'rotate-180': showCountdown }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="showCountdown" x-collapse>
                <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-4 md:gap-8 mb-4">
                    @if ($this->countdown['years'] > 0)
                        <div class="flex flex-col items-center min-w-0">
                            <div class="font-display text-2xl sm:text-4xl md:text-6xl font-bold gradient-text" x-text="years"></div>
                            <div class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-1">{{ trans_choice('app.years', $this->countdown['years']) }}</div>
                        </div>
                    @endif
                    @if ($this->countdown['months'] > 0)
                        <div class="flex flex-col items-center min-w-0">
                            <div class="font-display text-2xl sm:text-4xl md:text-6xl font-bold gradient-text" x-text="months"></div>
                            <div class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-1">{{ trans_choice('app.months', $this->countdown['months']) }}</div>
                        </div>
                    @endif
                    <div class="flex flex-col items-center min-w-0">
                        <div class="font-display text-2xl sm:text-4xl md:text-6xl font-bold gradient-text" x-text="days"></div>
                        <div class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-1">{{ __('app.days') }}</div>
                    </div>
                    <div class="flex flex-col items-center min-w-0">
                        <div class="font-display text-2xl sm:text-4xl md:text-6xl font-bold text-slate-700 dark:text-slate-300" x-text="hours.toString().padStart(2, '0')"></div>
                        <div class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-1">{{ __('app.hours') }}</div>
                    </div>
                    <div class="flex flex-col items-center min-w-0">
                        <div class="font-display text-2xl sm:text-4xl md:text-6xl font-bold text-slate-700 dark:text-slate-300" x-text="minutes.toString().padStart(2, '0')"></div>
                        <div class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-1">{{ __('app.minutes') }}</div>
                    </div>
                    <div class="flex flex-col items-center min-w-0">
                        <div class="font-display text-2xl sm:text-4xl md:text-6xl font-bold text-slate-700 dark:text-slate-300" x-text="seconds.toString().padStart(2, '0')"></div>
                        <div class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-1">{{ __('app.seconds') }}</div>
                    </div>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-sm">
                    {{ __('app.debt_free_by') }} <span class="font-semibold gradient-text">{{ Carbon\Carbon::parse($this->debtFreeDate)->locale(app()->getLocale())->translatedFormat('j. F Y') }}</span>
                    (<span x-text="totalDays"></span> {{ __('app.days') }})
                </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Calendar Section --}}
    <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 overflow-hidden">
        {{-- Calendar Header with Navigation --}}
        <div class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700/50 px-3 sm:px-6 py-3 sm:py-4">
            {{-- Mobile Header (stacked) --}}
            <div class="flex flex-col gap-3 sm:hidden">
                <div class="flex items-center justify-between">
                    <button
                        wire:click="previousMonth"
                        type="button"
                        class="p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        aria-label="{{ __('app.previous_month') }}"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>

                    <h2 class="font-display text-base font-semibold text-slate-900 dark:text-white">
                        {{ $this->currentMonthName }}
                    </h2>

                    <button
                        wire:click="nextMonth"
                        type="button"
                        class="p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        aria-label="{{ __('app.next_month') }}"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
                <div class="flex items-center justify-center gap-2">
                    <select
                        wire:model.live="currentYear"
                        class="px-2 py-1 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                        @foreach ($this->availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                    <button
                        wire:click="goToToday"
                        type="button"
                        class="px-3 py-1 text-sm rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                        {{ __('app.today') }}
                    </button>
                    @if ($ynabEnabled)
                        <button
                            wire:click="openYnabModal"
                            type="button"
                            class="px-3 py-1 text-sm rounded-lg bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 hover:bg-cyan-200 dark:hover:bg-cyan-900/50 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-cyan-500 flex items-center gap-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{ __('app.check_ynab') }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- Desktop Header (horizontal) --}}
            <div class="hidden sm:flex items-center justify-between">
                <button
                    wire:click="previousMonth"
                    type="button"
                    class="p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    aria-label="{{ __('app.previous_month') }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>

                <div class="flex items-center gap-4">
                    <h2 class="font-display text-xl font-semibold text-slate-900 dark:text-white">
                        {{ $this->currentMonthName }}
                    </h2>
                    <select
                        wire:model.live="currentYear"
                        class="px-3 py-1 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
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
                        class="px-3 py-1 text-sm rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                        {{ __('app.today') }}
                    </button>
                    @if ($ynabEnabled)
                        <button
                            wire:click="openYnabModal"
                            type="button"
                            class="px-3 py-1 text-sm rounded-lg bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 hover:bg-cyan-200 dark:hover:bg-cyan-900/50 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-cyan-500 flex items-center gap-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{ __('app.check_ynab') }}
                        </button>
                    @endif
                </div>

                <button
                    wire:click="nextMonth"
                    type="button"
                    class="p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    aria-label="{{ __('app.next_month') }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Calendar Grid - Desktop --}}
        <div class="hidden sm:block p-4 md:p-6">
            {{-- Day Names Header --}}
            <div class="grid grid-cols-7 gap-1 md:gap-2 mb-4">
                @foreach ([__('app.mon'), __('app.tue'), __('app.wed'), __('app.thu'), __('app.fri'), __('app.sat'), __('app.sun')] as $day)
                    <div class="text-center text-xs font-semibold text-slate-500 dark:text-slate-400 py-2">
                        {{ $day }}
                    </div>
                @endforeach
            </div>

            {{-- Calendar Days --}}
            <div class="grid grid-cols-7 gap-1 md:gap-2">
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
                            class="aspect-square p-1 md:p-2 rounded-lg border transition-all {{ $day['isCurrentMonth'] ? 'border-slate-200 dark:border-slate-700/50' : 'border-transparent bg-slate-50 dark:bg-slate-900/50' }} {{ $day['isToday'] ? 'ring-2 ring-emerald-500' : '' }} {{ $isDebtFree ? 'bg-gradient-to-br from-emerald-400 to-cyan-500 dark:from-emerald-600 dark:to-cyan-600 border-emerald-500' : ($hasMilestone ? 'bg-cyan-50 dark:bg-cyan-900/20 border-cyan-300 dark:border-cyan-700' : ($hasAnyOverduePayment ? 'bg-rose-50 dark:bg-rose-900/20 border-rose-300 dark:border-rose-700' : ($hasAnyPaidPayment ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700' : ($hasPayment ? 'bg-slate-100 dark:bg-slate-800/50 border-slate-300 dark:border-slate-600' : '')))) }}"
                        >
                            <div class="flex flex-col h-full">
                                <div class="text-xs md:text-sm font-medium {{ $day['isCurrentMonth'] ? ($isDebtFree ? 'text-white' : 'text-slate-900 dark:text-white') : 'text-slate-400 dark:text-slate-600' }}">
                                    {{ $day['date']->day }}
                                </div>
                                <div class="flex-1 mt-1 space-y-0.5 md:space-y-1 overflow-hidden">
                                    @if ($isDebtFree)
                                        <div class="text-xs font-bold text-white truncate" title="{{ __('app.debt_free_day') }}">
                                            {{ __('app.debt_free_short') }}
                                        </div>
                                    @endif
                                    @if ($hasMilestone)
                                        @foreach ($this->milestones[$dateKey] as $milestone)
                                            <div class="text-xs font-medium text-cyan-700 dark:text-cyan-300 truncate" title="{{ __('app.paid_off') }}: {{ $milestone['debtName'] }}">
                                                {{ $milestone['debtName'] }}
                                            </div>
                                        @endforeach
                                    @endif
                                    @if ($hasPayment && !$isDebtFree)
                                        @foreach ($this->paymentEvents[$dateKey]['debts'] as $debt)
                                            @php
                                                $isPaid = $debt['isPaid'] ?? false;
                                                $isOverdue = $debt['isOverdue'] ?? false;
                                                $textColor = $isPaid ? 'text-emerald-700 dark:text-emerald-300' : ($isOverdue ? 'text-rose-700 dark:text-rose-300' : 'text-slate-700 dark:text-slate-300');
                                                $amountColor = $isPaid ? 'text-emerald-600 dark:text-emerald-400' : ($isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-slate-600 dark:text-slate-400');
                                                $statusText = $isPaid ? ' - ' . __('app.paid') : ($isOverdue ? ' - ' . __('app.payment_overdue') : '');
                                            @endphp
                                            <div
                                                @if (!$isPaid)
                                                    wire:click="openPaymentModal({{ $debt['debt_id'] }}, @js($debt['name']), {{ $debt['amount'] }}, {{ $debt['month_number'] }}, @js($debt['payment_month']))"
                                                    class="cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700/50 rounded px-1 -mx-1 transition-colors"
                                                    title="{{ __('app.click_to_register_payment') }}"
                                                @endif
                                            >
                                                <div class="flex items-center gap-1">
                                                    @if ($isPaid)
                                                        <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    @elseif ($isOverdue)
                                                        <svg class="w-3 h-3 text-rose-600 dark:text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        {{-- Calendar List - Mobile (shows only days with events) --}}
        <div class="sm:hidden p-3">
            <div class="space-y-2">
                @php
                    $hasAnyEvents = false;
                @endphp
                @foreach ($this->calendar as $week)
                    @foreach ($week as $day)
                        @php
                            $dateKey = $day['date']->format('Y-m-d');
                            $hasPayment = isset($this->paymentEvents[$dateKey]);
                            $hasMilestone = isset($this->milestones[$dateKey]);
                            $isDebtFree = $dateKey === $this->debtFreeDate;
                            $hasAnyPaidPayment = $hasPayment && collect($this->paymentEvents[$dateKey]['debts'])->contains('isPaid', true);
                            $hasAnyOverduePayment = $hasPayment && collect($this->paymentEvents[$dateKey]['debts'])->contains('isOverdue', true);
                            $showDay = $day['isCurrentMonth'] && ($hasPayment || $hasMilestone || $isDebtFree || $day['isToday']);
                            if ($showDay) $hasAnyEvents = true;
                        @endphp
                        @if ($showDay)
                            <div class="flex items-start gap-3 p-3 rounded-xl border {{ $day['isToday'] ? 'ring-2 ring-emerald-500' : '' }} {{ $isDebtFree ? 'bg-gradient-to-r from-emerald-400 to-cyan-500 border-emerald-500' : ($hasMilestone ? 'bg-cyan-50 dark:bg-cyan-900/20 border-cyan-300 dark:border-cyan-700' : ($hasAnyOverduePayment ? 'bg-rose-50 dark:bg-rose-900/20 border-rose-300 dark:border-rose-700' : ($hasAnyPaidPayment ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700' : ($hasPayment ? 'bg-slate-100 dark:bg-slate-800/50 border-slate-300 dark:border-slate-600' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700')))) }}">
                                {{-- Date Column --}}
                                <div class="flex-shrink-0 text-center w-12">
                                    <div class="font-display text-2xl font-bold {{ $isDebtFree ? 'text-white' : 'text-slate-900 dark:text-white' }}">
                                        {{ $day['date']->day }}
                                    </div>
                                    <div class="text-xs {{ $isDebtFree ? 'text-emerald-100' : 'text-slate-500 dark:text-slate-400' }}">
                                        {{ $day['date']->locale(app()->getLocale())->shortDayName }}
                                    </div>
                                </div>

                                {{-- Events Column --}}
                                <div class="flex-1 min-w-0">
                                    @if ($isDebtFree)
                                        <div class="font-bold text-white">
                                            {{ __('app.debt_free_day') }}
                                        </div>
                                    @endif
                                    @if ($hasMilestone)
                                        @foreach ($this->milestones[$dateKey] as $milestone)
                                            <div class="flex items-center gap-2 text-cyan-700 dark:text-cyan-300">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="font-medium truncate">{{ $milestone['debtName'] }} {{ __('app.paid_off') }}</span>
                                            </div>
                                        @endforeach
                                    @endif
                                    @if ($hasPayment && !$isDebtFree)
                                        @foreach ($this->paymentEvents[$dateKey]['debts'] as $debt)
                                            @php
                                                $isPaid = $debt['isPaid'] ?? false;
                                                $isOverdue = $debt['isOverdue'] ?? false;
                                                $textColor = $isPaid ? 'text-emerald-700 dark:text-emerald-300' : ($isOverdue ? 'text-rose-700 dark:text-rose-300' : 'text-slate-700 dark:text-slate-300');
                                            @endphp
                                            <div
                                                @if (!$isPaid)
                                                    wire:click="openPaymentModal({{ $debt['debt_id'] }}, @js($debt['name']), {{ $debt['amount'] }}, {{ $debt['month_number'] }}, @js($debt['payment_month']))"
                                                    title="{{ __('app.tap_to_register_payment') }}"
                                                @endif
                                                class="flex items-center justify-between gap-2 {{ $textColor }} {{ !$isPaid ? 'cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700/50 rounded-lg px-2 py-1 -mx-2 transition-colors' : '' }}"
                                            >
                                                <div class="flex items-center gap-2 min-w-0">
                                                    @if ($isPaid)
                                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    @elseif ($isOverdue)
                                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                        </svg>
                                                    @else
                                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    @endif
                                                    <span class="font-medium truncate">{{ $debt['name'] }}</span>
                                                </div>
                                                <span class="font-display font-semibold whitespace-nowrap">{{ number_format($debt['amount'], 0) }} kr</span>
                                            </div>
                                        @endforeach
                                    @endif
                                    @if ($day['isToday'] && !$hasPayment && !$hasMilestone && !$isDebtFree)
                                        <div class="text-slate-500 dark:text-slate-400 text-sm">
                                            {{ __('app.today') }} - {{ __('app.no_payments') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endforeach
                @if (!$hasAnyEvents)
                    <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                        {{ __('app.no_events_this_month') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Legend --}}
        <div class="border-t border-slate-200 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-800/30 px-3 sm:px-6 py-3 sm:py-4">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">
                {{ __('app.legend') }}
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg border-2 border-emerald-500 ring-2 ring-emerald-500"></div>
                    <span class="text-xs text-slate-600 dark:text-slate-400">{{ __('app.today') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-slate-800/50 border border-slate-300 dark:border-slate-600"></div>
                    <span class="text-xs text-slate-600 dark:text-slate-400">{{ __('app.payment_due') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-300 dark:border-emerald-700 flex items-center justify-center">
                        <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="text-xs text-slate-600 dark:text-slate-400">{{ __('app.payment_paid') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-rose-50 dark:bg-rose-900/20 border border-rose-300 dark:border-rose-700 flex items-center justify-center">
                        <svg class="w-3 h-3 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <span class="text-xs text-slate-600 dark:text-slate-400">{{ __('app.payment_overdue') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-300 dark:border-cyan-700"></div>
                    <span class="text-xs text-slate-600 dark:text-slate-400">{{ __('app.debt_paid_off') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-emerald-400 to-cyan-500 border border-emerald-500"></div>
                    <span class="text-xs text-slate-600 dark:text-slate-400">{{ __('app.debt_free_day') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- No Debts State --}}
    @if ($this->getDebts()->isEmpty())
        <div class="mt-8 premium-card rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <h3 class="font-display text-lg font-semibold text-slate-900 dark:text-white mb-2">
                {{ __('app.no_debts') }}
            </h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                {{ __('app.add_debts_to_see_calendar') }}
            </p>
            <a href="{{ route('debts') }}" wire:navigate class="btn-momentum inline-flex items-center px-6 py-3 rounded-xl font-medium transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                {{ __('app.add_first_debt') }}
            </a>
        </div>
    @endif

    {{-- Payment Modal --}}
    @if ($showPaymentModal)
        <x-modal wire:model="showPaymentModal" max-width="md">
            <form wire:submit="recordPayment">
                <x-modal.header :title="__('app.register_payment')" on-close="closePaymentModal" />

                <x-modal.body>
                    {{-- Debt Info Display --}}
                    <div class="bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-4">
                        <p class="text-sm text-slate-700 dark:text-slate-300">
                            <span class="font-medium">{{ __('app.debt') }}:</span> {{ $selectedDebtName }}
                        </p>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                            <span class="font-medium">{{ __('app.planned_amount') }}:</span> {{ number_format($plannedAmount, 0, ',', ' ') }} kr
                        </p>
                    </div>

                    <div class="space-y-4">
                        {{-- Amount Field --}}
                        @include('components.form.amount-input', [
                            'id' => 'payment-amount',
                            'label' => __('app.amount_kr_required'),
                            'model' => 'paymentAmount',
                            'required' => true,
                            'error' => $errors->first('paymentAmount'),
                        ])

                        {{-- Date Field --}}
                        @include('components.form.date-picker', [
                            'id' => 'payment-date',
                            'label' => __('app.payment_date_required'),
                            'model' => 'paymentDate',
                            'value' => $paymentDate,
                            'maxDate' => date('Y-m-d'),
                            'required' => true,
                            'error' => $errors->first('paymentDate'),
                        ])

                        {{-- Notes Field --}}
                        @include('components.form.textarea', [
                            'id' => 'payment-notes',
                            'label' => __('app.notes_optional_field'),
                            'model' => 'paymentNotes',
                            'placeholder' => __('app.notes_placeholder'),
                            'error' => $errors->first('paymentNotes'),
                        ])
                    </div>
                </x-modal.body>

                <x-modal.footer>
                    <x-modal.button-secondary wire:click="closePaymentModal">
                        {{ __('app.cancel') }}
                    </x-modal.button-secondary>
                    <x-modal.button-primary
                        type="submit"
                        :loading="true"
                        loading-target="recordPayment"
                    >
                        {{ __('app.register_payment') }}
                    </x-modal.button-primary>
                </x-modal.footer>
            </form>
        </x-modal>
    @endif

    {{-- YNAB Transaction Checker Modal --}}
    @if ($showYnabModal)
        <x-modal wire:model="showYnabModal" max-width="2xl">
            <x-modal.header on-close="closeYnabModal">
                <x-slot:title>
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{ __('app.check_ynab_transactions') }}
                    </span>
                </x-slot:title>
                <x-slot:actions>
                    <button
                        wire:click="checkYnabTransactions"
                        type="button"
                        class="cursor-pointer p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors"
                        title="{{ __('app.refresh') }}"
                    >
                        <svg class="h-5 w-5 {{ $isCheckingYnab ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </x-slot:actions>
            </x-modal.header>

            <x-modal.body class="max-h-[60vh] overflow-y-auto">
                {{-- Success Message --}}
                @if (session('ynab_import_success'))
                    <div class="mb-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-xl flex items-center gap-2">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ session('ynab_import_success') }}
                    </div>
                @endif

                {{-- Loading State --}}
                @if ($isCheckingYnab)
                    <div class="flex flex-col items-center justify-center py-12">
                        <svg class="w-12 h-12 text-cyan-500 animate-spin mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <p class="text-slate-600 dark:text-slate-400">{{ __('app.checking_ynab') }}</p>
                    </div>
                @elseif ($ynabError)
                    {{-- Error State --}}
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 px-4 py-3 rounded-xl">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <p>{{ $ynabError }}</p>
                        </div>
                    </div>
                @elseif (!empty($ynabDebtSummary))
                    {{-- Debt Summary --}}
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">{{ __('app.ynab_debt_status') }}</h4>
                        <div class="grid gap-2">
                            @foreach ($ynabDebtSummary as $summary)
                                <div class="flex items-center justify-between px-3 py-2 rounded-xl {{ $summary['status'] === 'all_matched' ? 'bg-emerald-50 dark:bg-emerald-900/20' : ($summary['status'] === 'has_issues' ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-slate-50 dark:bg-slate-800/50') }}">
                                    <span class="font-medium text-slate-900 dark:text-white">{{ $summary['debt_name'] }}</span>
                                    <div class="flex items-center gap-2">
                                        @if ($summary['status'] === 'all_matched')
                                            <span class="inline-flex items-center text-sm text-emerald-700 dark:text-emerald-300">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                {{ __('app.ynab_all_matched') }}
                                            </span>
                                        @elseif ($summary['status'] === 'has_issues')
                                            <span class="inline-flex items-center text-sm text-amber-700 dark:text-amber-300">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                                {{ __('app.ynab_needs_attention') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                </svg>
                                                {{ __('app.ynab_no_transactions') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if (!empty($ynabComparisonResults))
                    {{-- Results --}}
                    <div class="space-y-6">
                        @foreach ($ynabComparisonResults as $result)
                            <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                                <div class="bg-slate-50 dark:bg-slate-800/50 px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                                    <h4 class="font-medium text-slate-900 dark:text-white">
                                        {{ $result['debt_name'] }}
                                    </h4>
                                </div>
                                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                                    @foreach ($result['ynab_transactions'] as $tx)
                                        <div class="px-4 py-3 {{ $tx['status'] === 'missing' ? 'bg-amber-50 dark:bg-amber-900/10' : ($tx['status'] === 'mismatch' ? 'bg-rose-50 dark:bg-rose-900/10' : 'bg-emerald-50 dark:bg-emerald-900/10') }}">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        @if ($tx['status'] === 'matched')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-emerald-100 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                                </svg>
                                                                {{ __('app.transaction_matched') }}
                                                            </span>
                                                        @elseif ($tx['status'] === 'missing')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-amber-100 dark:bg-amber-800 text-amber-800 dark:text-amber-200">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                                </svg>
                                                                {{ __('app.transaction_missing') }}
                                                            </span>
                                                        @elseif ($tx['status'] === 'linkable')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-cyan-100 dark:bg-cyan-800 text-cyan-800 dark:text-cyan-200">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                                                </svg>
                                                                {{ __('app.transaction_linkable') }}
                                                            </span>
                                                        @elseif ($tx['status'] === 'mismatch')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-rose-100 dark:bg-rose-800 text-rose-800 dark:text-rose-200">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                </svg>
                                                                {{ __('app.transaction_mismatch') }}
                                                            </span>
                                                        @endif
                                                        <span class="text-sm text-slate-500 dark:text-slate-400">
                                                            {{ \Carbon\Carbon::parse($tx['date'])->format('d.m.Y') }}
                                                        </span>
                                                    </div>
                                                    <div class="font-display text-lg font-semibold text-slate-900 dark:text-white">
                                                        {{ __('app.ynab_amount') }}: {{ number_format($tx['amount'], 0, ',', ' ') }} kr
                                                    </div>
                                                    @if (($tx['status'] === 'mismatch' || $tx['status'] === 'linkable') && $tx['local_amount'])
                                                        <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                                            {{ __('app.local_amount') }}: {{ number_format($tx['local_amount'], 0, ',', ' ') }} kr
                                                            @if (isset($tx['local_date']))
                                                                ({{ \Carbon\Carbon::parse($tx['local_date'])->format('d.m.Y') }})
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if ($tx['memo'])
                                                        <div class="text-sm text-slate-500 dark:text-slate-500 mt-1 italic">
                                                            {{ $tx['memo'] }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-shrink-0">
                                                    @if ($tx['status'] === 'missing')
                                                        <button
                                                            wire:click="importYnabTransaction('{{ $tx['id'] }}', {{ $result['debt_id'] }})"
                                                            type="button"
                                                            class="cursor-pointer px-3 py-1.5 text-sm font-medium rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors"
                                                        >
                                                            {{ __('app.import_transaction') }}
                                                        </button>
                                                    @elseif ($tx['status'] === 'linkable')
                                                        <button
                                                            wire:click="linkYnabTransaction('{{ $tx['id'] }}', {{ $result['debt_id'] }})"
                                                            type="button"
                                                            class="cursor-pointer px-3 py-1.5 text-sm font-medium rounded-lg bg-cyan-500 text-white hover:bg-cyan-600 transition-colors"
                                                        >
                                                            {{ __('app.link_transaction') }}
                                                        </button>
                                                    @elseif ($tx['status'] === 'mismatch')
                                                        <button
                                                            wire:click="updatePaymentFromYnab('{{ $tx['id'] }}', {{ $tx['local_payment_id'] }}, {{ $tx['amount'] }}, '{{ $tx['date'] }}')"
                                                            type="button"
                                                            class="cursor-pointer px-3 py-1.5 text-sm font-medium rounded-lg bg-cyan-500 text-white hover:bg-cyan-600 transition-colors"
                                                        >
                                                            {{ __('app.sync_from_ynab') }}
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif
                @endif
            </x-modal.body>

            <x-modal.footer>
                <x-modal.button-secondary wire:click="closeYnabModal" class="w-full">
                    {{ __('app.close') }}
                </x-modal.button-secondary>
            </x-modal.footer>
        </x-modal>
    @endif
</div>
