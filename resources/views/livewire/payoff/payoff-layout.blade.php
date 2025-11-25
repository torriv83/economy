<div>
    {{-- Fixed Sidebar for Desktop --}}
    <aside class="hidden md:block fixed left-0 top-16 w-64 h-[calc(100vh-4rem)] bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto transition-colors duration-200">
        <nav class="p-4">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">{{ __('app.payoff_planning') }}</h2>

            <div class="space-y-1">
                <button
                    wire:click="showCalendar"
                    class="w-full text-left px-3 py-2 rounded-lg transition cursor-pointer {{ $currentView === 'calendar' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="font-medium">{{ __('app.calendar') }}</span>
                    </div>
                </button>

                <button
                    wire:click="showPlan"
                    class="w-full text-left px-3 py-2 rounded-lg transition cursor-pointer {{ $currentView === 'plan' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span class="font-medium">{{ __('app.repayments') }}</span>
                    </div>
                </button>

                <button
                    wire:click="showStrategies"
                    class="w-full text-left px-3 py-2 rounded-lg transition cursor-pointer {{ $currentView === 'strategies' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="font-medium">{{ __('app.strategies') }}</span>
                    </div>
                </button>

                <button
                    wire:click="showSettings"
                    class="w-full text-left px-3 py-2 rounded-lg transition cursor-pointer {{ $currentView === 'settings' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="font-medium">{{ __('app.settings') }}</span>
                    </div>
                </button>
            </div>
        </nav>
    </aside>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            @if ($currentView === 'strategies')
                {{ __('app.payoff_strategies') }}
            @elseif ($currentView === 'plan')
                {{ __('app.payment_plan') }}
            @elseif ($currentView === 'calendar')
                {{ __('app.payoff_calendar') }}
            @elseif ($currentView === 'settings')
                {{ __('app.payoff_settings') }}
            @endif
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            @if ($currentView === 'strategies')
                {{ __('app.strategies_description') }}
            @elseif ($currentView === 'plan')
                {{ __('app.payment_plan_description') }}
            @elseif ($currentView === 'calendar')
                {{ __('app.calendar_description') }}
            @elseif ($currentView === 'settings')
                {{ __('app.payoff_settings_description') }}
            @endif
        </p>
    </div>

    {{-- Mobile Sidebar Navigation --}}
    <div class="md:hidden mb-6">
        <nav class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">{{ __('app.payoff_planning') }}</h2>

            <div class="space-y-1">
                <button
                    wire:click="showCalendar"
                    class="w-full text-left px-3 py-2 rounded-lg transition cursor-pointer {{ $currentView === 'calendar' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="font-medium">{{ __('app.calendar') }}</span>
                    </div>
                </button>

                <button
                    wire:click="showPlan"
                    class="w-full text-left px-3 py-2 rounded-lg transition cursor-pointer {{ $currentView === 'plan' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span class="font-medium">{{ __('app.repayments') }}</span>
                    </div>
                </button>

                <button
                    wire:click="showStrategies"
                    class="w-full text-left px-3 py-2 rounded-lg transition cursor-pointer {{ $currentView === 'strategies' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="font-medium">{{ __('app.strategies') }}</span>
                    </div>
                </button>

                <button
                    wire:click="showSettings"
                    class="w-full text-left px-3 py-2 rounded-lg transition cursor-pointer {{ $currentView === 'settings' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="font-medium">{{ __('app.settings') }}</span>
                    </div>
                </button>
            </div>
        </nav>
    </div>

    {{-- Main Content --}}
    <div>
        @if ($currentView === 'strategies')
            <livewire:strategy-comparison />
        @elseif ($currentView === 'plan')
            <livewire:payment-plan :extraPayment="$extraPayment" :strategy="$strategy" />
        @elseif ($currentView === 'calendar')
            <livewire:payoff-calendar :extraPayment="$extraPayment" :strategy="$strategy" />
        @elseif ($currentView === 'settings')
            <livewire:payoff.payoff-settings />
        @endif
    </div>
</div>
