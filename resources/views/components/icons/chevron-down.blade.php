@props(['rotated' => null])
<svg
    {{ $attributes->merge(['fill' => 'none', 'stroke' => 'currentColor', 'viewBox' => '0 0 24 24']) }}
    @if ($rotated !== null) :class="{ 'rotate-180': {{ $rotated }} }" @endif
>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
</svg>
