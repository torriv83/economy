@props([
    'type' => 'button',
])

<button
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 transition-colors cursor-pointer'
    ]) }}
>
    {{ $slot }}
</button>
