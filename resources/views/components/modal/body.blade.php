@props([
    'padding' => true,
])

<div {{ $attributes->merge(['class' => ($padding ? 'px-6 py-5' : '')]) }}>
    {{ $slot }}
</div>
