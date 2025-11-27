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

@php
$inputClasses = 'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none';
$errorClasses = $error ? 'border-red-500 dark:border-red-400' : '';
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        {{ $label }}@if($required ?? false) *@endif
        @if(isset($hint))
            <span class="font-normal text-gray-500 dark:text-gray-400">({{ $hint }})</span>
        @endif
    </label>
    <input
        type="number"
        id="{{ $id }}"
        wire:model="{{ $model }}"
        step="0.01"
        min="{{ $min ?? '0.01' }}"
        @if(isset($max)) max="{{ $max }}" @endif
        class="{{ $inputClasses }} {{ $errorClasses }}"
        @if($required ?? false) required @endif
    >
    @if(isset($error) && $error)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
