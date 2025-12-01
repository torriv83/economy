<div>
    <div class="premium-card rounded-2xl border border-slate-200/50 dark:border-slate-700/50">
        {{-- Header --}}
        <div class="p-6 border-b border-slate-200/50 dark:border-slate-700/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-display font-semibold text-slate-900 dark:text-white">{{ __('app.keyboard_shortcuts') }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.keyboard_shortcuts_description') }}</p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="space-y-2">
                @foreach ($shortcuts as $shortcut)
                    <div class="flex items-center justify-between py-3 px-4 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                        <span class="text-sm text-slate-700 dark:text-slate-300 font-medium">{{ $shortcut['description'] }}</span>
                        <div class="flex items-center gap-1.5">
                            @if ($shortcut['key'] === 'L' || $shortcut['key'] === '?')
                                <kbd class="inline-flex items-center justify-center min-w-[2.5rem] h-8 px-2.5 text-xs font-semibold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">SHIFT</kbd>
                                <span class="text-slate-400 dark:text-slate-500 text-xs font-medium">+</span>
                                <kbd class="inline-flex items-center justify-center min-w-[2rem] h-8 px-2.5 text-xs font-semibold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">{{ $shortcut['key'] }}</kbd>
                            @else
                                <kbd class="inline-flex items-center justify-center min-w-[2rem] h-8 px-2.5 text-xs font-semibold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm group-hover:bg-emerald-50 group-hover:border-emerald-200 group-hover:text-emerald-700 dark:group-hover:bg-emerald-900/30 dark:group-hover:border-emerald-800 dark:group-hover:text-emerald-400 transition-colors">{{ strtoupper($shortcut['key']) }}</kbd>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
