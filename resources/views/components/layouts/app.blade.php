<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'no' ? 'nb-NO' : app()->getLocale() }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches), mobileMenuOpen: false }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? __('app.app_name') . ' - Debt Management' }}</title>
    <meta name="description" content="{{ __('app.app_description', ['default' => 'Track your debts and plan your path to financial freedom']) }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">

    {{-- PWA --}}
    <link rel="manifest" href="/build/manifest.webmanifest">
    <meta name="theme-color" content="#10b981">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
    {{-- SVG Gradient Definitions (for progress rings) --}}
    <svg width="0" height="0" class="absolute">
        <defs>
            <linearGradient id="momentum-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:rgb(16 185 129)" />
                <stop offset="100%" style="stop-color:rgb(6 182 212)" />
            </linearGradient>
        </defs>
    </svg>

    {{-- Skip to Content Link --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[100] focus:px-4 focus:py-2 focus:bg-emerald-600 focus:text-white focus:rounded-lg focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
        {{ __('app.skip_to_content') }}
    </a>

    {{-- Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200/50 dark:border-slate-800/50 transition-colors duration-300">
        <div class="flex items-center h-16">
            {{-- Logo/Brand --}}
            <div class="flex items-center justify-center {{ request()->routeIs('home', 'debts', 'debts.edit', 'payoff', 'self-loans', 'settings') ? 'px-4 md:w-64' : 'px-4 sm:px-6 lg:px-8' }} flex-shrink-0">
                <a href="{{ route('home') }}" wire:navigate.hover class="flex items-center gap-3 group focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 rounded-lg">
                    {{-- Animated Logo --}}
                    <div class="relative">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-cyan-500 flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:shadow-emerald-500/40 transition-shadow duration-300">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div class="absolute -inset-1 rounded-xl bg-gradient-to-br from-emerald-500 to-cyan-500 opacity-0 group-hover:opacity-20 blur transition-opacity duration-300"></div>
                    </div>
                    <span class="text-lg font-display font-bold text-slate-900 dark:text-white tracking-tight hidden sm:block">
                        {{ __('app.app_name') }}
                    </span>
                </a>
            </div>

            {{-- Navigation Links --}}
            <div class="flex-1 flex justify-between items-center px-4 sm:px-6 lg:px-8">
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('debts') }}" wire:navigate.hover class="nav-link px-4 py-2 rounded-lg font-medium text-sm transition-colors {{ request()->routeIs('home', 'debts', 'debts.edit') ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                        {{ __('app.debts') }}
                    </a>
                    <a href="{{ route('payoff') }}" wire:navigate.hover class="nav-link px-4 py-2 rounded-lg font-medium text-sm transition-colors {{ request()->routeIs('payoff') ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                        {{ __('app.payoff_planning') }}
                    </a>
                    <a href="{{ route('self-loans') }}" wire:navigate.hover class="nav-link px-4 py-2 rounded-lg font-medium text-sm transition-colors {{ request()->routeIs('self-loans') ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                        {{ __('app.self_loans') }}
                    </a>
                </div>

                {{-- Right side controls --}}
                <div class="hidden md:flex items-center gap-2 ml-auto">
                    {{-- Settings Link --}}
                    <a href="{{ route('settings') }}" wire:navigate.hover aria-label="{{ __('app.settings') }}" class="p-2.5 rounded-xl transition-colors {{ request()->routeIs('settings') ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </a>

                    {{-- Logout Button --}}
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" aria-label="{{ __('app.logout') }}" class="p-2.5 rounded-xl text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </form>

                    {{-- Divider --}}
                    <div class="w-px h-6 bg-slate-200 dark:bg-slate-700 mx-1"></div>

                    {{-- Dark Mode Toggle --}}
                    <button @click="darkMode = !darkMode" aria-label="{{ __('app.toggle_dark_mode') }}" class="p-2.5 rounded-xl text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                        <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </button>

                    {{-- Language Switcher --}}
                    <div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-xl p-1">
                        <a href="{{ route('locale.switch', 'en') }}" aria-label="{{ __('app.switch_to_english') }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ app()->getLocale() === 'en' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                            EN
                        </a>
                        <a href="{{ route('locale.switch', 'no') }}" aria-label="{{ __('app.switch_to_norwegian') }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ app()->getLocale() === 'no' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                            NO
                        </a>
                    </div>
                </div>

                {{-- Mobile Menu Button --}}
                <div class="md:hidden flex items-center gap-2">
                    {{-- Dark Mode Toggle (Mobile) --}}
                    <button @click="darkMode = !darkMode" aria-label="{{ __('app.toggle_dark_mode') }}" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                        <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        aria-label="{{ __('app.toggle_menu') }}"
                        :aria-expanded="mobileMenuOpen.toString()"
                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        <svg x-show="!mobileMenuOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <svg x-show="mobileMenuOpen" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Menu --}}
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
            class="md:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900"
            x-cloak
            x-data="{
                debtsOpen: {{ request()->routeIs('home', 'debts', 'debts.edit') ? 'true' : 'false' }},
                payoffOpen: {{ request()->routeIs('payoff') ? 'true' : 'false' }},
                selfLoansOpen: {{ request()->routeIs('self-loans') ? 'true' : 'false' }},
                settingsOpen: {{ request()->routeIs('settings') ? 'true' : 'false' }}
            }"
        >
            @php
                $payoffView = request()->query('view', 'calendar');
                $selfLoansView = request()->query('view', 'overview');
                $settingsView = request()->query('view', 'plan');
            @endphp
            <div class="px-4 pt-2 pb-4 space-y-1">
                {{-- Debts Section --}}
                <div>
                    <button
                        type="button"
                        @click="debtsOpen = !debtsOpen"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-base font-medium {{ request()->routeIs('home', 'debts', 'debts.edit') ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }} transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        <span>{{ __('app.debts') }}</span>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': debtsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    @php
                        $debtsView = request()->query('view', 'overview');
                    @endphp
                    <div x-show="debtsOpen" x-collapse class="pl-4 space-y-1 mt-1">
                        <a href="{{ route('debts', ['view' => 'overview']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('debts') && $debtsView === 'overview' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.overview') }}
                        </a>
                        <a href="{{ route('debts', ['view' => 'create']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('debts') && $debtsView === 'create' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.add_debt') }}
                        </a>
                        <a href="{{ route('debts', ['view' => 'pay']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('debts') && $debtsView === 'pay' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.pay_debt') }}
                        </a>
                        <a href="{{ route('debts', ['view' => 'progress']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('debts') && $debtsView === 'progress' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.progress') }}
                        </a>
                        <a href="{{ route('debts', ['view' => 'recommendations']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('debts') && $debtsView === 'recommendations' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.recommendations') }}
                        </a>
                        <a href="{{ route('debts', ['view' => 'insights']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('debts') && $debtsView === 'insights' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.insights') }}
                        </a>
                        <a href="{{ route('debts', ['view' => 'reconciliations']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('debts') && $debtsView === 'reconciliations' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.reconciliation_history') }}
                        </a>
                    </div>
                </div>

                {{-- Payoff Planning Section --}}
                <div>
                    <button
                        type="button"
                        @click="payoffOpen = !payoffOpen"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-base font-medium {{ request()->routeIs('payoff') ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }} transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        <span>{{ __('app.payoff_planning') }}</span>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': payoffOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="payoffOpen" x-collapse class="pl-4 space-y-1 mt-1">
                        <a href="{{ route('payoff', ['view' => 'calendar']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('payoff') && $payoffView === 'calendar' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.calendar') }}
                        </a>
                        <a href="{{ route('payoff', ['view' => 'plan']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('payoff') && $payoffView === 'plan' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.repayments') }}
                        </a>
                        <a href="{{ route('payoff', ['view' => 'strategies']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('payoff') && $payoffView === 'strategies' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.strategies') }}
                        </a>
                    </div>
                </div>

                {{-- Self Loans Section --}}
                <div>
                    <button
                        type="button"
                        @click="selfLoansOpen = !selfLoansOpen"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-base font-medium {{ request()->routeIs('self-loans') ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }} transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        <span>{{ __('app.self_loans') }}</span>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': selfLoansOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="selfLoansOpen" x-collapse class="pl-4 space-y-1 mt-1">
                        <a href="{{ route('self-loans', ['view' => 'overview']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('self-loans') && $selfLoansView === 'overview' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.overview') }}
                        </a>
                        <a href="{{ route('self-loans', ['view' => 'create']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('self-loans') && $selfLoansView === 'create' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.new_loan') }}
                        </a>
                        <a href="{{ route('self-loans', ['view' => 'history']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('self-loans') && $selfLoansView === 'history' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.repayment_history') }}
                        </a>
                    </div>
                </div>

                {{-- Settings Section --}}
                <div>
                    <button
                        type="button"
                        @click="settingsOpen = !settingsOpen"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-base font-medium {{ request()->routeIs('settings') ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }} transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        <span>{{ __('app.settings') }}</span>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': settingsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="settingsOpen" x-collapse class="pl-4 space-y-1 mt-1">
                        <a href="{{ route('settings', ['view' => 'plan']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('settings') && $settingsView === 'plan' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.plan_settings') }}
                        </a>
                        <a href="{{ route('settings', ['view' => 'debt']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('settings') && $settingsView === 'debt' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.debt_settings') }}
                        </a>
                        <a href="{{ route('settings', ['view' => 'ynab']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('settings') && $settingsView === 'ynab' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.ynab_settings') }}
                        </a>
                        <a href="{{ route('settings', ['view' => 'recommendations']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('settings') && $settingsView === 'recommendations' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.recommendation_settings') }}
                        </a>
                        <a href="{{ route('settings', ['view' => 'shortcuts']) }}" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('settings') && $settingsView === 'shortcuts' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            {{ __('app.keyboard_shortcuts') }}
                        </a>
                    </div>
                </div>

                {{-- Language Switcher (Mobile) --}}
                <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between px-4">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('app.language') }}</span>
                        <div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
                            <a
                                href="{{ route('locale.switch', 'en') }}"
                                @click="mobileMenuOpen = false"
                                aria-label="{{ __('app.switch_to_english') }}"
                                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-all {{ app()->getLocale() === 'en' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                            >
                                EN
                            </a>
                            <a
                                href="{{ route('locale.switch', 'no') }}"
                                @click="mobileMenuOpen = false"
                                aria-label="{{ __('app.switch_to_norwegian') }}"
                                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-all {{ app()->getLocale() === 'no' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                            >
                                NO
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Logout (Mobile) --}}
                <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" @click="mobileMenuOpen = false" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-base font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                            <span>{{ __('app.logout') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main id="main-content" class="{{ request()->routeIs('home', 'debts', 'debts.edit', 'payoff', 'self-loans', 'settings') ? 'md:ml-64' : '' }} pt-24 px-4 sm:px-6 lg:px-8 pb-24 md:pb-8">
        {{ $slot }}
    </main>

    {{-- Mobile Bottom Navigation --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border-t border-slate-200/50 dark:border-slate-800/50 safe-area-inset-bottom">
        <div class="flex items-center justify-around h-16 px-2">
            {{-- Gjeld --}}
            <a
                href="{{ route('debts') }}"
                wire:navigate.hover
                class="flex flex-col items-center justify-center flex-1 py-2 rounded-lg transition-colors {{ request()->routeIs('home', 'debts', 'debts.edit') && request()->query('view', 'overview') !== 'pay' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}"
            >
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                </svg>
                <span class="text-xs font-medium mt-1">{{ __('app.debts') }}</span>
            </a>

            {{-- Betal --}}
            <a
                href="{{ route('debts', ['view' => 'pay']) }}"
                wire:navigate.hover
                class="flex flex-col items-center justify-center flex-1 py-2 rounded-lg transition-colors {{ request()->routeIs('debts') && request()->query('view') === 'pay' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}"
            >
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-xs font-medium mt-1">{{ __('app.pay') }}</span>
            </a>

            {{-- Plan --}}
            <a
                href="{{ route('payoff') }}"
                wire:navigate.hover
                class="flex flex-col items-center justify-center flex-1 py-2 rounded-lg transition-colors {{ request()->routeIs('payoff') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}"
            >
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                <span class="text-xs font-medium mt-1">{{ __('app.plan') }}</span>
            </a>

            {{-- Innstillinger --}}
            <a
                href="{{ route('settings') }}"
                wire:navigate.hover
                class="flex flex-col items-center justify-center flex-1 py-2 rounded-lg transition-colors {{ request()->routeIs('settings') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}"
            >
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-xs font-medium mt-1">{{ __('app.settings') }}</span>
            </a>
        </div>
    </nav>

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
