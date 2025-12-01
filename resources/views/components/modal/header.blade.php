@props([
    'title' => '',
    'closeable' => true,
    'onClose' => null,
])

<div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700/50">
    <div class="flex items-center justify-between">
        <div class="flex-1 min-w-0">
            <h3 class="font-display text-lg font-semibold text-slate-900 dark:text-white" id="modal-title">
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
                    class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-800 rounded-lg p-1.5 transition-colors cursor-pointer"
                >
                    <span class="sr-only">{{ __('app.close') }}</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            @endif
        </div>
    </div>
</div>
