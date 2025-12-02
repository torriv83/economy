<div class="max-w-md">
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
            <table class="w-full">
                <tbody class="divide-y divide-slate-200/50 dark:divide-slate-700/50">
                    @foreach ($shortcuts as $shortcut)
                        <tr
                            class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer"
                            x-data
                            x-on:click="
                                const url = '{{ $shortcut['url'] }}';
                                const view = {{ $shortcut['view'] ? "'" . $shortcut['view'] . "'" : 'null' }};
                                if (window.location.pathname === url && view) {
                                    Livewire.dispatch('setView', { view: view });
                                } else {
                                    window.pendingView = view;
                                    Livewire.navigate(url);
                                }
                            "
                        >
                            <td class="py-3 pr-4 w-28">
                                <div class="flex items-center gap-1.5">
                                    @if ($shortcut['key'] === 'L' || $shortcut['key'] === '?')
                                        <kbd class="inline-flex items-center justify-center h-7 px-2 text-xs font-semibold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm group-hover:bg-emerald-50 group-hover:border-emerald-200 group-hover:text-emerald-700 dark:group-hover:bg-emerald-900/30 dark:group-hover:border-emerald-800 dark:group-hover:text-emerald-400 transition-colors">SHIFT</kbd>
                                        <span class="text-slate-400 dark:text-slate-500 text-xs">+</span>
                                        <kbd class="inline-flex items-center justify-center h-7 px-2 text-xs font-semibold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm group-hover:bg-emerald-50 group-hover:border-emerald-200 group-hover:text-emerald-700 dark:group-hover:bg-emerald-900/30 dark:group-hover:border-emerald-800 dark:group-hover:text-emerald-400 transition-colors">{{ $shortcut['key'] }}</kbd>
                                    @else
                                        <kbd class="inline-flex items-center justify-center min-w-[1.75rem] h-7 px-2 text-xs font-semibold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm group-hover:bg-emerald-50 group-hover:border-emerald-200 group-hover:text-emerald-700 dark:group-hover:bg-emerald-900/30 dark:group-hover:border-emerald-800 dark:group-hover:text-emerald-400 transition-colors">{{ strtoupper($shortcut['key']) }}</kbd>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 text-sm text-slate-700 dark:text-slate-300">{{ $shortcut['description'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
