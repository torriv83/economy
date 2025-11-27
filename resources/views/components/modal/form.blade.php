@props([
    'title',
    'onClose' => null,
    'onSubmit',
    'submitText',
    'cancelText' => null,
    'loading' => false,
    'loadingTarget' => null,
    'loadingText' => null,
    'variant' => 'primary',
])

@php
$cancelLabel = $cancelText ?? __('app.cancel');
$cancelAction = $onClose ? "wire:click={$onClose}" : 'x-on:click=show = false';
@endphp

<form wire:submit.prevent="{{ $onSubmit }}">
    <x-modal.header
        :title="$title"
        :on-close="$onClose"
    />

    <x-modal.body>
        {{ $slot }}
    </x-modal.body>

    <x-modal.footer>
        @if($onClose)
            <x-modal.button-secondary wire:click="{{ $onClose }}">
                {{ $cancelLabel }}
            </x-modal.button-secondary>
        @else
            <x-modal.button-secondary x-on:click="show = false">
                {{ $cancelLabel }}
            </x-modal.button-secondary>
        @endif
        <x-modal.button-primary
            type="submit"
            :variant="$variant"
            :loading="$loading"
            :loading-target="$loadingTarget ?? $onSubmit"
            :loading-text="$loadingText"
        >
            {{ $submitText }}
        </x-modal.button-primary>
    </x-modal.footer>
</form>
