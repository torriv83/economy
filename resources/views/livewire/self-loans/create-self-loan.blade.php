<div wire:init="loadData">
    @if ($isLoading)
        <div class="animate-pulse space-y-5 premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6 max-w-2xl">
            {{-- Name Field Skeleton --}}
            <div>
                <div class="h-4 w-20 bg-slate-200 dark:bg-slate-700 rounded mb-2"></div>
                <div class="h-12 w-full bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
            </div>

            {{-- Description Field Skeleton --}}
            <div>
                <div class="h-4 w-32 bg-slate-200 dark:bg-slate-700 rounded mb-2"></div>
                <div class="h-24 w-full bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
            </div>

            {{-- Amount Field Skeleton --}}
            <div>
                <div class="h-4 w-24 bg-slate-200 dark:bg-slate-700 rounded mb-2"></div>
                <div class="h-12 w-full bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
            </div>

            {{-- Submit Button Skeleton --}}
            <div class="pt-3">
                <div class="h-12 w-full bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
            </div>
        </div>
    @else
        @if (session('message'))
            <div class="mb-6 premium-card rounded-xl border border-emerald-200 dark:border-emerald-800/50 px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ session('message') }}</p>
                </div>
            </div>
        @endif

        <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6 max-w-2xl">
            <form wire:submit.prevent="createLoan">
            <div class="space-y-5">
                {{-- Name Field --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        {{ __('app.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        wire:model="name"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                        required>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description Field --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        {{ __('app.description_optional') }}
                    </label>
                    <textarea
                        id="description"
                        wire:model="description"
                        rows="3"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-colors resize-none"
                    ></textarea>
                    @error('description')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Amount Field --}}
                <div>
                    <label for="amount" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        {{ __('app.amount_kr') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            id="amount"
                            wire:model="amount"
                            step="0.01"
                            min="0.01"
                            class="w-full px-4 py-3 pr-14 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                            required>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                            <span class="text-slate-500 dark:text-slate-400 text-sm font-medium">NOK</span>
                        </div>
                    </div>
                    @error('amount')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- YNAB Connection (only if YNAB is configured) --}}
                @if ($this->isYnabConfigured)
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                            {{ __('app.link_to_ynab_optional') }}
                        </label>

                        <div class="space-y-3">
                            {{-- No connection --}}
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 dark:has-[:checked]:bg-emerald-900/20">
                                <input type="radio" wire:model.live="ynabConnectionType" value="none" class="text-emerald-500 focus:ring-emerald-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('app.no_ynab_connection') }}</span>
                            </label>

                            {{-- Account connection --}}
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 dark:has-[:checked]:bg-emerald-900/20">
                                <input type="radio" wire:model.live="ynabConnectionType" value="account" class="text-emerald-500 focus:ring-emerald-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('app.link_to_ynab_account') }}</span>
                            </label>

                            @if ($ynabConnectionType === 'account')
                                <div class="ml-7">
                                    <select
                                        wire:model="ynabAccountId"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-colors">
                                        <option value="">{{ __('app.select_account') }}</option>
                                        @foreach ($this->ynabAccounts as $account)
                                            <option value="{{ $account['id'] }}">
                                                {{ $account['name'] }} ({{ number_format($account['balance'], 0, ',', ' ') }} kr)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Category connection --}}
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 dark:has-[:checked]:bg-emerald-900/20">
                                <input type="radio" wire:model.live="ynabConnectionType" value="category" class="text-emerald-500 focus:ring-emerald-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('app.link_to_ynab_category') }}</span>
                            </label>

                            @if ($ynabConnectionType === 'category')
                                <div class="ml-7">
                                    <select
                                        wire:model="ynabCategoryId"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-colors">
                                        <option value="">{{ __('app.select_category') }}</option>
                                        @foreach ($this->ynabCategories as $category)
                                            <option value="{{ $category['id'] }}">
                                                {{ $category['group_name'] }}: {{ $category['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>

                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{ __('app.ynab_connection_help') }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-8">
                <button
                    type="submit"
                    class="w-full btn-momentum px-6 py-3 text-base font-semibold rounded-xl transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    {{ __('app.create_self_loan') }}
                </button>
            </div>
            </form>
        </div>
    @endif
</div>
