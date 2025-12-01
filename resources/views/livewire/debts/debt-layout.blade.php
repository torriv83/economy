<div>
    {{-- Fixed Sidebar for Desktop --}}
    <aside class="hidden md:block fixed left-0 top-16 w-64 h-[calc(100vh-4rem)] bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm border-r border-slate-200/50 dark:border-slate-800/50 overflow-y-auto custom-scrollbar transition-colors duration-300">
        <nav class="p-5">
            {{-- Section Header --}}
            <div class="flex items-center gap-2 mb-4">
                <div class="w-2 h-2 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500"></div>
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('app.debts') }}</h2>
            </div>

            <div class="space-y-1">
                {{-- Overview --}}
                <button
                    wire:click="showOverview"
                    class="sidebar-link w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ in_array($currentView, ['overview', 'edit', 'detail']) ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ in_array($currentView, ['overview', 'edit', 'detail']) ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ in_array($currentView, ['overview', 'edit', 'detail']) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ __('app.overview') }}</span>
                    </div>
                </button>

                {{-- Add Debt --}}
                <button
                    wire:click="showCreate"
                    class="sidebar-link w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $currentView === 'create' ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $currentView === 'create' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ $currentView === 'create' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ __('app.add_debt') }}</span>
                    </div>
                </button>

                {{-- Progress --}}
                <button
                    wire:click="showProgress"
                    class="sidebar-link w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $currentView === 'progress' ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $currentView === 'progress' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ $currentView === 'progress' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ __('app.progress') }}</span>
                    </div>
                </button>

                {{-- Insights --}}
                <button
                    wire:click="showInsights"
                    class="sidebar-link w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $currentView === 'insights' ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $currentView === 'insights' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ $currentView === 'insights' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ __('app.insights') }}</span>
                    </div>
                </button>

                {{-- Reconciliation History --}}
                <button
                    wire:click="showReconciliations"
                    class="sidebar-link w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $currentView === 'reconciliations' ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $currentView === 'reconciliations' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ $currentView === 'reconciliations' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
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
    <div class="animate-fade-in-up">
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
