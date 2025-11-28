<div>
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

                    <!-- Original Balance (Read-only) -->
                    <div>
                        <label for="originalBalance" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('app.original_balance') }}
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                id="originalBalance"
                                value="{{ number_format($debt->original_balance ?? 0, 2, ',', ' ') }}"
                                disabled
                                readonly
                                class="w-full px-4 py-3 pr-14 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 cursor-not-allowed transition-colors duration-200"
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">NOK</span>
                            </div>
                        </div>
                        @if($this->debt?->created_at)
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('app.added_on') }}: {{ $this->debt->created_at->locale('nb')->translatedFormat('d. F Y') }}
                            </p>
                        @endif
                    </div>

                    <!-- Balance (Read-only - Calculated) -->
                    <div>
                        <label for="balance" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('app.balance') }}
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                id="balance"
                                value="{{ number_format($debt->balance ?? 0, 2, ',', ' ') }}"
                                disabled
                                readonly
                                class="w-full px-4 py-3 pr-14 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 cursor-not-allowed transition-colors duration-200"
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">NOK</span>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('app.balance_calculated_info') }}
                        </p>
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

                    <!-- Debt Type -->
                    <div x-data="debtTypeCalculator({
                        type: @entangle('type'),
                        balance: {{ $debt->balance ?? 0 }},
                        interestRate: @entangle('interestRate')
                    })">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('app.debt_type') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-4">
                            <label class="flex-1 relative flex items-center p-4 rounded-lg border-2 cursor-pointer transition-all duration-200"
                                   :class="type === 'kredittkort' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'">
                                <input
                                    type="radio"
                                    wire:model.live="type"
                                    value="kredittkort"
                                    class="sr-only"
                                >
                                <div class="flex items-center gap-3 w-full">
                                    <div class="flex items-center justify-center w-5 h-5 rounded-full border-2 shrink-0"
                                         :class="type === 'kredittkort' ? 'border-blue-500' : 'border-gray-300 dark:border-gray-600'">
                                        <div x-show="type === 'kredittkort'" class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ __('app.kredittkort') }}
                                    </span>
                                </div>
                            </label>
                            <label class="flex-1 relative flex items-center p-4 rounded-lg border-2 cursor-pointer transition-all duration-200"
                                   :class="type === 'forbrukslån' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'">
                                <input
                                    type="radio"
                                    wire:model.live="type"
                                    value="forbrukslån"
                                    class="sr-only"
                                >
                                <div class="flex items-center gap-3 w-full">
                                    <div class="flex items-center justify-center w-5 h-5 rounded-full border-2 shrink-0"
                                         :class="type === 'forbrukslån' ? 'border-blue-500' : 'border-gray-300 dark:border-gray-600'">
                                        <div x-show="type === 'forbrukslån'" class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ __('app.forbrukslån') }}
                                    </span>
                                </div>
                            </label>
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-show="type === 'kredittkort'">
                            {{ __('app.minimum_payment_help_kredittkort') }}
                            <span x-show="calculatedMinimum > 0" class="font-medium text-gray-700 dark:text-gray-300">
                                <br>{{ __('app.calculated_minimum_required') }}: <span x-text="formatCurrency(calculatedMinimum)"></span> kr
                            </span>
                        </p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-show="type === 'forbrukslån'">
                            {{ __('app.minimum_payment_help_forbrukslån') }}
                            <span x-show="calculatedMinimum > 0" class="font-medium text-gray-700 dark:text-gray-300">
                                <br>{{ __('app.calculated_minimum_required') }}: <span x-text="formatCurrency(calculatedMinimum)"></span> kr/mnd
                            </span>
                        </p>
                        @error('type')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <!-- Calculated Minimum Display -->
                        <div x-show="calculatedMinimum > 0"
                             x-bind:class="parseFloat($wire.minimumPayment || 0) < calculatedMinimum
                                ? 'bg-red-50 dark:bg-red-900/20 border-red-500 dark:border-red-700'
                                : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'"
                             class="mt-3 p-3 border rounded-lg">
                            <div class="flex items-center gap-2">
                                <template x-if="parseFloat($wire.minimumPayment || 0) < calculatedMinimum">
                                    <!-- Warning icon (red) -->
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </template>
                                <template x-if="parseFloat($wire.minimumPayment || 0) >= calculatedMinimum">
                                    <!-- Info icon (blue) -->
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                </template>
                                <p x-bind:class="parseFloat($wire.minimumPayment || 0) < calculatedMinimum
                                    ? 'text-sm font-medium text-red-900 dark:text-red-200'
                                    : 'text-sm font-medium text-blue-900 dark:text-blue-200'">
                                    {{ __('app.calculated_minimum') }}:
                                    <span x-text="formatCurrency(calculatedMinimum)"></span> kr
                                </p>
                            </div>
                        </div>
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
                        @error('minimumPayment')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Payment Due Day -->
                    <div>
                        <label for="dueDay" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('app.due_day') }}
                        </label>
                        <input
                            type="number"
                            id="dueDay"
                            wire:model.blur="dueDay"
                            min="1"
                            max="31"
                            placeholder="{{ __('app.due_day_placeholder') }}"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-colors duration-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none @error('dueDay') border-red-500 dark:border-red-400 @enderror"
                        >
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('app.due_day_help') }}
                        </p>
                        @error('dueDay')
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
                    <button
                        type="button"
                        wire:click="$parent.cancelEdit()"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors duration-200 cursor-pointer"
                    >
                        {{ __('app.cancel') }}
                    </button>
                </div>
            </form>
        </div>
</div>
