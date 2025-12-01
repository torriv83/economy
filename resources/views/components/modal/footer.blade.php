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

<div {{ $attributes->merge(['class' => "bg-slate-50 dark:bg-slate-800/30 px-6 py-4 border-t border-slate-200 dark:border-slate-700/50 flex {$alignClasses} gap-3"]) }}>
    {{ $slot }}
</div>
