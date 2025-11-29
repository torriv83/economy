<div>
    {{-- Success Message --}}
    @if (session('payment_recorded'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ session('payment_recorded') }}
        </div>
    @endif

    {{-- Countdown Widget (Prominent) --}}
    @if ($this->debtFreeDate && $this->countdown['months'] > 0)
        <div wire:key="countdown-{{ $strategy }}-{{ $extraPayment }}"
             class="bg-gradient-to-r from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-lg shadow-lg border border-green-400 dark:border-green-500 mb-6 sm:mb-8 transition-all duration-200"
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
            <div class="text-center">
                <button
                    type="button"
                    @click="showCountdown = !showCountdown"
                    class="w-full flex items-center justify-center gap-2 cursor-pointer group"
                    :class="showCountdown ? 'mb-4' : 'mb-0'"
                >
                    <h2 class="text-white text-lg font-medium">
                        {{ __('app.countdown_to_freedom') }}
                    </h2>
                    <svg
                        class="w-5 h-5 text-green-100 transition-transform duration-200 group-hover:text-white"
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
                            <div class="text-2xl sm:text-4xl md:text-6xl font-bold text-white" x-text="years"></div>
                            <div class="text-green-100 text-xs font-medium mt-1">{{ trans_choice('app.years', $this->countdown['years']) }}</div>
                        </div>
                    @endif
                    @if ($this->countdown['months'] > 0)
                        <div class="flex flex-col items-center min-w-0">
                            <div class="text-2xl sm:text-4xl md:text-6xl font-bold text-white" x-text="months"></div>
                            <div class="text-green-100 text-xs font-medium mt-1">{{ trans_choice('app.months', $this->countdown['months']) }}</div>
                        </div>
                    @endif
                    <div class="flex flex-col items-center min-w-0">
                        <div class="text-2xl sm:text-4xl md:text-6xl font-bold text-white" x-text="days"></div>
                        <div class="text-green-100 text-xs font-medium mt-1">{{ __('app.days') }}</div>
                    </div>
                    <div class="flex flex-col items-center min-w-0">
                        <div class="text-2xl sm:text-4xl md:text-6xl font-bold text-white" x-text="hours.toString().padStart(2, '0')"></div>
                        <div class="text-green-100 text-xs font-medium mt-1">{{ __('app.hours') }}</div>
                    </div>
                    <div class="flex flex-col items-center min-w-0">
                        <div class="text-2xl sm:text-4xl md:text-6xl font-bold text-white" x-text="minutes.toString().padStart(2, '0')"></div>
                        <div class="text-green-100 text-xs font-medium mt-1">{{ __('app.minutes') }}</div>
                    </div>
                    <div class="flex flex-col items-center min-w-0">
                        <div class="text-2xl sm:text-4xl md:text-6xl font-bold text-white" x-text="seconds.toString().padStart(2, '0')"></div>
                        <div class="text-green-100 text-xs font-medium mt-1">{{ __('app.seconds') }}</div>
                    </div>
                </div>
                <p class="text-green-100 text-sm">
                    {{ __('app.debt_free_by') }} {{ Carbon\Carbon::parse($this->debtFreeDate)->locale(app()->getLocale())->translatedFormat('j. F Y') }}
                    (<span x-text="totalDays"></span> {{ __('app.days') }})
                </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Ready to Assign from YNAB --}}
    <div class="mb-6">
        <livewire:ynab.ready-to-assign />
    </div>

    {{-- Calendar Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Calendar Header with Navigation --}}
        <div class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 px-3 sm:px-6 py-3 sm:py-4">
            {{-- Mobile Header (stacked) --}}
            <div class="flex flex-col gap-3 sm:hidden">
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

                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ $this->currentMonthName }}
                    </h2>

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
                <div class="flex items-center justify-center gap-2">
                    <select
                        wire:model.live="currentYear"
                        class="px-2 py-1 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                    >
                        @foreach ($this->availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                    <button
                        wire:click="goToToday"
                        type="button"
                        class="px-3 py-1 text-sm rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/60 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                    >
                        {{ __('app.today') }}
                    </button>
                    <button
                        wire:click="openYnabModal"
                        type="button"
                        class="px-3 py-1 text-sm rounded-lg bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/60 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{ __('app.check_ynab') }}
                    </button>
                </div>
            </div>

            {{-- Desktop Header (horizontal) --}}
            <div class="hidden sm:flex items-center justify-between">
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
                    <button
                        wire:click="openYnabModal"
                        type="button"
                        class="px-3 py-1 text-sm rounded-lg bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/60 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{ __('app.check_ynab') }}
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

        {{-- Calendar Grid - Desktop --}}
        <div class="hidden sm:block p-4 md:p-6">
            {{-- Day Names Header --}}
            <div class="grid grid-cols-7 gap-1 md:gap-2 mb-4">
                @foreach ([__('app.mon'), __('app.tue'), __('app.wed'), __('app.thu'), __('app.fri'), __('app.sat'), __('app.sun')] as $day)
                    <div class="text-center text-xs font-semibold text-gray-600 dark:text-gray-400 py-2">
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
                            class="aspect-square p-1 md:p-2 rounded-lg border transition-all {{ $day['isCurrentMonth'] ? 'border-gray-200 dark:border-gray-700' : 'border-transparent bg-gray-50 dark:bg-gray-900/50' }} {{ $day['isToday'] ? 'ring-2 ring-blue-500 dark:ring-blue-400' : '' }} {{ $isDebtFree ? 'bg-gradient-to-br from-green-400 to-green-500 dark:from-green-600 dark:to-green-700 border-green-500 dark:border-green-400' : ($hasMilestone ? 'bg-cyan-50 dark:bg-cyan-900/20 border-cyan-300 dark:border-cyan-700' : ($hasAnyOverduePayment ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700' : ($hasAnyPaidPayment ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700' : ($hasPayment ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : '')))) }}"
                        >
                            <div class="flex flex-col h-full">
                                <div class="text-xs md:text-sm font-medium {{ $day['isCurrentMonth'] ? ($isDebtFree ? 'text-white' : 'text-gray-900 dark:text-white') : 'text-gray-400 dark:text-gray-600' }}">
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
                                                $textColor = $isPaid ? 'text-green-700 dark:text-green-300' : ($isOverdue ? 'text-red-700 dark:text-red-300' : 'text-blue-700 dark:text-blue-300');
                                                $amountColor = $isPaid ? 'text-green-600 dark:text-green-400' : ($isOverdue ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400');
                                                $statusText = $isPaid ? ' - ' . __('app.paid') : ($isOverdue ? ' - ' . __('app.payment_overdue') : '');
                                            @endphp
                                            <div
                                                @if (!$isPaid)
                                                    wire:click="openPaymentModal({{ $debt['debt_id'] }}, @js($debt['name']), {{ $debt['amount'] }}, {{ $debt['month_number'] }}, @js($debt['payment_month']))"
                                                    class="cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-800/30 rounded px-1 -mx-1 transition-colors"
                                                    title="{{ __('app.click_to_register_payment') }}"
                                                @endif
                                            >
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
                            <div class="flex items-start gap-3 p-3 rounded-lg border {{ $day['isToday'] ? 'ring-2 ring-blue-500 dark:ring-blue-400' : '' }} {{ $isDebtFree ? 'bg-gradient-to-r from-green-400 to-green-500 dark:from-green-600 dark:to-green-700 border-green-500 dark:border-green-400' : ($hasMilestone ? 'bg-cyan-50 dark:bg-cyan-900/20 border-cyan-300 dark:border-cyan-700' : ($hasAnyOverduePayment ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700' : ($hasAnyPaidPayment ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700' : ($hasPayment ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700')))) }}">
                                {{-- Date Column --}}
                                <div class="flex-shrink-0 text-center w-12">
                                    <div class="text-2xl font-bold {{ $isDebtFree ? 'text-white' : 'text-gray-900 dark:text-white' }}">
                                        {{ $day['date']->day }}
                                    </div>
                                    <div class="text-xs {{ $isDebtFree ? 'text-green-100' : 'text-gray-500 dark:text-gray-400' }}">
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
                                                $textColor = $isPaid ? 'text-green-700 dark:text-green-300' : ($isOverdue ? 'text-red-700 dark:text-red-300' : 'text-blue-700 dark:text-blue-300');
                                            @endphp
                                            <div
                                                @if (!$isPaid)
                                                    wire:click="openPaymentModal({{ $debt['debt_id'] }}, @js($debt['name']), {{ $debt['amount'] }}, {{ $debt['month_number'] }}, @js($debt['payment_month']))"
                                                    class="cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-800/30 rounded-lg px-2 py-1 -mx-2 transition-colors"
                                                    title="{{ __('app.tap_to_register_payment') }}"
                                                @endif
                                                class="flex items-center justify-between gap-2 {{ $textColor }}"
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
                                                <span class="font-semibold whitespace-nowrap">{{ number_format($debt['amount'], 0) }} kr</span>
                                            </div>
                                        @endforeach
                                    @endif
                                    @if ($day['isToday'] && !$hasPayment && !$hasMilestone && !$isDebtFree)
                                        <div class="text-gray-500 dark:text-gray-400 text-sm">
                                            {{ __('app.today') }} - {{ __('app.no_payments') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endforeach
                @if (!$hasAnyEvents)
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        {{ __('app.no_events_this_month') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Legend --}}
        <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 px-3 sm:px-6 py-3 sm:py-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                {{ __('app.legend') }}
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-4">
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
                    <div class="w-6 h-6 rounded bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-300 dark:border-cyan-700"></div>
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

    {{-- Payment Modal --}}
    @if ($showPaymentModal)
        <x-modal wire:model="showPaymentModal" max-width="md">
            <form wire:submit="recordPayment">
                <x-modal.header :title="__('app.register_payment')" on-close="closePaymentModal" />

                <x-modal.body>
                    {{-- Debt Info Display --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <span class="font-medium">{{ __('app.debt') }}:</span> {{ $selectedDebtName }}
                        </p>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
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
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{ __('app.check_ynab_transactions') }}
                    </span>
                </x-slot:title>
                <x-slot:actions>
                    <button
                        wire:click="checkYnabTransactions"
                        type="button"
                        class="cursor-pointer p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
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
                    <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg flex items-center gap-2">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ session('ynab_import_success') }}
                    </div>
                @endif

                {{-- Loading State --}}
                @if ($isCheckingYnab)
                    <div class="flex flex-col items-center justify-center py-12">
                        <svg class="w-12 h-12 text-green-500 animate-spin mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400">{{ __('app.checking_ynab') }}</p>
                    </div>
                @elseif ($ynabError)
                    {{-- Error State --}}
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200 px-4 py-3 rounded-lg">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <p>{{ $ynabError }}</p>
                        </div>
                    </div>
                @elseif (!empty($ynabComparisonResults))
                    {{-- Results --}}
                    <div class="space-y-6">
                        @foreach ($ynabComparisonResults as $result)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ $result['debt_name'] }}
                                    </h4>
                                </div>
                                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($result['ynab_transactions'] as $tx)
                                        <div class="px-4 py-3 {{ $tx['status'] === 'missing' ? 'bg-yellow-50 dark:bg-yellow-900/10' : ($tx['status'] === 'mismatch' ? 'bg-red-50 dark:bg-red-900/10' : 'bg-green-50 dark:bg-green-900/10') }}">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        @if ($tx['status'] === 'matched')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                                </svg>
                                                                {{ __('app.transaction_matched') }}
                                                            </span>
                                                        @elseif ($tx['status'] === 'missing')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                                </svg>
                                                                {{ __('app.transaction_missing') }}
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-200">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                </svg>
                                                                {{ __('app.transaction_mismatch') }}
                                                            </span>
                                                        @endif
                                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                                            {{ \Carbon\Carbon::parse($tx['date'])->format('d.m.Y') }}
                                                        </span>
                                                    </div>
                                                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                                        {{ __('app.ynab_amount') }}: {{ number_format($tx['amount'], 0, ',', ' ') }} kr
                                                    </div>
                                                    @if ($tx['status'] === 'mismatch' && $tx['local_amount'])
                                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                            {{ __('app.local_amount') }}: {{ number_format($tx['local_amount'], 0, ',', ' ') }} kr
                                                        </div>
                                                    @endif
                                                    @if ($tx['memo'])
                                                        <div class="text-sm text-gray-500 dark:text-gray-500 mt-1 italic">
                                                            {{ $tx['memo'] }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-shrink-0">
                                                    @if ($tx['status'] === 'missing')
                                                        <button
                                                            wire:click="importYnabTransaction('{{ $tx['id'] }}', {{ $result['debt_id'] }})"
                                                            type="button"
                                                            class="cursor-pointer px-3 py-1.5 text-sm font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition-colors"
                                                        >
                                                            {{ __('app.import_transaction') }}
                                                        </button>
                                                    @elseif ($tx['status'] === 'mismatch')
                                                        <button
                                                            wire:click="updatePaymentFromYnab('{{ $tx['id'] }}', {{ $tx['local_payment_id'] }}, {{ $tx['amount'] }})"
                                                            type="button"
                                                            class="cursor-pointer px-3 py-1.5 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors"
                                                        >
                                                            {{ __('app.update_amount') }}
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
            </x-modal.body>

            <x-modal.footer>
                <x-modal.button-secondary wire:click="closeYnabModal" class="w-full">
                    {{ __('app.close') }}
                </x-modal.button-secondary>
            </x-modal.footer>
        </x-modal>
    @endif
</div>
