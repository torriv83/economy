@props([
    'type' => 'button',
    'variant' => 'primary',
    'loading' => false,
    'loadingTarget' => null,
    'loadingText' => null,
])

@php
$variantClasses = [
    'primary' => 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:ring-blue-500 dark:focus:ring-blue-400',
    'danger' => 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 focus:ring-red-500 dark:focus:ring-red-400',
    'warning' => 'bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-500 dark:hover:bg-yellow-600 focus:ring-yellow-500 dark:focus:ring-yellow-400',
    'success' => 'bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 focus:ring-green-500 dark:focus:ring-green-400',
][$variant] ?? 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:ring-blue-500 dark:focus:ring-blue-400';
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => "inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm {$variantClasses} focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors cursor-pointer"
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
