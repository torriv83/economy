@props([
    'show' => false,
    'maxWidth' => 'md',
    'closeable' => true,
    'id' => null,
])

@php
$maxWidthClasses = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth] ?? 'sm:max-w-md';
$modalId = $id ?? 'modal-' . uniqid();
@endphp

<div
    x-data="{ show: @entangle($attributes->wire('model')) }"
    x-show="show"
    x-on:keydown.escape.window="{{ $closeable ? 'show = false' : '' }}"
    x-trap.inert.noscroll="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    aria-labelledby="{{ $modalId }}-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 dark:bg-black/70 transition-opacity cursor-pointer"
        @if($closeable) x-on:click="show = false" @endif
        aria-hidden="true"
    ></div>

    {{-- Modal Container --}}
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl ring-1 ring-black/5 dark:ring-white/10 transition-all sm:my-8 sm:w-full {{ $maxWidthClasses }}"
                @click.stop
            >
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
