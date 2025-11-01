<x-layouts.app>
    <!-- Hero Section -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ __('app.debt_overview') }}</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ __('app.track_debts') }}</p>
    </div>

    <!-- Total Debt Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8 transition-colors duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('app.total_debt') }}</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">0 kr</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('app.active_debts') }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">0</p>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center transition-colors duration-200">
        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('app.no_debts_yet') }}</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('app.get_started') }}</p>
        <a href="{{ route('debts.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('app.add_debt') }}
        </a>
    </div>
</x-layouts.app>