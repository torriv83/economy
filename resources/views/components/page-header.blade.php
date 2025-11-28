@props(['title', 'subtitle' => null])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-white break-words">
            {{ $title }}
        </h1>
        @if ($subtitle)
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ $subtitle }}
            </p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex items-center gap-2 shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>
