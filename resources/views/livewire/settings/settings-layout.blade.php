<div>
    {{-- Fixed Sidebar for Desktop --}}
    <aside class="hidden md:block fixed left-0 top-16 w-64 h-[calc(100vh-4rem)] bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm border-r border-slate-200/50 dark:border-slate-800/50 overflow-y-auto custom-scrollbar transition-colors duration-300">
        <nav class="p-5">
            {{-- Section Header --}}
            <div class="flex items-center gap-2 mb-4">
                <div class="w-2 h-2 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500"></div>
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('app.settings') }}</h2>
            </div>

            <div class="space-y-1">
                {{-- Plan Settings --}}
                <button
                    wire:click="showPlan"
                    class="w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $currentView === 'plan' ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $currentView === 'plan' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ $currentView === 'plan' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ __('app.plan_settings') }}</span>
                    </div>
                </button>

                {{-- Debt Settings --}}
                <button
                    wire:click="showDebt"
                    class="w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $currentView === 'debt' ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $currentView === 'debt' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ $currentView === 'debt' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ __('app.debt_settings') }}</span>
                    </div>
                </button>

                {{-- YNAB Settings --}}
                <button
                    wire:click="showYnab"
                    class="w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $currentView === 'ynab' ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $currentView === 'ynab' ? 'bg-cyan-100 dark:bg-cyan-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ $currentView === 'ynab' ? 'text-cyan-600 dark:text-cyan-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ __('app.ynab_settings') }}</span>
                    </div>
                </button>

                {{-- Keyboard Shortcuts --}}
                <button
                    wire:click="showShortcuts"
                    class="w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $currentView === 'shortcuts' ? 'sidebar-link-active text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $currentView === 'shortcuts' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
                            <svg class="h-5 w-5 {{ $currentView === 'shortcuts' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ __('app.keyboard_shortcuts') }}</span>
                    </div>
                </button>
            </div>
        </nav>
    </aside>

    {{-- Header --}}
    <x-page-header
        :title="__('app.settings')"
        :subtitle="__('app.settings_description')"
    />

    {{-- Main Content --}}
    <div class="animate-fade-in-up">
        @if ($currentView === 'plan')
            <livewire:payoff.payoff-settings lazy />
        @elseif ($currentView === 'debt')
            <livewire:settings.debt-settings />
        @elseif ($currentView === 'ynab')
            <livewire:settings.ynab-settings />
        @elseif ($currentView === 'shortcuts')
            <livewire:settings.keyboard-shortcuts />
        @endif
    </div>
</div>
