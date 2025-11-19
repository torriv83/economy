<div>
    @if (count($this->allRepayments) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('app.all_repayments') }}</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($this->allRepayments as $repayment)
                    <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $repayment['loan_name'] }}</p>
                                @if ($repayment['notes'])
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $repayment['notes'] }}</p>
                                @endif
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ $repayment['paid_at'] }}</p>
                            </div>
                            <div class="ml-4 text-right">
                                <p class="text-lg font-bold text-green-600 dark:text-green-400">
                                    +{{ number_format($repayment['amount'], 0, ',', ' ') }} kr
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if (count($this->paidOffLoans) > 0)
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('app.paid_off_loans') }}</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($this->paidOffLoans as $loan)
                        <div class="bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800 rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $loan['name'] }}</h3>
                            </div>
                            @if ($loan['description'])
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $loan['description'] }}</p>
                            @endif
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                <span class="font-medium">{{ __('app.amount') }}:</span> {{ number_format($loan['original_amount'], 0, ',', ' ') }} kr
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ __('app.created') }}: {{ $loan['created_at'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if (count($this->allRepayments) === 0 && count($this->paidOffLoans) === 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="max-w-sm mx-auto">
                <div class="h-16 w-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ __('app.no_history_yet') }}</h2>
                <p class="text-gray-600 dark:text-gray-400">{{ __('app.repayments_will_appear') }}</p>
            </div>
        </div>
    @endif
</div>
