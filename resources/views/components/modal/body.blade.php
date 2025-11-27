@props([
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800' . ($padding ? ' px-6 py-5' : '')]) }}>
    {{ $slot }}
</div>
