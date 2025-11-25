import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import sort from '@alpinejs/sort';

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
