<div>
    <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50">
        {{-- Header --}}
        <div class="p-6 border-b border-slate-200/50 dark:border-slate-700/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-display font-semibold text-slate-900 dark:text-white">
                        {{ __('app.buffer_settings') }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('app.buffer_settings_description') }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- Info Box --}}
            <div
                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200/50 dark:border-blue-800/50 rounded-xl p-4">
                <div class="flex gap-3">
                    <div class="shrink-0">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                            <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200">
                            {{ __('app.buffer_settings_info_title') }}</h3>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            {{ __('app.buffer_settings_info_description') }}</p>
                    </div>
                </div>
            </div>

            {{-- Emergency Buffer Target Amount --}}
            <div class="space-y-2">
                <label for="bufferTargetAmount" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ __('app.buffer_target_amount') }}
                </label>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.buffer_target_amount_help') }}</p>
                <div class="mt-2 relative">
                    <input type="number" id="bufferTargetAmount" wire:model.live.debounce.500ms="bufferTargetAmount"
                        min="0" step="1000"
                        class="w-full px-4 py-3 pr-14 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span class="text-slate-400 dark:text-slate-500 text-sm font-medium">NOK</span>
                    </div>
                </div>
                @error('bufferTargetAmount')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Buffer Categories --}}
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ __('app.buffer_categories') }}
                    </label>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.buffer_categories_help') }}</p>
                </div>

                {{-- Invalid Categories Warning --}}
                @if (count($this->invalidCategories) > 0)
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200/50 dark:border-amber-800/50 rounded-xl p-4">
                        <div class="flex gap-3">
                            <div class="shrink-0">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                                    <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">{{ __('app.invalid_categories_warning') }}</h3>
                                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                    {{ __('app.invalid_categories_description', ['categories' => implode(', ', $this->invalidCategories)]) }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Categories List --}}
                @if (count($categories) > 0)
                    <div class="space-y-3">
                        @foreach ($categories as $index => $category)
                            <div wire:key="category-{{ $index }}"
                                class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border {{ in_array($category['name'], $this->invalidCategories) ? 'border-amber-300 dark:border-amber-700' : 'border-slate-200/50 dark:border-slate-700/50' }}">
                                {{-- Category name - full width --}}
                                <div class="mb-3">
                                    @if ($this->isYnabConfigured && count($this->ynabCategories) > 0)
                                        <select wire:change="updateCategoryName({{ $index }}, $event.target.value)"
                                            class="w-full px-3 py-2 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-sm font-medium text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 rounded-lg transition-colors">
                                            @foreach ($this->ynabCategories as $ynabCat)
                                                <option value="{{ $ynabCat['name'] }}" @selected($category['name'] === $ynabCat['name'])>
                                                    {{ $ynabCat['group_name'] }}: {{ $ynabCat['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="text-sm font-medium text-slate-900 dark:text-white px-3 py-2 block">
                                            {{ $category['name'] }}
                                        </span>
                                    @endif
                                </div>
                                {{-- Target amount and delete button --}}
                                <div class="flex items-center gap-3">
                                    <div class="relative flex-1">
                                        <input type="number"
                                            wire:change="updateCategoryTarget({{ $index }}, $event.target.value)"
                                            value="{{ $category['target'] }}" min="0" step="100"
                                            class="w-full px-3 py-2 pr-12 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <span class="text-slate-400 dark:text-slate-500 text-xs font-medium">NOK</span>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="removeCategory({{ $index }})"
                                        class="p-2 text-slate-400 hover:text-red-500 dark:text-slate-500 dark:hover:text-red-400 transition-colors cursor-pointer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-sm text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200/50 dark:border-slate-700/50">
                        {{ __('app.no_categories') }}
                    </div>
                @endif

                {{-- Add Category Form --}}
                <div class="pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                    <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                        {{ __('app.add_category') }}
                    </h4>
                    @if ($this->isYnabConfigured && count($this->ynabCategories) > 0)
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="w-full sm:w-64">
                                <select wire:model="newCategoryName"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors">
                                    <option value="">{{ __('app.select_ynab_category') }}</option>
                                    @foreach ($this->ynabCategories as $category)
                                        <option value="{{ $category['name'] }}">
                                            {{ $category['group_name'] }}: {{ $category['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('newCategoryName')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="relative w-full sm:w-40">
                                <input type="number" wire:model="newCategoryTarget"
                                    placeholder="{{ __('app.target_amount') }}" min="0" step="100"
                                    class="w-full px-4 py-3 pr-12 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                    <span class="text-slate-400 dark:text-slate-500 text-xs font-medium">NOK</span>
                                </div>
                                @error('newCategoryTarget')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="button" wire:click="addCategory" wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-all cursor-pointer">
                                <svg wire:loading.remove wire:target="addCategory" class="w-5 h-5 sm:mr-2" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                <svg wire:loading wire:target="addCategory" class="animate-spin w-5 h-5 sm:mr-2"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span class="hidden sm:inline">{{ __('app.add_category') }}</span>
                            </button>
                        </div>
                    @else
                        <div class="p-4 text-center text-sm text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200/50 dark:border-amber-800/50">
                            {{ __('app.ynab_required_for_categories') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Reset to Defaults Button --}}
            <div class="pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                <button type="button" wire:click="resetToDefaults" wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-all cursor-pointer">
                    <svg wire:loading.remove wire:target="resetToDefaults" class="w-4 h-4 mr-2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <svg wire:loading wire:target="resetToDefaults" class="animate-spin w-4 h-4 mr-2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    {{ __('app.reset_to_defaults') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Success Flash Message --}}
    <div x-data="{ show: false }"
        x-on:buffer-settings-saved.window="show = true; setTimeout(() => show = false, 3000)"
        x-on:buffer-settings-reset.window="show = true; setTimeout(() => show = false, 3000)" x-show="show"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-4 right-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-xl shadow-lg"
        style="display: none;">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ __('app.settings_saved') }}</span>
        </div>
    </div>
</div>
