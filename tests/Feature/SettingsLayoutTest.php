<?php

use App\Livewire\Settings\SettingsLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('settings page is accessible', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get('/settings');

    $response->assertStatus(200);
});

test('settings layout renders with default plan view', function () {
    Livewire::test(SettingsLayout::class)
        ->assertSet('currentView', 'plan');
});

test('settings layout includes payoff settings component', function () {
    Livewire::test(SettingsLayout::class)
        ->assertSee(__('app.plan_settings'));
});

test('settings route responds to view query parameter', function () {
    Livewire::withQueryParams(['view' => 'plan'])
        ->test(SettingsLayout::class)
        ->assertSet('currentView', 'plan');
});
