<?php

declare(strict_types=1);

use App\Livewire\Settings\KeyboardShortcuts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('keyboard shortcuts settings view is accessible', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get('/settings?view=shortcuts')
        ->assertOk();
});

it('keyboard shortcuts component renders with shortcuts list', function (): void {
    Livewire::test(KeyboardShortcuts::class)
        ->assertOk()
        ->assertSee('n');
});
