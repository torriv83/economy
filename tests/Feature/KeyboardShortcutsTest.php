<?php

declare(strict_types=1);

use App\Livewire\Settings\KeyboardShortcuts;
use Livewire\Livewire;

it('keyboard shortcuts settings view is accessible', function (): void {
    $this->get('/settings?view=shortcuts')
        ->assertOk();
});

it('keyboard shortcuts component renders with shortcuts list', function (): void {
    Livewire::test(KeyboardShortcuts::class)
        ->assertOk()
        ->assertSee('n');
});
