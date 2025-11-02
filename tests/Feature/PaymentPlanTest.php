<?php

use App\Livewire\PaymentPlan;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('payment plan component renders successfully', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 500]);

    $response = $this->get('/payment-plan');

    $response->assertSuccessful();
});

test('can switch between avalanche and snowball strategies', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 500]);
    Debt::factory()->create(['name' => 'Studielån', 'balance' => 200000, 'interest_rate' => 2.5, 'minimum_payment' => null]);

    Livewire::test(PaymentPlan::class)
        ->assertSet('strategy', 'avalanche')
        ->assertSee('Avalanche Method')
        ->assertSee('Snowball Method')
        ->call('$set', 'strategy', 'snowball')
        ->assertSet('strategy', 'snowball')
        ->call('$set', 'strategy', 'avalanche')
        ->assertSet('strategy', 'avalanche');
});

test('default strategy is avalanche', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 500]);

    $component = Livewire::test(PaymentPlan::class);

    expect($component->get('strategy'))->toBe('avalanche');
});

test('displays strategy toggle buttons', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 500]);

    Livewire::test(PaymentPlan::class)
        ->assertSee('Selected Strategy')
        ->assertSee('Avalanche Method')
        ->assertSee('Snowball Method');
});

test('displays extra monthly payment amount', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 500]);

    Livewire::test(PaymentPlan::class)
        ->assertSee('Extra Monthly Payment')
        ->assertSee('2 000 kr');
});
