import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import sort from '@alpinejs/sort';

// Import Alpine components
import './alpine/debt-type-calculator';

Alpine.plugin(sort);

Livewire.hook('request', ({ fail }) => {
    fail(({ status, preventDefault }) => {
        if (status === 419) {
            preventDefault();

            if (confirm('Your session has expired. Refresh the page?')) {
                window.location.reload();
            }
        }
    });
});

Livewire.start();

// Global keyboard shortcuts (registered once, not on every navigation)
let pendingView = null;

document.addEventListener('keydown', (e) => {
    // Ignore if user is typing in an input, textarea, or contenteditable
    const target = e.target;
    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable) {
        return;
    }

    // Skip if modifier keys are pressed (except Shift for ?)
    if (e.ctrlKey || e.metaKey || e.altKey) {
        return;
    }

    const key = e.key;

    const shortcuts = {
        'h': { url: '/debts', view: 'overview' },
        'c': { url: '/payoff', view: 'calendar' },
        'p': { url: '/payoff', view: 'plan' },
        's': { url: '/payoff', view: 'strategies' },
        'l': { url: '/self-loans', view: null },
        'L': { url: '/self-loans', view: 'create' },
        'n': { url: '/debts', view: 'create' },
        '?': { url: '/settings', view: 'shortcuts' },
    };

    if (shortcuts[key]) {
        e.preventDefault();
        const shortcut = shortcuts[key];

        // Check if we're already on the same base URL
        const currentPath = window.location.pathname;
        if (currentPath === shortcut.url && shortcut.view) {
            // Just dispatch the event, no navigation needed
            Livewire.dispatch('setView', { view: shortcut.view });
        } else {
            // Navigate and set pending view for after navigation
            pendingView = shortcut.view;
            Livewire.navigate(shortcut.url);
        }
    }
});

// Dispatch setView event after navigation completes
document.addEventListener('livewire:navigated', () => {
    // Check both local and window-level pending views (for click navigation)
    const view = pendingView || window.pendingView;
    if (view) {
        Livewire.dispatch('setView', { view: view });
        pendingView = null;
        window.pendingView = null;
    }
});
