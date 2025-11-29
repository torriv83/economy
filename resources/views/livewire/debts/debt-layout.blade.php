<div>
    {{-- Fixed Sidebar for Desktop --}}
    <aside class="hidden md:block fixed left-0 top-16 w-64 h-[calc(100vh-4rem)] bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto transition-colors duration-200">
        <nav class="p-4">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">{{ __('app.debts') }}</h2>

            <div class="space-y-1">
                <button
                    wire:click="showOverview"
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer {{ in_array($currentView, ['overview', 'edit', 'detail']) ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
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

                <button
                    wire:click="showInsights"
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer {{ $currentView === 'insights' ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <span class="font-medium">{{ __('app.insights') }}</span>
                    </div>
                </button>

                <button
                    wire:click="showReconciliations"
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer {{ $currentView === 'reconciliations' ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">{{ __('app.reconciliation_history') }}</span>
                    </div>
                </button>
            </div>
        </nav>
    </aside>

    {{-- Header (not for overview or detail - they render their own) --}}
    @if (!in_array($currentView, ['overview', 'detail']))
        <x-page-header
            :title="match($currentView) {
                'create' => __('app.create_debt'),
                'progress' => __('app.debt_progress'),
                'edit' => __('app.edit_debt'),
                'insights' => __('app.interest_insights'),
                'reconciliations' => __('app.reconciliation_history'),
                default => ''
            }"
            :subtitle="match($currentView) {
                'create' => __('app.create_debt_description'),
                'progress' => __('app.debt_progress_description'),
                'edit' => __('app.edit_debt_description'),
                'insights' => __('app.interest_insights_description'),
                'reconciliations' => __('app.reconciliation_history_description'),
                default => ''
            }"
        />
    @endif

    {{-- Main Content --}}
    <div>
        @if ($currentView === 'overview')
            <livewire:debt-list />
        @elseif ($currentView === 'create')
            <livewire:create-debt />
        @elseif ($currentView === 'progress')
            <livewire:debt-progress />
        @elseif ($currentView === 'detail' && $viewingDebt)
            <livewire:debts.debt-detail :key="'detail-' . $viewingDebtId" :debt="$viewingDebt" :embedded="true" />
        @elseif ($currentView === 'edit' && $editingDebt)
            <livewire:edit-debt :key="'edit-' . $editingDebtId" :debt="$editingDebt" />
        @elseif ($currentView === 'insights')
            <livewire:interest-insights />
        @elseif ($currentView === 'reconciliations')
            <livewire:reconciliation-history />
        @endif
    </div>
</div>
