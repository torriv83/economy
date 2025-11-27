<div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('app.keyboard_shortcuts') }}</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('app.keyboard_shortcuts_description') }}</p>
        </div>
        <div class="p-4 sm:p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('app.shortcut') }}</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('app.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($shortcuts as $shortcut)
                            <tr>
                                <td class="py-3 px-4">
                                    @if ($shortcut['key'] === 'L' || $shortcut['key'] === '?')
                                        <kbd class="px-2 py-1 text-sm font-mono bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded border border-gray-300 dark:border-gray-600">SHIFT</kbd>
                                        <span class="mx-1 text-gray-500 dark:text-gray-400">+</span>
                                        <kbd class="px-2 py-1 text-sm font-mono bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded border border-gray-300 dark:border-gray-600">{{ $shortcut['key'] }}</kbd>
                                    @else
                                        <kbd class="px-2 py-1 text-sm font-mono bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded border border-gray-300 dark:border-gray-600">{{ strtoupper($shortcut['key']) }}</kbd>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-700 dark:text-gray-300">{{ $shortcut['description'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
