@props([
    'id',
    'label',
    'model',
    'type' => 'text',
    'placeholder' => null,
    'error' => null,
    'required' => false,
])

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 font-body">
        {{ $label }}@if($required ?? false) <span class="text-rose-500">*</span>@endif
    </label>
    <input
        type="{{ $type ?? 'text' }}"
        id="{{ $id }}"
        wire:model="{{ $model }}"
        @if(isset($placeholder)) placeholder="{{ $placeholder }}" @endif
        class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 transition-colors duration-200 {{ $error ? 'border-rose-500 dark:border-rose-400 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500' : 'border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500' }}"
        @if($required ?? false) required @endif
    >
    @if(isset($error) && $error)
        <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $error }}</p>
    @endif
</div>
