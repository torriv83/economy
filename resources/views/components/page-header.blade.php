@props(['title', 'subtitle' => null])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-slate-900 dark:text-white tracking-tight">
            {{ $title }}
        </h1>
        @if ($subtitle)
            <p class="text-slate-500 dark:text-slate-400 mt-1.5 text-sm sm:text-base">
                {{ $subtitle }}
            </p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex items-center gap-3 shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>
