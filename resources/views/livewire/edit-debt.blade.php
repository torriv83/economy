<div>
    <!-- Header -->
    <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('app.edit_debt') }}</h1>
                <a href="{{ route('home') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                    {{ __('app.cancel') }}
                </a>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-colors duration-200">
            <form wire:submit="update" class="p-6 sm:p-8">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Debt Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('app.debt_name') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            wire:model.blur="name"
                            placeholder="{{ __('app.debt_name_placeholder') }}"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors duration-200 @error('name') border-red-500 dark:border-red-400 @enderror"
                        >
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Balance -->
                    <div>
                        <label for="balance" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('app.balance') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                id="balance"
                                wire:model.blur="balance"
                                step="0.01"
                                min="0"
                                placeholder="{{ __('app.balance_placeholder') }}"
                                class="w-full px-4 py-3 pr-14 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors duration-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none @error('balance') border-red-500 dark:border-red-400 @enderror"
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">NOK</span>
                            </div>
                        </div>
                        @error('balance')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Interest Rate -->
                    <div>
                        <label for="interestRate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('app.interest_rate') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                id="interestRate"
                                wire:model.blur="interestRate"
                                step="0.01"
                                min="0"
                                max="100"
                                placeholder="{{ __('app.interest_rate_placeholder') }}"
                                class="w-full px-4 py-3 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors duration-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none @error('interestRate') border-red-500 dark:border-red-400 @enderror"
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">%</span>
                            </div>
                        </div>
                        @error('interestRate')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Minimum Payment -->
                    <div>
                        <label for="minimumPayment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('app.minimum_payment') }}
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                id="minimumPayment"
                                wire:model.blur="minimumPayment"
                                step="0.01"
                                min="0"
                                placeholder="{{ __('app.minimum_payment_placeholder') }}"
                                class="w-full px-4 py-3 pr-14 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors duration-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none @error('minimumPayment') border-red-500 dark:border-red-400 @enderror"
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">NOK</span>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('app.minimum_payment_help') }}</p>
                        @error('minimumPayment')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                    >
                        <span wire:loading.remove>{{ __('app.update_debt') }}</span>
                        <span wire:loading class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('app.updating') }}
                        </span>
                    </button>
                    <a
                        href="{{ route('home') }}"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors duration-200"
                    >
                        {{ __('app.cancel') }}
                    </a>
                </div>
            </form>
        </div>
</div>
