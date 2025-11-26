<div>
    {{-- Fixed Sidebar for Desktop --}}
    <aside class="hidden md:block fixed left-0 top-16 w-64 h-[calc(100vh-4rem)] bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto transition-colors duration-200">
        <nav class="p-4">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">{{ __('app.debts') }}</h2>

            <div class="space-y-1">
                <button
                    wire:click="showOverview"
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer {{ $currentView === 'overview' ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span class="font-medium">{{ __('app.overview') }}</span>
                    </div>
                </button>

                <button
                    wire:click="showCreate"
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer {{ $currentView === 'create' ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="font-medium">{{ __('app.add_debt') }}</span>
                    </div>
                </button>

                <button
                    wire:click="showProgress"
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer {{ $currentView === 'progress' ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="font-medium">{{ __('app.progress') }}</span>
                    </div>
                </button>

                <a
                    href="{{ route('reconciliations') }}"
                    wire:navigate.hover
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer block {{ request()->routeIs('reconciliations') ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">{{ __('app.reconciliation_history') }}</span>
                    </div>
                </a>
            </div>
        </nav>
    </aside>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-2 break-words">
            @if ($currentView === 'overview')
                {{ __('app.debts_overview') }}
            @elseif ($currentView === 'create')
                {{ __('app.create_debt') }}
            @elseif ($currentView === 'progress')
                {{ __('app.debt_progress') }}
            @endif
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            @if ($currentView === 'overview')
                {{ __('app.debts_overview_description') }}
            @elseif ($currentView === 'create')
                {{ __('app.create_debt_description') }}
            @elseif ($currentView === 'progress')
                {{ __('app.debt_progress_description') }}
            @endif
        </p>
    </div>

    {{-- Main Content --}}
    <div>
        @if ($currentView === 'overview')
            <livewire:debt-list />
        @elseif ($currentView === 'create')
            <livewire:create-debt />
        @elseif ($currentView === 'progress')
            <livewire:debt-progress />
        @endif
    </div>
</div>
