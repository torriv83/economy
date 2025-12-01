<div class="space-y-6 animate-fade-in-up">
    @if ($debtId === null)
        {{-- Filter Card - only shown when viewing all reconciliations --}}
        <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-700 flex items-center justify-center">
                        <svg class="h-5 w-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                    </div>
                    <h2 class="font-display font-semibold text-slate-900 dark:text-white">
                        {{ __('app.filter') }}
                    </h2>
                </div>

                {{-- Filter Dropdown --}}
                <div class="w-full sm:w-72">
                    <label for="filterDebtId" class="sr-only">{{ __('app.filter_by_debt') }}</label>
                    <select
                        id="filterDebtId"
                        wire:model.live="filterDebtId"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all cursor-pointer font-medium"
                    >
                        <option value="">{{ __('app.all_debts') }}</option>
                        @foreach ($this->debts as $filterDebt)
                            <option value="{{ $filterDebt->id }}">{{ $filterDebt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    @endif

    {{-- Reconciliation List --}}
    <div class="premium-card rounded-2xl border border-slate-200 dark:border-slate-700/50 overflow-hidden">
        @if ($this->reconciliations->isEmpty())
            {{-- Empty State --}}
            <div class="text-center py-16 px-6">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-700 flex items-center justify-center mx-auto mb-5">
                    <svg class="h-10 w-10 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <h3 class="font-display font-semibold text-lg text-slate-900 dark:text-white mb-2">
                    {{ __('app.no_reconciliations') }}
                </h3>
                <p class="text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    {{ __('app.no_reconciliations_description') }}
                </p>
            </div>
        @else
            {{-- Reconciliations List --}}
            <div class="divide-y divide-slate-100 dark:divide-slate-700/50 stagger-children">
                @foreach ($this->reconciliations as $reconciliation)
                    <div wire:key="reconciliation-{{ $reconciliation->id }}" class="p-5 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all duration-200 group">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4 flex-1 min-w-0">
                                {{-- Status Icon Box --}}
                                @php
                                    $isDecrease = $reconciliation->principal_paid > 0;
                                @endphp
                                <div class="shrink-0">
                                    <div class="w-12 h-12 rounded-xl {{ $isDecrease ? 'bg-gradient-to-br from-emerald-100 to-cyan-100 dark:from-emerald-900/30 dark:to-cyan-900/30' : 'bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30' }} flex items-center justify-center">
                                        @if ($isDecrease)
                                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181" />
                                            </svg>
                                        @else
                                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.281m5.94 2.28l-2.28 5.941" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    @if ($debtId === null)
                                        {{-- Debt Name - only shown when viewing all reconciliations --}}
                                        <h4 class="font-display font-semibold text-slate-900 dark:text-white mb-1">
                                            {{ $reconciliation->debt->name }}
                                        </h4>
                                    @endif

                                    {{-- Date and Amount --}}
                                    <div class="flex flex-wrap items-center gap-3 mb-2">
                                        <span class="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                            </svg>
                                            {{ $reconciliation->payment_date->format('d.m.Y') }}
                                        </span>

                                        {{-- Amount Badge --}}
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold {{ $isDecrease ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' }}">
                                            @if ($isDecrease)
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                                </svg>
                                                -{{ number_format(abs($reconciliation->principal_paid), 0, ',', ' ') }} kr
                                            @else
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                                </svg>
                                                +{{ number_format(abs($reconciliation->principal_paid), 0, ',', ' ') }} kr
                                            @endif
                                        </span>
                                    </div>

                                    {{-- Notes --}}
                                    @if ($reconciliation->notes)
                                        <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2">
                                            {{ $reconciliation->notes }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-1.5 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <button
                                    type="button"
                                    wire:click="openEditModal({{ $reconciliation->id }})"
                                    class="p-2.5 text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                    title="{{ __('app.edit') }}"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="confirmDelete({{ $reconciliation->id }})"
                                    class="p-2.5 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                                    title="{{ __('app.delete') }}"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Edit Modal --}}
    @include('components.reconciliation.edit-modal')

    {{-- Delete Confirmation Modal --}}
    @include('components.reconciliation.delete-modal')
</div>
