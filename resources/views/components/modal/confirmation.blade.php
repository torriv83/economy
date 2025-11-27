@props([
    'title',
    'message',
    'confirmText' => null,
    'cancelText' => null,
    'onConfirm' => '',
    'onCancel' => null,
    'variant' => 'danger',
    'loading' => false,
    'loadingTarget' => null,
])

@php
$iconColors = [
    'danger' => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    'warning' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
    'info' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
][$variant] ?? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400';

$confirmButtonColors = [
    'danger' => 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 focus:ring-red-500',
    'warning' => 'bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-500 dark:hover:bg-yellow-600 focus:ring-yellow-500',
    'info' => 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:ring-blue-500',
][$variant] ?? 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 focus:ring-red-500';

$defaultConfirmText = $variant === 'danger' ? __('app.delete') : __('app.confirm');
$confirmLabel = $confirmText ?? $defaultConfirmText;
$cancelLabel = $cancelText ?? __('app.cancel');
@endphp

<x-modal.body>
    <div class="flex items-start gap-4">
        {{-- Icon --}}
        <div class="h-12 w-12 {{ $iconColors }} rounded-full flex items-center justify-center shrink-0">
            @if($variant === 'danger')
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            @elseif($variant === 'warning')
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            @else
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            @endif
        </div>

        {{-- Content --}}
        <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                {{ $title }}
            </h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ $message }}
            </p>
        </div>
    </div>
</x-modal.body>

<x-modal.footer>
    @if($onCancel)
        <x-modal.button-secondary wire:click="{{ $onCancel }}">
            {{ $cancelLabel }}
        </x-modal.button-secondary>
    @else
        <x-modal.button-secondary x-on:click="show = false">
            {{ $cancelLabel }}
        </x-modal.button-secondary>
    @endif

    <x-modal.button-primary
        wire:click="{{ $onConfirm }}"
        x-on:click="show = false"
        :variant="$variant"
        :loading="$loading"
        :loading-target="$loadingTarget ?? $onConfirm"
    >
        {{ $confirmLabel }}
    </x-modal.button-primary>
</x-modal.footer>
