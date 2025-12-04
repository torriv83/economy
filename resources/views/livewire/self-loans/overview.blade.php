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

    @if (count($this->selfLoans) > 0)
        {{-- Summary Card --}}
        <div class="mb-8 premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">
                        {{ __('app.total_owed_to_self') }}
                    </p>
                    <p class="font-display font-bold text-3xl">
                        <span class="gradient-text">{{ number_format($this->totalBorrowed, 0, ',', ' ') }}</span>
                        <span class="text-lg font-normal text-slate-400 dark:text-slate-500">kr</span>
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                        {{ $this->loansCount }} {{ trans_choice('app.active_loans', $this->loansCount) }}
                    </p>
                </div>
                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-emerald-500/10 to-cyan-500/10 dark:from-emerald-500/20 dark:to-cyan-500/20 flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Self-Loans Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($this->selfLoans as $loan)
                <div wire:key="loan-{{ $loan['id'] }}" class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 card-interactive overflow-hidden">
                    <div class="p-6">
                        {{-- Loan Name with Edit Icon --}}
                        <div class="mb-4 flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-display font-semibold text-lg text-slate-900 dark:text-white">
                                    {{ $loan['name'] }}
                                </h3>
                            </div>
                            <button
                                wire:click="openEditModal({{ $loan['id'] }})"
                                class="ml-2 p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                title="{{ __('app.edit') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>
                        </div>
                        @if ($loan['description'])
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 mb-4">
                                {{ $loan['description'] }}
                            </p>
                        @endif

                        {{-- Progress Bar --}}
                        @if ($loan['progress_percentage'] > 0)
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                        {{ __('app.repaid_progress') }}
                                    </span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ number_format($loan['progress_percentage'], 1, ',', ' ') }}%
                                    </span>
                                </div>
                                <div class="relative h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div
                                        class="absolute inset-y-0 left-0 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-full transition-all duration-500"
                                        style="width: {{ $loan['progress_percentage'] }}%"
                                    ></div>
                                </div>
                                <div class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ number_format($loan['total_repaid'], 0, ',', ' ') }} kr {{ __('app.of') }} {{ number_format($loan['original_amount'], 0, ',', ' ') }} kr {{ __('app.repaid') }}
                                </div>
                            </div>
                        @endif

                        {{-- Loan Details --}}
                        <div class="space-y-3 mb-3">
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.balance') }}</span>
                                <span class="font-display font-bold text-xl text-slate-900 dark:text-white">
                                    {{ number_format($loan['current_balance'], 0, ',', ' ') }} kr
                                </span>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.original_amount') }}</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">
                                    {{ number_format($loan['original_amount'], 0, ',', ' ') }} kr
                                </span>
                            </div>
                            <div class="flex justify-between items-baseline pt-3 border-t border-slate-200/50 dark:border-slate-700/50">
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('app.created') }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $loan['created_at'] }}
                                </span>
                            </div>
                            @if ($loan['ynab_account_name'] || $loan['ynab_category_name'])
                                <div class="flex justify-between items-baseline">
                                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('app.linked_to_ynab') }}</span>
                                    <span class="text-xs text-blue-600 dark:text-blue-400">
                                        {{ $loan['ynab_account_name'] ?? $loan['ynab_category_name'] }}
                                    </span>
                                </div>
                                @if ($loan['ynab_available'] !== null)
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('app.available_in_ynab') }}</span>
                                        <span class="text-xs text-emerald-600 dark:text-emerald-400">
                                            {{ number_format($loan['ynab_available'], 0, ',', ' ') }} kr
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                            <button
                                wire:click="openRepaymentModal({{ $loan['id'] }})"
                                class="btn-momentum px-4 py-2.5 text-sm font-medium rounded-xl transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                {{ __('app.add_repayment') }}
                            </button>
                            <button
                                wire:click="openWithdrawalModal({{ $loan['id'] }})"
                                class="px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xl transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                                {{ __('app.withdraw_more') }}
                            </button>
                            <button
                                type="button"
                                wire:click="confirmDelete({{ $loan['id'] }}, '{{ $loan['name'] }}')"
                                class="col-span-2 px-4 py-2.5 text-sm font-medium text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:text-rose-600 hover:border-rose-300 dark:hover:text-rose-400 dark:hover:border-rose-800 rounded-xl transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                                {{ __('app.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Add New Self-Loan Placeholder --}}
            <button
                wire:click="$parent.showCreate"
                class="premium-card rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-600 hover:border-emerald-500 dark:hover:border-emerald-500 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-all p-6 flex flex-col items-center justify-center min-h-[300px] group focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 cursor-pointer">
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 group-hover:bg-gradient-to-br group-hover:from-emerald-500/10 group-hover:to-cyan-500/10 dark:group-hover:from-emerald-500/20 dark:group-hover:to-cyan-500/20 rounded-xl flex items-center justify-center mb-4 transition-colors">
                    <svg class="w-8 h-8 text-slate-400 dark:text-slate-500 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </div>
                <h3 class="font-display font-semibold text-lg text-slate-600 dark:text-slate-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                    {{ __('app.create_self_loan') }}
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-500 mt-2 text-center">
                    {{ __('app.click_to_add_self_loan') }}
                </p>
            </button>
        </div>

        {{-- Security Buffer Card --}}
        @if ($this->bufferStatus)
            <div class="mt-8 premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500/10 to-indigo-500/10 dark:from-blue-500/20 dark:to-indigo-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-display font-semibold text-lg text-slate-900 dark:text-white">
                            {{ __('app.security_buffer') }}
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ __('app.months_of_security_count', ['count' => $this->bufferStatus['months_of_security']]) }}
                        </p>
                    </div>
                    {{-- Status Badge --}}
                    <div class="ml-auto">
                        @if ($this->bufferStatus['status'] === 'healthy')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                {{ __('app.buffer_status_healthy') }}
                            </span>
                        @elseif ($this->bufferStatus['status'] === 'warning')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                {{ __('app.buffer_status_warning') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                                {{ __('app.buffer_status_critical') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="space-y-6">
                    {{-- Layer 1: Operational Buffer --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ __('app.layer1_operational_buffer') }}
                            </span>
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                {{ number_format($this->bufferStatus['layer1']['amount'], 0, ',', ' ') }} kr
                            </span>
                        </div>
                        <div class="relative h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div
                                class="absolute inset-y-0 left-0 {{ $this->bufferStatus['layer1']['is_month_ahead'] ? 'bg-emerald-500' : 'bg-amber-500' }} rounded-full transition-all duration-500"
                                style="width: {{ min($this->bufferStatus['layer1']['percentage'], 100) }}%"
                            ></div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ __('app.assigned_next_month') }} -
                            @if ($this->bufferStatus['layer1']['is_month_ahead'])
                                <span class="text-emerald-600 dark:text-emerald-400">{{ __('app.month_ahead') }}</span>
                            @else
                                {{ $this->bufferStatus['layer1']['percentage'] }}%
                            @endif
                        </p>
                    </div>

                    {{-- Layer 2: Emergency Buffer --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ __('app.layer2_emergency_buffer') }}
                            </span>
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                {{ number_format($this->bufferStatus['layer2']['amount'], 0, ',', ' ') }} kr
                            </span>
                        </div>
                        <div class="relative h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            @php
                                $layer2Percentage = $this->bufferStatus['layer2']['target_months'] > 0
                                    ? min(100, ($this->bufferStatus['layer2']['months'] / $this->bufferStatus['layer2']['target_months']) * 100)
                                    : 0;
                            @endphp
                            <div
                                class="absolute inset-y-0 left-0 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full transition-all duration-500"
                                style="width: {{ $layer2Percentage }}%"
                            ></div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ __('app.savings_accounts') }} -
                            {{ __('app.of_target', ['months' => $this->bufferStatus['layer2']['months'], 'target' => $this->bufferStatus['layer2']['target_months']]) }}
                        </p>
                    </div>

                    {{-- Total --}}
                    <div class="pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ __('app.total_buffer') }}
                            </span>
                            <span class="font-display font-bold text-xl text-slate-900 dark:text-white">
                                {{ number_format($this->bufferStatus['total_buffer'], 0, ',', ' ') }} kr
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recommendations Card --}}
            @if (count($this->recommendations) > 0)
                <div class="mt-6 premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500/10 to-orange-500/10 dark:from-amber-500/20 dark:to-orange-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-display font-semibold text-lg text-slate-900 dark:text-white">
                                {{ __('app.buffer.recommendations_title') }}
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ __('app.buffer.recommendations_description') }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @foreach ($this->recommendations as $recommendation)
                            <div wire:key="rec-{{ $loop->index }}" class="flex items-start gap-4 p-4 rounded-xl {{ $recommendation['status'] === 'action' ? 'bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/50' : ($recommendation['status'] === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800/50' : 'bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700') }}">
                                {{-- Icon --}}
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center {{ $recommendation['status'] === 'action' ? 'bg-amber-100 dark:bg-amber-900/30' : ($recommendation['status'] === 'success' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-slate-200 dark:bg-slate-700') }}">
                                    @if ($recommendation['icon'] === 'check-circle')
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @elseif ($recommendation['icon'] === 'arrow-right')
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                    @elseif ($recommendation['icon'] === 'banknotes')
                                        <svg class="w-5 h-5 {{ $recommendation['status'] === 'action' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                        </svg>
                                    @elseif ($recommendation['icon'] === 'shield')
                                        <svg class="w-5 h-5 {{ $recommendation['status'] === 'action' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                        </svg>
                                    @elseif ($recommendation['icon'] === 'shield-check')
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                        </svg>
                                    @elseif ($recommendation['icon'] === 'scale')
                                        <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                        </svg>
                                    @endif
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-sm {{ $recommendation['status'] === 'action' ? 'text-amber-900 dark:text-amber-200' : ($recommendation['status'] === 'success' ? 'text-emerald-900 dark:text-emerald-200' : 'text-slate-900 dark:text-white') }}">
                                        {{ __('app.'.$recommendation['title'], $recommendation['params']) }}
                                    </h3>
                                    <p class="mt-1 text-sm {{ $recommendation['status'] === 'action' ? 'text-amber-700 dark:text-amber-300' : ($recommendation['status'] === 'success' ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-400') }}">
                                        {{ __('app.'.$recommendation['description'], $recommendation['params']) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Scenario Comparison Toggle --}}
                    <div class="mt-6 pt-6 border-t border-slate-200/50 dark:border-slate-700/50">
                        <button
                            wire:click="toggleScenarioComparison"
                            class="flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4 transition-transform {{ $showScenarioComparison ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                            {{ $showScenarioComparison ? __('app.buffer.hide_options') : __('app.buffer.compare_options') }}
                        </button>

                        {{-- Scenario Comparison Panel --}}
                        @if ($showScenarioComparison && $this->scenarioComparison)
                            <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <h4 class="font-semibold text-sm text-slate-900 dark:text-white mb-4">
                                    {{ __('app.buffer.scenario_comparison_title', ['amount' => number_format($scenarioAmount, 0, ',', ' ').' kr']) }}
                                </h4>

                                {{-- Amount Input --}}
                                <div class="mb-4">
                                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                        {{ __('app.amount') }}
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="number"
                                            wire:model.live.debounce.500ms="scenarioAmount"
                                            min="100"
                                            step="100"
                                            class="flex-1 px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent"
                                        >
                                        <span class="text-sm text-slate-500 dark:text-slate-400">kr</span>
                                    </div>
                                </div>

                                {{-- Options --}}
                                <div class="space-y-3">
                                    @foreach ($this->scenarioComparison['options'] as $option)
                                        <div wire:key="option-{{ $loop->index }}" class="flex items-center justify-between p-3 bg-white dark:bg-slate-700/50 rounded-lg border {{ $this->scenarioComparison['recommendation']['target'] === $option['target'] || ($option['target'] === 'debt' && str_starts_with($this->scenarioComparison['recommendation']['target'], 'debt')) ? 'border-emerald-300 dark:border-emerald-700' : 'border-slate-200 dark:border-slate-600' }}">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                        @if ($option['target'] === 'buffer')
                                                            {{ __('app.buffer.scenario_buffer') }}
                                                        @else
                                                            {{ __('app.buffer.scenario_debt', ['debt_name' => $option['debt_name'] ?? '']) }}
                                                        @endif
                                                    </span>
                                                    @if ($this->scenarioComparison['recommendation']['target'] === $option['target'] || ($option['target'] === 'debt' && isset($option['debt_id']) && $this->scenarioComparison['recommendation']['target'] === 'debt'))
                                                        <span class="px-1.5 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded">
                                                            {{ __('app.buffer.recommended') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    @if ($option['target'] === 'buffer')
                                                        {{ __('app.buffer.days_security', ['days' => $option['impact']['days_of_security_added']]) }}
                                                        ({{ $this->bufferStatus['months_of_security'] }} → {{ $option['impact']['new_buffer_months'] }} {{ __('app.months_short') }})
                                                    @else
                                                        {{ __('app.buffer.interest_savings', ['amount' => number_format($option['impact']['interest_saved'] ?? 0, 0, ',', ' ')]) }},
                                                        {{ __('app.buffer.months_earlier', ['months' => $option['impact']['months_saved'] ?? 0]) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Recommendation Reason --}}
                                <div class="mt-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800/50">
                                    <p class="text-sm text-emerald-700 dark:text-emerald-300">
                                        <span class="font-medium">{{ __('app.buffer.recommended') }}:</span>
                                        {{ __('app.'.$this->scenarioComparison['recommendation']['reason']) }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    @else
        {{-- Empty State --}}
        <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-12 text-center">
            <div class="max-w-sm mx-auto">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-500/10 to-cyan-500/10 dark:from-emerald-500/20 dark:to-cyan-500/20 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
                <h2 class="font-display font-semibold text-xl text-slate-900 dark:text-white mb-2">
                    {{ __('app.no_active_self_loans') }}
                </h2>
                <p class="text-slate-500 dark:text-slate-400 mb-6">
                    {{ __('app.no_self_loans_message') }}
                </p>
            </div>
        </div>
    @endif

    {{-- Repayment Modal --}}
    @if ($showRepaymentModal)
        <x-modal wire:model="showRepaymentModal" max-width="md">
            <x-modal.form
                :title="__('app.add_repayment')"
                on-close="closeRepaymentModal"
                on-submit="addRepayment"
                :submit-text="__('app.add_repayment')"
                :loading="true"
                loading-target="addRepayment"
            >
                <div class="space-y-4">
                    @include('components.form.amount-input', [
                        'id' => 'repayment-amount',
                        'label' => __('app.amount_kr'),
                        'hint' => __('app.up_to_amount', ['amount' => number_format($this->selectedLoanBalance, 2, ',', ' ')]),
                        'model' => 'repaymentAmount',
                        'required' => true,
                        'error' => $errors->first('repaymentAmount'),
                    ])

                    @include('components.form.date-picker', [
                        'id' => 'repayment-date',
                        'label' => __('app.payment_date'),
                        'model' => 'repaymentDate',
                        'value' => $repaymentDate,
                        'maxDate' => date('Y-m-d'),
                        'required' => true,
                        'error' => $errors->first('repaymentDate'),
                    ])

                    @include('components.form.textarea', [
                        'id' => 'repayment-notes',
                        'label' => __('app.notes_optional'),
                        'model' => 'repaymentNotes',
                        'error' => $errors->first('repaymentNotes'),
                    ])
                </div>
            </x-modal.form>
        </x-modal>
    @endif

    {{-- Withdrawal Modal --}}
    @if ($showWithdrawalModal)
        <x-modal wire:model="showWithdrawalModal" max-width="md">
            <x-modal.form
                :title="__('app.withdraw_more')"
                on-close="closeWithdrawalModal"
                on-submit="addWithdrawal"
                :submit-text="__('app.add_withdrawal')"
                :loading="true"
                loading-target="addWithdrawal"
            >
                <div class="space-y-4">
                    @include('components.form.amount-input', [
                        'id' => 'withdrawal-amount',
                        'label' => __('app.amount_kr'),
                        'model' => 'withdrawalAmount',
                        'required' => true,
                        'error' => $errors->first('withdrawalAmount'),
                    ])

                    @include('components.form.date-picker', [
                        'id' => 'withdrawal-date',
                        'label' => __('app.withdrawal_date'),
                        'model' => 'withdrawalDate',
                        'value' => $withdrawalDate,
                        'maxDate' => date('Y-m-d'),
                        'required' => true,
                        'error' => $errors->first('withdrawalDate'),
                    ])

                    @include('components.form.textarea', [
                        'id' => 'withdrawal-notes',
                        'label' => __('app.notes_optional'),
                        'model' => 'withdrawalNotes',
                        'error' => $errors->first('withdrawalNotes'),
                    ])
                </div>
            </x-modal.form>
        </x-modal>
    @endif

    {{-- Edit Modal --}}
    @if ($showEditModal)
        <x-modal wire:model="showEditModal" max-width="md">
            <x-modal.form
                :title="__('app.edit_self_loan')"
                on-close="closeEditModal"
                on-submit="updateLoan"
                :submit-text="__('app.update_self_loan')"
                :loading="true"
                loading-target="updateLoan"
            >
                <div class="space-y-4">
                    @include('components.form.text-input', [
                        'id' => 'edit-name',
                        'label' => __('app.name'),
                        'model' => 'editName',
                        'required' => true,
                        'error' => $errors->first('editName'),
                    ])

                    @include('components.form.textarea', [
                        'id' => 'edit-description',
                        'label' => __('app.description_optional'),
                        'model' => 'editDescription',
                        'error' => $errors->first('editDescription'),
                    ])

                    @include('components.form.amount-input', [
                        'id' => 'edit-original-amount',
                        'label' => __('app.original_amount_kr'),
                        'model' => 'editOriginalAmount',
                        'required' => true,
                        'error' => $errors->first('editOriginalAmount'),
                    ])

                    {{-- YNAB Connection --}}
                    @if ($this->isYnabConfigured)
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                                {{ __('app.link_to_ynab_optional') }}
                            </label>

                            <div class="space-y-3">
                                {{-- No connection --}}
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 dark:has-[:checked]:bg-emerald-900/20">
                                    <input type="radio" wire:model.live="editYnabConnectionType" value="none" class="text-emerald-500 focus:ring-emerald-500">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('app.no_ynab_connection') }}</span>
                                </label>

                                {{-- Account connection --}}
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 dark:has-[:checked]:bg-emerald-900/20">
                                    <input type="radio" wire:model.live="editYnabConnectionType" value="account" class="text-emerald-500 focus:ring-emerald-500">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('app.link_to_ynab_account') }}</span>
                                </label>

                                @if ($editYnabConnectionType === 'account')
                                    <div class="ml-7">
                                        <select
                                            wire:model="editYnabAccountId"
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
                                    <input type="radio" wire:model.live="editYnabConnectionType" value="category" class="text-emerald-500 focus:ring-emerald-500">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('app.link_to_ynab_category') }}</span>
                                </label>

                                @if ($editYnabConnectionType === 'category')
                                    <div class="ml-7">
                                        <select
                                            wire:model="editYnabCategoryId"
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
            </x-modal.form>
        </x-modal>
    @endif

    {{-- Delete Confirmation Modal --}}
    <x-delete-confirmation-modal
        wire:model="showDeleteModal"
        :title="__('app.delete_self_loan_confirm', ['name' => $recordNameToDelete])"
        :message="__('app.delete_self_loan_warning')"
        on-confirm="executeDelete"
    />
</div>
