@props([
    'id',
    'label',
    'model',
    'error' => null,
    'required' => false,
    'hint' => null,
    'min' => '0.01',
    'max' => null,
])

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 font-body">
        {{ $label }}@if($required ?? false) <span class="text-rose-500">*</span>@endif
        @if(isset($hint))
            <span class="font-normal text-slate-500 dark:text-slate-400">({{ $hint }})</span>
        @endif
    </label>
    <input
        type="number"
        id="{{ $id }}"
        wire:model="{{ $model }}"
        step="0.01"
        min="{{ $min ?? '0.01' }}"
        @if(isset($max)) max="{{ $max }}" @endif
        class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 transition-colors duration-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none {{ $error ? 'border-rose-500 dark:border-rose-400 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500' : 'border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500' }}"
        @if($required ?? false) required @endif
    >
    @if(isset($error) && $error)
        <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $error }}</p>
    @endif
</div>
