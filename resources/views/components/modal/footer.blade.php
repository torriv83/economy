@props([
    'align' => 'end',
])

@php
$alignClasses = [
    'start' => 'justify-start',
    'center' => 'justify-center',
    'end' => 'justify-end',
    'between' => 'justify-between',
][$align] ?? 'justify-end';
@endphp

<div {{ $attributes->merge(['class' => "bg-gray-50 dark:bg-gray-800/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex {$alignClasses} gap-3"]) }}>
    {{ $slot }}
</div>
