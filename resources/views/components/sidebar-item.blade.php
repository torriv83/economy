@props([
    'action',
    'label',
    'icon',
    'active' => false,
    'activeColor' => 'emerald',
])

@php
    $colorClasses = match($activeColor) {
        'cyan' => [
            'text' => 'text-cyan-600 dark:text-cyan-400',
            'bg' => 'bg-cyan-100 dark:bg-cyan-900/30',
            'ring' => 'focus-visible:ring-cyan-500',
        ],
        default => [
            'text' => 'text-emerald-600 dark:text-emerald-400',
            'bg' => 'bg-emerald-100 dark:bg-emerald-900/30',
            'ring' => 'focus-visible:ring-emerald-500',
        ],
    };
@endphp

<button
    wire:click="{{ $action }}"
    class="sidebar-link w-full text-left px-4 py-3 rounded-xl transition-all cursor-pointer group {{ $active ? 'sidebar-link-active ' . $colorClasses['text'] . ' font-medium' : 'text-slate-600 dark:text-slate-400' }} focus-visible:outline-none focus-visible:ring-2 {{ $colorClasses['ring'] }} focus-visible:ring-offset-2"
>
    <div class="flex items-center gap-3">
        <div class="flex-shrink-0 w-9 h-9 rounded-lg {{ $active ? $colorClasses['bg'] : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-slate-200 dark:group-hover:bg-slate-700' }} flex items-center justify-center transition-colors">
            <svg class="h-5 w-5 {{ $active ? $colorClasses['text'] : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                {!! $icon !!}
            </svg>
        </div>
        <span class="font-medium">{{ $label }}</span>
    </div>
</button>
