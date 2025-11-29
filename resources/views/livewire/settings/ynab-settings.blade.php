<div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('app.ynab_settings') }}</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('app.ynab_settings_description') }}</p>
                </div>
                {{-- YNAB Logo --}}
                <div class="shrink-0">
                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-6">
            {{-- Enable/Disable Toggle --}}
            <div class="flex items-center justify-between">
                <div>
                    <label for="ynabEnabled" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('app.ynab_enable_integration') }}
                    </label>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('app.ynab_enable_description') }}</p>
                </div>
                <button
                    type="button"
                    id="ynabEnabled"
                    wire:click="$toggle('ynabEnabled')"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 {{ $ynabEnabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600' }}"
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
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-6">
                    {{-- Status Indicator --}}
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('app.ynab_status') }}:</span>
                        @if($isConfigured)
                            @if($connectionStatus === true)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ __('app.ynab_connected') }}
                                </span>
                            @elseif($connectionStatus === false)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ __('app.ynab_connection_failed') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ __('app.ynab_configured') }}
                                </span>
                            @endif
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ __('app.ynab_not_configured_status') }}
                            </span>
                        @endif
                    </div>

                    {{-- API Token Field --}}
                    <div>
                        <label for="token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('app.ynab_api_token') }}
                        </label>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('app.ynab_api_token_help') }}</p>
                        <div class="mt-2">
                            <input
                                type="password"
                                id="token"
                                wire:model="token"
                                placeholder="{{ $isConfigured ? '••••••••••••••••' : __('app.ynab_api_token_placeholder') }}"
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                            >
                        </div>
                        @error('token')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Budget ID Field --}}
                    <div>
                        <label for="budgetId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('app.ynab_budget_id') }}
                        </label>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('app.ynab_budget_id_help') }}</p>
                        <div class="mt-2">
                            <input
                                type="text"
                                id="budgetId"
                                wire:model="budgetId"
                                placeholder="{{ __('app.ynab_budget_id_placeholder') }}"
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all font-mono text-sm"
                            >
                        </div>
                        @error('budgetId')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Help Link --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex">
                            <div class="shrink-0">
                                <svg class="h-5 w-5 text-blue-400 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ __('app.ynab_where_to_find') }}</h3>
                                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                                    {{ __('app.ynab_api_token_location') }}
                                    <a href="https://app.ynab.com/settings/developer" target="_blank" rel="noopener noreferrer" class="underline hover:no-underline font-medium">
                                        {{ __('app.ynab_developer_settings') }}
                                    </a>
                                </p>
                                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                                    {{ __('app.ynab_budget_id_location') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        {{-- Save Credentials Button --}}
                        <button
                            type="button"
                            wire:click="saveCredentials"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg wire:loading.remove wire:target="saveCredentials" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
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
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg wire:loading.remove wire:target="testConnection" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
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
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-700 dark:text-red-300 bg-white dark:bg-gray-700 border border-red-300 dark:border-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors cursor-pointer"
                            >
                                <svg wire:loading.remove wire:target="clearCredentials" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                        <div class="pt-6 border-t border-gray-200 dark:border-gray-700 space-y-4">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('app.ynab_background_sync') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.ynab_background_sync_description') }}</p>

                            {{-- Background Sync Toggle --}}
                            <div class="flex items-center justify-between">
                                <div>
                                    <label for="backgroundSyncEnabled" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('app.ynab_enable_background_sync') }}
                                    </label>
                                </div>
                                <button
                                    type="button"
                                    id="backgroundSyncEnabled"
                                    wire:click="$toggle('backgroundSyncEnabled')"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 {{ $backgroundSyncEnabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600' }}"
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
                                <div>
                                    <label for="backgroundSyncInterval" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('app.ynab_sync_interval') }}
                                    </label>
                                    <select
                                        id="backgroundSyncInterval"
                                        wire:model.live="backgroundSyncInterval"
                                        class="mt-2 w-full sm:w-48 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all"
                                    >
                                        @foreach($intervalOptions as $interval)
                                            <option value="{{ $interval }}">{{ $interval }} {{ __('app.minutes') }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Last Sync Info --}}
                                @if($lastSyncAt)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('app.ynab_last_sync') }}: {{ $lastSyncAt }}
                                    </p>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            @else
                {{-- Disabled State --}}
                <div class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.ynab_disabled_message') }}</p>
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
        class="fixed bottom-4 right-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg shadow-lg"
        style="display: none;"
    >
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm font-medium" x-text="message"></span>
        </div>
    </div>
</div>
