<div>
    {{-- Header - Full Width --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            @if ($currentView === 'overview')
                {{ __('app.self_loans_overview') }}
            @elseif ($currentView === 'create')
                {{ __('app.new_loan') }}
            @elseif ($currentView === 'history')
                {{ __('app.repayment_history') }}
            @endif
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            @if ($currentView === 'overview')
                {{ __('app.track_self_loans') }}
            @elseif ($currentView === 'create')
                {{ __('app.create_new_self_loan_description') }}
            @elseif ($currentView === 'history')
                {{ __('app.view_repayment_history_description') }}
            @endif
        </p>
    </div>

    {{-- Sidebar + Content Layout --}}
    <div class="flex flex-col md:flex-row gap-6">
        {{-- Sidebar --}}
        <aside class="w-full md:w-64 flex-shrink-0">
            <nav class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('app.self_loans') }}</h2>

                <div class="space-y-1">
                    <button
                        wire:click="showOverview"
                        class="w-full text-left px-3 py-2 rounded-lg transition cursor-pointer {{ $currentView === 'overview' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="font-medium">{{ __('app.overview') }}</span>
                        </div>
                    </button>

                    <button
                        wire:click="showCreate"
                        class="w-full text-left px-3 py-2 rounded-lg transition cursor-pointer {{ $currentView === 'create' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="font-medium">{{ __('app.new_loan') }}</span>
                        </div>
                    </button>

                    <button
                        wire:click="showHistory"
                        class="w-full text-left px-3 py-2 rounded-lg transition cursor-pointer {{ $currentView === 'history' ? 'bg-blue-600 dark:bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium">{{ __('app.repayment_history') }}</span>
                        </div>
                    </button>
                </div>
            </nav>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 min-w-0">
            @if ($currentView === 'overview')
                <livewire:self-loans.overview />
            @elseif ($currentView === 'create')
                <livewire:self-loans.create-self-loan />
            @elseif ($currentView === 'history')
                <livewire:self-loans.history />
            @endif
        </div>
    </div>
</div>
