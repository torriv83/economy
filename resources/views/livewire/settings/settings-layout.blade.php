<div>
    {{-- Fixed Sidebar for Desktop --}}
    <aside class="hidden md:block fixed left-0 top-16 w-64 h-[calc(100vh-4rem)] bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto transition-colors duration-200">
        <nav class="p-4">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">{{ __('app.settings') }}</h2>

            <div class="space-y-1">
                <button
                    wire:click="showPlan"
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer {{ $currentView === 'plan' ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="font-medium">{{ __('app.plan_settings') }}</span>
                    </div>
                </button>
                <button
                    wire:click="showDebt"
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer {{ $currentView === 'debt' ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span class="font-medium">{{ __('app.debt_settings') }}</span>
                    </div>
                </button>
                <button
                    wire:click="showYnab"
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer {{ $currentView === 'ynab' ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        <span class="font-medium">{{ __('app.ynab_settings') }}</span>
                    </div>
                </button>
                <button
                    wire:click="showShortcuts"
                    class="w-full text-left px-3 py-2 rounded-r-lg transition cursor-pointer {{ $currentView === 'shortcuts' ? 'border-l-3 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
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
    <div>
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
