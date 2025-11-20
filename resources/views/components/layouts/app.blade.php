<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'no' ? 'nb-NO' : app()->getLocale() }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches), mobileMenuOpen: false }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? __('app.app_name') . ' - Debt Management' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Skip to Content Link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-blue-600 dark:focus:bg-blue-500 focus:text-white focus:rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
        {{ __('app.skip_to_content') }}
    </a>

    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo/Brand -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 rounded">
                        {{ __('app.app_name') }}
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center gap-6">
                    <a href="{{ route('debts') }}" class="{{ request()->routeIs('home', 'debts', 'debts.edit') ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} font-medium transition focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 rounded px-3 py-2">
                        {{ __('app.debts') }}
                    </a>
                    <a href="{{ route('payoff') }}" class="{{ request()->routeIs('payoff') ? 'bg-green-600 dark:bg-green-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} font-medium transition focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:ring-offset-2 rounded px-3 py-2">
                        {{ __('app.payoff_planning') }}
                    </a>
                    <a href="{{ route('self-loans') }}" class="{{ request()->routeIs('self-loans') ? 'bg-teal-600 dark:bg-teal-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} font-medium transition focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2 rounded px-3 py-2">
                        {{ __('app.self_loans') }}
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
        >
            <div class="px-4 pt-2 pb-4 space-y-1">
                <a
                    href="{{ route('debts') }}"
                    @click="mobileMenuOpen = false"
                    class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->routeIs('home', 'debts', 'debts.edit') ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }} transition focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2"
                >
                    {{ __('app.debts') }}
                </a>
                <a
                    href="{{ route('payoff') }}"
                    @click="mobileMenuOpen = false"
                    class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->routeIs('payoff') ? 'bg-green-600 dark:bg-green-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }} transition focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:ring-offset-2"
                >
                    {{ __('app.payoff_planning') }}
                </a>
                <a
                    href="{{ route('self-loans') }}"
                    @click="mobileMenuOpen = false"
                    class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->routeIs('self-loans') ? 'bg-teal-600 dark:bg-teal-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }} transition focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:ring-offset-2"
                >
                    {{ __('app.self_loans') }}
                </a>

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

    <!-- Main Content -->
    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    @livewireScripts

    <script>
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, content, preventDefault }) => {
                if (status === 419) {
                    preventDefault()

                    if (confirm('Your session has expired. Refresh the page?')) {
                        window.location.reload()
                    }
                }
            })
        })
    </script>

    @stack('scripts')
</body>
</html>