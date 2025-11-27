@props(['title', 'message', 'onConfirm' => ''])

<x-modal wire:model="{{ $attributes->wire('model')->value() }}" max-width="sm">
    <x-modal.confirmation
        :title="$title"
        :message="$message"
        :on-confirm="$onConfirm"
        variant="danger"
        :loading="true"
        :loading-target="$onConfirm"
    />
</x-modal>
