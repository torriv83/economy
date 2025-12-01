<div>
    {{-- Fixed Sidebar for Desktop --}}
    <aside class="hidden md:block fixed left-0 top-16 w-64 h-[calc(100vh-4rem)] bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm border-r border-slate-200/50 dark:border-slate-800/50 overflow-y-auto custom-scrollbar transition-colors duration-300">
        <nav class="p-5">
            {{-- Section Header --}}
            <div class="flex items-center gap-2 mb-4">
                <div class="w-2 h-2 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500"></div>
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('app.self_loans') }}</h2>
            </div>

            <div class="space-y-1">
                {{-- Overview --}}
                <button
                    wire:click="showOverview"
                    class="w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $currentView === 'overview' ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $currentView === 'overview' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ $currentView === 'overview' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ __('app.overview') }}</span>
                    </div>
                </button>

                {{-- New Loan --}}
                <button
                    wire:click="showCreate"
                    class="w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $currentView === 'create' ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $currentView === 'create' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ $currentView === 'create' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ __('app.new_loan') }}</span>
                    </div>
                </button>

                {{-- History --}}
                <button
                    wire:click="showHistory"
                    class="w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $currentView === 'history' ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $currentView === 'history' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ $currentView === 'history' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
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
    <div class="animate-fade-in-up">
        @if ($currentView === 'overview')
            <livewire:self-loans.overview />
        @elseif ($currentView === 'create')
            <livewire:self-loans.create-self-loan />
        @elseif ($currentView === 'history')
            <livewire:self-loans.history />
        @endif
    </div>
</div>
