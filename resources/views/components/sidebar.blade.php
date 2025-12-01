@props([
    'sectionTitle',
])

<aside class="hidden md:block fixed left-0 top-16 w-64 h-[calc(100vh-4rem)] bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm border-r border-slate-200/50 dark:border-slate-800/50 overflow-y-auto custom-scrollbar transition-colors duration-300">
    <nav class="p-5">
        {{-- Section Header --}}
        <div class="flex items-center gap-2 mb-4">
            <div class="w-2 h-2 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500"></div>
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $sectionTitle }}</h2>
        </div>

        <div class="space-y-1">
            {{ $slot }}
        </div>
    </nav>
</aside>
