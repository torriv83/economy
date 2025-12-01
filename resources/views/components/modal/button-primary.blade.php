@props([
    'type' => 'button',
    'variant' => 'primary',
    'loading' => false,
    'loadingTarget' => null,
    'loadingText' => null,
])

@php
$variantClasses = [
    'primary' => 'btn-momentum',
    'danger' => 'bg-rose-600 hover:bg-rose-700 dark:bg-rose-500 dark:hover:bg-rose-600 shadow-rose-500/20 hover:shadow-rose-500/30',
    'warning' => 'bg-amber-600 hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-600 shadow-amber-500/20 hover:shadow-amber-500/30',
    'success' => 'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 shadow-emerald-500/20 hover:shadow-emerald-500/30',
][$variant] ?? 'btn-momentum';
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => "inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold text-white shadow-lg {$variantClasses} focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-800 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none transition-all cursor-pointer"
    ]) }}
    @if($loading && $loadingTarget)
        wire:loading.attr="disabled"
        wire:target="{{ $loadingTarget }}"
    @endif
>
    @if($loading && $loadingTarget)
        <span wire:loading.remove wire:target="{{ $loadingTarget }}">{{ $slot }}</span>
        <span wire:loading wire:target="{{ $loadingTarget }}" class="inline-flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ $loadingText ?? $slot }}...
        </span>
    @else
        {{ $slot }}
    @endif
</button>
