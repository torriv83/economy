<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'no' ? 'nb-NO' : app()->getLocale() }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches), mobileMenuOpen: false }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? __('app.app_name') . ' - Debt Management' }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Skip to Content Link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-blue-600 dark:focus:bg-blue-500 focus:text-white focus:rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
        {{ __('app.skip_to_content') }}
    </a>

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
        <div class="flex items-center h-16">
            <!-- Logo/Brand - Fixed width on left to align with sidebar (responsive) -->
            <div class="flex items-center justify-center {{ request()->routeIs('home', 'debts', 'debts.edit', 'payoff', 'self-loans', 'settings') ? 'px-4 md:w-64' : 'px-4 sm:px-6 lg:px-8' }} flex-shrink-0">
                <a href="{{ route('home') }}" wire:navigate.hover class="flex items-center gap-2 text-base sm:text-xl font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 rounded">
                    <x-app-logo class="w-6 h-6 sm:w-7 sm:h-7 shrink-0" />
                    <span class="truncate">{{ __('app.app_name') }}</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 flex justify-between items-center px-4 sm:px-6 lg:px-8">
                <div class="hidden md:flex items-center gap-6">
                    <a href="{{ route('debts') }}" wire:navigate.hover class="{{ request()->routeIs('home', 'debts', 'debts.edit') ? 'border-b-2 border-blue-600 dark:border-blue-400 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} font-medium transition focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 rounded px-3 py-2">
                        {{ __('app.debts') }}
                    </a>
                    <a href="{{ route('payoff') }}" wire:navigate.hover class="{{ request()->routeIs('payoff') ? 'border-b-2 border-blue-600 dark:border-blue-400 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} font-medium transition focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 rounded px-3 py-2">
                        {{ __('app.payoff_planning') }}
                    </a>
                    <a href="{{ route('self-loans') }}" wire:navigate.hover class="{{ request()->routeIs('self-loans') ? 'border-b-2 border-blue-600 dark:border-blue-400 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} font-medium transition focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 rounded px-3 py-2">
                        {{ __('app.self_loans') }}
                    </a>
                </div>

                <!-- Settings, Dark Mode Toggle and Language Switcher -->
                <div class="hidden md:flex items-center gap-4 ml-auto">
                    <!-- Settings Link -->
                    <a href="{{ route('settings') }}" wire:navigate.hover aria-label="{{ __('app.settings') }}" class="p-2 rounded-lg {{ request()->routeIs('settings') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </a>

                    <!-- Dark Mode Toggle -->
                    <button @click="darkMode = !darkMode" aria-label="{{ __('app.toggle_dark_mode') }}" class="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>

                    <!-- Language Switcher -->
                    <div class="flex items-center gap-2">
                        <a href="{{ route('locale.switch', 'en') }}" aria-label="{{ __('app.switch_to_english') }}" class="px-2 py-1 text-sm rounded {{ app()->getLocale() === 'en' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }} transition focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                            EN
                        </a>
                        <a href="{{ route('locale.switch', 'no') }}" aria-label="{{ __('app.switch_to_norwegian') }}" class="px-2 py-1 text-sm rounded {{ app()->getLocale() === 'no' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }} transition focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                            NO
                        </a>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center gap-2">
                    <!-- Dark Mode Toggle (Mobile) -->
                    <button @click="darkMode = !darkMode" aria-label="{{ __('app.toggle_dark_mode') }}" class="p-2 rounded-lg text-gray-700 dark:text-gray-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        aria-label="{{ __('app.toggle_menu') }}"
                        :aria-expanded="mobileMenuOpen.toString()"
                        class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 rounded-lg p-2"
                    >
                        <svg x-show="!mobileMenuOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg x-show="mobileMenuOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            @click.outside="mobileMenuOpen = false"
            @keydown.escape.window="mobileMenuOpen = false"
            class="md:hidden border-t border-gray-200 dark:border-gray-700"
            x-cloak
            x-data="{
                debtsOpen: {{ request()->routeIs('home', 'debts', 'debts.edit') ? 'true' : 'false' }},
                payoffOpen: {{ request()->routeIs('payoff') ? 'true' : 'false' }},
                selfLoansOpen: {{ request()->routeIs('self-loans') ? 'true' : 'false' }}
            }"
        >
            @php
                $payoffView = request()->query('view', 'calendar');
                $selfLoansView = request()->query('view', 'overview');
            @endphp
            <div class="px-4 pt-2 pb-4 space-y-1">
                {{-- Debts Section --}}
                <div>
                    <button
                        type="button"
                        @click="debtsOpen = !debtsOpen"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-r-lg text-base font-medium {{ request()->routeIs('home', 'debts', 'debts.edit') ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }} transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2"
                    >
                        <span>{{ __('app.debts') }}</span>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': debtsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="debtsOpen" x-collapse class="pl-4 space-y-1 mt-1">
                        <a href="{{ route('debts') }}" wire:navigate.hover @click="mobileMenuOpen = false; Livewire.dispatch('setView', { view: 'overview' })" class="block px-3 py-1.5 text-sm rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            {{ __('app.overview') }}
                        </a>
                        <a href="{{ route('debts') }}" wire:navigate.hover @click="mobileMenuOpen = false; Livewire.dispatch('setView', { view: 'create' })" class="block px-3 py-1.5 text-sm rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            {{ __('app.add_debt') }}
                        </a>
                        <a href="{{ route('debts') }}" wire:navigate.hover @click="mobileMenuOpen = false; Livewire.dispatch('setView', { view: 'progress' })" class="block px-3 py-1.5 text-sm rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            {{ __('app.progress') }}
                        </a>
                        <a href="{{ route('debts') }}" wire:navigate.hover @click="mobileMenuOpen = false; Livewire.dispatch('setView', { view: 'insights' })" class="block px-3 py-1.5 text-sm rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            {{ __('app.insights') }}
                        </a>
                        <a href="{{ route('debts') }}" wire:navigate.hover @click="mobileMenuOpen = false; Livewire.dispatch('setView', { view: 'reconciliations' })" class="block px-3 py-1.5 text-sm rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            {{ __('app.reconciliation_history') }}
                        </a>
                    </div>
                </div>

                {{-- Payoff Planning Section --}}
                <div>
                    <button
                        type="button"
                        @click="payoffOpen = !payoffOpen"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-r-lg text-base font-medium {{ request()->routeIs('payoff') ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }} transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2"
                    >
                        <span>{{ __('app.payoff_planning') }}</span>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': payoffOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="payoffOpen" x-collapse class="pl-4 space-y-1 mt-1">
                        <a href="{{ route('payoff', ['view' => 'calendar']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-1.5 text-sm rounded-lg {{ request()->routeIs('payoff') && $payoffView === 'calendar' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            {{ __('app.calendar') }}
                        </a>
                        <a href="{{ route('payoff', ['view' => 'plan']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-1.5 text-sm rounded-lg {{ request()->routeIs('payoff') && $payoffView === 'plan' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            {{ __('app.repayments') }}
                        </a>
                        <a href="{{ route('payoff', ['view' => 'strategies']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-1.5 text-sm rounded-lg {{ request()->routeIs('payoff') && $payoffView === 'strategies' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            {{ __('app.strategies') }}
                        </a>
                    </div>
                </div>

                {{-- Self Loans Section --}}
                <div>
                    <button
                        type="button"
                        @click="selfLoansOpen = !selfLoansOpen"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-r-lg text-base font-medium {{ request()->routeIs('self-loans') ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }} transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2"
                    >
                        <span>{{ __('app.self_loans') }}</span>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': selfLoansOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="selfLoansOpen" x-collapse class="pl-4 space-y-1 mt-1">
                        <a href="{{ route('self-loans', ['view' => 'overview']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-1.5 text-sm rounded-lg {{ request()->routeIs('self-loans') && $selfLoansView === 'overview' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            {{ __('app.overview') }}
                        </a>
                        <a href="{{ route('self-loans', ['view' => 'create']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-1.5 text-sm rounded-lg {{ request()->routeIs('self-loans') && $selfLoansView === 'create' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            {{ __('app.new_loan') }}
                        </a>
                        <a href="{{ route('self-loans', ['view' => 'history']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-1.5 text-sm rounded-lg {{ request()->routeIs('self-loans') && $selfLoansView === 'history' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            {{ __('app.repayment_history') }}
                        </a>
                    </div>
                </div>

                {{-- Settings Link --}}
                <div class="pt-2">
                    <a href="{{ route('settings') }}" wire:navigate.hover @click="mobileMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-lg text-base font-medium {{ request()->routeIs('settings') ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }} transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>{{ __('app.settings') }}</span>
                    </a>
                </div>

                <!-- Language Switcher (Mobile) -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2 px-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('app.language') }}:</span>
                        <a
                            href="{{ route('locale.switch', 'en') }}"
                            @click="mobileMenuOpen = false"
                            aria-label="{{ __('app.switch_to_english') }}"
                            class="px-3 py-1 text-sm rounded {{ app()->getLocale() === 'en' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }} transition focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
                        >
                            EN
                        </a>
                        <a
                            href="{{ route('locale.switch', 'no') }}"
                            @click="mobileMenuOpen = false"
                            aria-label="{{ __('app.switch_to_norwegian') }}"
                            class="px-3 py-1 text-sm rounded {{ app()->getLocale() === 'no' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }} transition focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
                        >
                            NO
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content with Sidebar Space -->
    <main id="main-content" class="{{ request()->routeIs('home', 'debts', 'debts.edit', 'payoff', 'self-loans', 'settings') ? 'md:ml-64' : '' }} pt-24 px-4 sm:px-6 lg:px-8 pb-8">
        {{ $slot }}
    </main>

    @livewireScriptConfig

    <script>
        // Restore dark mode after Livewire SPA navigation
        document.addEventListener('livewire:navigated', () => {
            const darkMode = localStorage.getItem('darkMode') === 'true' ||
                (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', darkMode);
        });

    </script>

    @stack('scripts')
</body>
</html>