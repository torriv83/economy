<div>
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
</div>
