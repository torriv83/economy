@props([
    'id',
    'label',
    'model',
    'rows' => 3,
    'placeholder' => null,
    'error' => null,
    'required' => false,
])

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        {{ $label }}@if($required ?? false) *@endif
    </label>
    <textarea
        id="{{ $id }}"
        wire:model="{{ $model }}"
        rows="{{ $rows ?? 3 }}"
        @if(isset($placeholder)) placeholder="{{ $placeholder }}" @endif
        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white {{ $error ? 'border-red-500 dark:border-red-400' : '' }}"
        @if($required ?? false) required @endif
    ></textarea>
    @if(isset($error) && $error)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
