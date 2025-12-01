<div>
    <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50">
        {{-- Header --}}
        <div class="p-6 border-b border-slate-200/50 dark:border-slate-700/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-display font-semibold text-slate-900 dark:text-white">{{ __('app.ynab_settings') }}</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.ynab_settings_description') }}</p>
                    </div>
                </div>
                {{-- YNAB Logo Badge --}}
                <div class="shrink-0">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-cyan-600 flex items-center justify-center shadow-lg shadow-cyan-500/20">
                        <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- Enable/Disable Toggle --}}
            <div class="flex items-center justify-between">
                <div>
                    <label for="ynabEnabled" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ __('app.ynab_enable_integration') }}
                    </label>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('app.ynab_enable_description') }}</p>
                </div>
                <button
                    type="button"
                    id="ynabEnabled"
                    wire:click="$toggle('ynabEnabled')"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 {{ $ynabEnabled ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}"
                    role="switch"
                    aria-checked="{{ $ynabEnabled ? 'true' : 'false' }}"
                >
                    <span
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $ynabEnabled ? 'translate-x-5' : 'translate-x-0' }}"
                    ></span>
                </button>
            </div>

            @if($ynabEnabled)
                {{-- Configuration Section --}}
                <div class="pt-4 border-t border-slate-200/50 dark:border-slate-700/50 space-y-6">
                    {{-- Status Indicator --}}
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('app.ynab_status') }}:</span>
                        @if($isConfigured)
                            @if($connectionStatus === true)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ __('app.ynab_connected') }}
                                </span>
                            @elseif($connectionStatus === false)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    {{ __('app.ynab_connection_failed') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    {{ __('app.ynab_configured') }}
                                </span>
                            @endif
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                {{ __('app.ynab_not_configured_status') }}
                            </span>
                        @endif
                    </div>

                    {{-- API Token Field --}}
                    <div class="space-y-2">
                        <label for="token" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ __('app.ynab_api_token') }}
                        </label>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.ynab_api_token_help') }}</p>
                        <div class="mt-2">
                            <input
                                type="password"
                                id="token"
                                wire:model="token"
                                placeholder="{{ $isConfigured ? '••••••••••••••••' : __('app.ynab_api_token_placeholder') }}"
                                class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-all"
                            >
                        </div>
                        @error('token')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Budget ID Field --}}
                    <div class="space-y-2">
                        <label for="budgetId" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ __('app.ynab_budget_id') }}
                        </label>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.ynab_budget_id_help') }}</p>
                        <div class="mt-2">
                            <input
                                type="text"
                                id="budgetId"
                                wire:model="budgetId"
                                placeholder="{{ __('app.ynab_budget_id_placeholder') }}"
                                class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-all font-mono text-sm"
                            >
                        </div>
                        @error('budgetId')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Help Link - YNAB branded info box --}}
                    <div class="bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200/50 dark:border-cyan-800/50 rounded-xl p-4">
                        <div class="flex gap-3">
                            <div class="shrink-0">
                                <div class="w-8 h-8 rounded-lg bg-cyan-100 dark:bg-cyan-900/40 flex items-center justify-center">
                                    <svg class="h-4 w-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-cyan-800 dark:text-cyan-200">{{ __('app.ynab_where_to_find') }}</h3>
                                <p class="mt-1 text-sm text-cyan-700 dark:text-cyan-300">
                                    {{ __('app.ynab_api_token_location') }}
                                    <a href="https://app.ynab.com/settings/developer" target="_blank" rel="noopener noreferrer" class="underline hover:no-underline font-medium">
                                        {{ __('app.ynab_developer_settings') }}
                                    </a>
                                </p>
                                <p class="mt-1 text-sm text-cyan-700 dark:text-cyan-300">
                                    {{ __('app.ynab_budget_id_location') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                        {{-- Save Credentials Button --}}
                        <button
                            type="button"
                            wire:click="saveCredentials"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="btn-momentum inline-flex items-center px-5 py-2.5 text-sm font-semibold rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg wire:loading.remove wire:target="saveCredentials" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg wire:loading wire:target="saveCredentials" class="animate-spin w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('app.ynab_save_credentials') }}
                        </button>

                        {{-- Test Connection Button --}}
                        <button
                            type="button"
                            wire:click="testConnection"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            wire:target="testConnection"
                            @if(!$isConfigured) disabled @endif
                            class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg wire:loading.remove wire:target="testConnection" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
                            </svg>
                            <svg wire:loading wire:target="testConnection" class="animate-spin w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('app.ynab_test_connection') }}
                        </button>

                        {{-- Clear Credentials Button --}}
                        @if($isConfigured)
                            <button
                                type="button"
                                wire:click="clearCredentials"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                wire:confirm="{{ __('app.ynab_clear_confirm') }}"
                                class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/30 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-all cursor-pointer"
                            >
                                <svg wire:loading.remove wire:target="clearCredentials" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                <svg wire:loading wire:target="clearCredentials" class="animate-spin w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('app.ynab_clear_credentials') }}
                            </button>
                        @endif
                    </div>

                    {{-- Background Sync Section --}}
                    @if($isConfigured)
                        <div class="premium-card rounded-xl border border-slate-200/50 dark:border-slate-700/50 p-5 mt-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-8 h-8 rounded-lg bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-display font-semibold text-slate-900 dark:text-white">{{ __('app.ynab_background_sync') }}</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.ynab_background_sync_description') }}</p>
                                </div>
                            </div>

                            {{-- Background Sync Toggle --}}
                            <div class="flex items-center justify-between py-3 border-t border-slate-200/50 dark:border-slate-700/50">
                                <div>
                                    <label for="backgroundSyncEnabled" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ __('app.ynab_enable_background_sync') }}
                                    </label>
                                </div>
                                <button
                                    type="button"
                                    id="backgroundSyncEnabled"
                                    wire:click="$toggle('backgroundSyncEnabled')"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 {{ $backgroundSyncEnabled ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}"
                                    role="switch"
                                    aria-checked="{{ $backgroundSyncEnabled ? 'true' : 'false' }}"
                                >
                                    <span
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $backgroundSyncEnabled ? 'translate-x-5' : 'translate-x-0' }}"
                                    ></span>
                                </button>
                            </div>

                            {{-- Sync Interval Selector --}}
                            @if($backgroundSyncEnabled)
                                <div class="pt-3 border-t border-slate-200/50 dark:border-slate-700/50 space-y-3">
                                    <label for="backgroundSyncInterval" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ __('app.ynab_sync_interval') }}
                                    </label>
                                    <select
                                        id="backgroundSyncInterval"
                                        wire:model.live="backgroundSyncInterval"
                                        class="w-full sm:w-48 px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-all"
                                    >
                                        @foreach($intervalOptions as $interval)
                                            <option value="{{ $interval }}">{{ $interval }} {{ __('app.minutes') }}</option>
                                        @endforeach
                                    </select>

                                    {{-- Last Sync Info --}}
                                    @if($lastSyncAt)
                                        <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ __('app.ynab_last_sync') }}: {{ $lastSyncAt }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                {{-- Disabled State --}}
                <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('app.ynab_disabled_message') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Success Flash Messages --}}
    <div
        x-data="{ show: false, message: '' }"
        x-on:ynab-settings-saved.window="message = '{{ __('app.ynab_settings_saved') }}'; show = true; setTimeout(() => show = false, 3000)"
        x-on:ynab-credentials-saved.window="message = '{{ __('app.ynab_credentials_saved') }}'; show = true; setTimeout(() => show = false, 3000)"
        x-on:ynab-credentials-cleared.window="message = '{{ __('app.ynab_credentials_cleared') }}'; show = true; setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-4 right-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-xl shadow-lg"
        style="display: none;"
    >
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium" x-text="message"></span>
        </div>
    </div>
</div>
