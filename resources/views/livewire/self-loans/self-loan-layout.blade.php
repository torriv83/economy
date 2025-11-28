<div>
    {{-- Fixed Sidebar for Desktop --}}
    <aside class="hidden md:block fixed left-0 top-16 w-64 h-[calc(100vh-4rem)] bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto transition-colors duration-200">
        <nav class="p-4">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">{{ __('app.self_loans') }}</h2>

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
                        <span class="font-medium">{{ __('app.new_loan') }}</span>
                    </div>
                </button>

                <button
                    wire:click="showHistory"
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer {{ $currentView === 'history' ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
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

    {{-- Header --}}
    <x-page-header
        :title="match($currentView) {
            'overview' => __('app.self_loans_overview'),
            'create' => __('app.new_loan'),
            'history' => __('app.repayment_history'),
            default => ''
        }"
        :subtitle="match($currentView) {
            'overview' => __('app.track_self_loans'),
            'create' => __('app.create_new_self_loan_description'),
            'history' => __('app.view_repayment_history_description'),
            default => ''
        }"
    />

    {{-- Main Content --}}
    <div>
        @if ($currentView === 'overview')
            <livewire:self-loans.overview />
        @elseif ($currentView === 'create')
            <livewire:self-loans.create-self-loan />
        @elseif ($currentView === 'history')
            <livewire:self-loans.history />
        @endif
    </div>
</div>
