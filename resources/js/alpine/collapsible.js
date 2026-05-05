import { Alpine } from '../../../vendor/livewire/livewire/dist/livewire.esm';

const readPersisted = (key, fallback) => {
    if (!key) {
        return fallback;
    }

    try {
        const saved = localStorage.getItem(key);
        return saved !== null ? JSON.parse(saved) : fallback;
    } catch (e) {
        return fallback;
    }
};

const writePersisted = (key, value) => {
    if (!key) {
        return;
    }

    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
        // Storage unavailable — silently ignore
    }
};

Alpine.data('collapsible', (initial = true, persistKey = null) => ({
    open: readPersisted(persistKey, initial),

    init() {
        if (persistKey) {
            this.$watch('open', (value) => writePersisted(persistKey, value));
        }
    },

    toggle() {
        this.open = !this.open;
    },
}));

Alpine.data('collapsibleMap', ({ persistKey = null, historicalKeys = [] } = {}) => ({
    expanded: readPersisted(persistKey, {}),
    historicalKeys,

    init() {
        if (persistKey) {
            this.$watch('expanded', (value) => writePersisted(persistKey, value));
        }
    },

    isExpanded(key) {
        if (this.expanded[key] !== undefined) {
            return this.expanded[key];
        }

        return !this.historicalKeys.includes(key);
    },

    toggle(key) {
        this.expanded = { ...this.expanded, [key]: !this.isExpanded(key) };
    },

    get allHistoricalCollapsed() {
        if (this.historicalKeys.length === 0) {
            return false;
        }

        return this.historicalKeys.every((k) => !this.expanded[k]);
    },

    toggleAllHistorical() {
        const expand = this.allHistoricalCollapsed;
        const next = { ...this.expanded };
        this.historicalKeys.forEach((k) => {
            next[k] = expand;
        });
        this.expanded = next;
    },
}));
