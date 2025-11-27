@props([
    'title' => '',
    'closeable' => true,
    'onClose' => null,
])

<div class="bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between">
        <div class="flex-1 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                {{ $title ?? '' }}
            </h3>
            @if(isset($slot) && $slot->isNotEmpty())
                {{ $slot }}
            @endif
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if(isset($actions))
                {{ $actions }}
            @endif
            @if($closeable)
                <button
                    type="button"
                    @if($onClose)
                        wire:click="{{ $onClose }}"
                    @else
                        x-on:click="show = false"
                    @endif
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg p-1 transition-colors cursor-pointer"
                >
                    <span class="sr-only">{{ __('app.close') }}</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            @endif
        </div>
    </div>
</div>
