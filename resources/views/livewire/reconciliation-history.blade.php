<div>
    {{-- Reconciliation History List --}}
    @if ($this->reconciliations->isEmpty())
        {{-- Empty State --}}
        <div class="text-center py-8">
            <div class="h-12 w-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="h-6 w-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
            <p class="text-gray-600 dark:text-gray-400 font-medium">
                {{ __('app.no_reconciliations') }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                {{ __('app.no_reconciliations_description') }}
            </p>
        </div>
    @else
        {{-- Reconciliations List --}}
        <div class="space-y-3">
            @foreach ($this->reconciliations as $reconciliation)
                <div wire:key="reconciliation-{{ $reconciliation->id }}" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            {{-- Date --}}
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $reconciliation->payment_date->format('d.m.Y') }}
                                </span>

                                {{-- Amount Badge --}}
                                @php
                                    $isDecrease = $reconciliation->principal_paid > 0;
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $isDecrease ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' }}">
                                    @if ($isDecrease)
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                        -{{ number_format(abs($reconciliation->principal_paid), 0, ',', ' ') }} kr
                                    @else
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                        +{{ number_format(abs($reconciliation->principal_paid), 0, ',', ' ') }} kr
                                    @endif
                                </span>
                            </div>

                            {{-- Notes --}}
                            @if ($reconciliation->notes)
                                <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                    {{ $reconciliation->notes }}
                                </p>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                type="button"
                                wire:click="openEditModal({{ $reconciliation->id }})"
                                class="p-2 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors cursor-pointer"
                                title="{{ __('app.edit') }}"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                wire:click="confirmDelete({{ $reconciliation->id }})"
                                class="p-2 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors cursor-pointer"
                                title="{{ __('app.delete') }}"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Edit Modal --}}
    @include('components.reconciliation.edit-modal')

    {{-- Delete Confirmation Modal --}}
    @include('components.reconciliation.delete-modal')
</div>
