@props([
    'id',
    'label',
    'model',
    'value' => '',
    'error' => null,
    'required' => false,
    'maxDate' => null,
    'minDate' => null,
])

@php
$pickerId = $id . '-picker';
@endphp

<div class="relative">
    <label for="{{ $id }}-display" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 font-body">
        {{ $label }}@if($required ?? false) <span class="text-rose-500">*</span>@endif
    </label>
    <div class="relative" x-data="{
        displayDate: '{{ $value ?? '' }}',
        updateFromPicker(value) {
            if (value) {
                const parts = value.split('-');
                this.displayDate = `${parts[2]}.${parts[1]}.${parts[0]}`;
                $wire.set('{{ $model }}', this.displayDate);
            }
        }
    }">
        <input
            type="text"
            id="{{ $id }}-display"
            x-model="displayDate"
            readonly
            @click="$refs.{{ $pickerId }}.showPicker()"
            placeholder="dd.mm.yyyy"
            class="w-full px-4 py-2.5 pr-10 border rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 cursor-pointer transition-colors duration-200 {{ $error ? 'border-rose-500 dark:border-rose-400 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500' : 'border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500' }}"
            @if($required ?? false) required @endif
        >
        <input
            type="date"
            x-ref="{{ $pickerId }}"
            @change="updateFromPicker($event.target.value)"
            @if(isset($maxDate)) max="{{ $maxDate }}" @endif
            @if(isset($minDate)) min="{{ $minDate }}" @endif
            class="absolute inset-0 opacity-0 cursor-pointer"
            style="z-index: -1;"
        >
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="h-5 w-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
    </div>
    @if(isset($error) && $error)
        <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $error }}</p>
    @endif
</div>
