<?php

use App\Livewire\EditDebt;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('edit debt page renders successfully', function () {
    $debt = Debt::factory()->create();

    $response = $this->get("/debts/{$debt->id}/edit");

    $response->assertSuccessful();
    $response->assertSee('Edit Debt');
});

test('edit debt component loads debt data correctly', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Credit Card',
        'balance' => 50000,
        'interest_rate' => 8.5,
        'minimum_payment' => 500,
    ]);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->assertSet('name', 'Test Credit Card')
        ->assertSet('balance', '50000')
        ->assertSet('interestRate', '8.5')
        ->assertSet('minimumPayment', '500')
        ->assertSee('Edit Debt');
});

test('can update debt with valid data', function () {
    $debt = Debt::factory()->create([
        'name' => 'Old Name',
        'balance' => 10000,
        'interest_rate' => 5.0,
        'minimum_payment' => 200,
    ]);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('name', 'New Name')
        ->set('balance', '15000')
        ->set('interestRate', '7.5')
        ->set('minimumPayment', '300')
        ->call('update')
        ->assertRedirect(route('home'));

    $debt->refresh();

    expect($debt->name)->toBe('New Name');
    expect($debt->balance)->toBe(15000.0);
    expect($debt->interest_rate)->toBe(7.5);
    expect($debt->minimum_payment)->toBe(300.0);
});

test('can update debt without minimum payment', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 10000,
        'interest_rate' => 5.0,
        'minimum_payment' => 200,
    ]);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('name', 'Updated Debt')
        ->set('balance', '12000')
        ->set('interestRate', '6.0')
        ->set('minimumPayment', '')
        ->call('update')
        ->assertRedirect(route('home'));

    $debt->refresh();

    expect($debt->name)->toBe('Updated Debt');
    expect($debt->balance)->toBe(12000.0);
    expect($debt->interest_rate)->toBe(6.0);
    expect($debt->minimum_payment)->toBeNull();
});

test('validates required fields', function () {
    $debt = Debt::factory()->create();

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('name', '')
        ->set('balance', '')
        ->set('interestRate', '')
        ->call('update')
        ->assertHasErrors(['name', 'balance', 'interestRate']);
});

test('validates balance is numeric and positive', function () {
    $debt = Debt::factory()->create();

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('balance', '-100')
        ->call('update')
        ->assertHasErrors(['balance']);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('balance', 'not-a-number')
        ->call('update')
        ->assertHasErrors(['balance']);
});

test('validates interest rate is within valid range', function () {
    $debt = Debt::factory()->create();

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('interestRate', '-1')
        ->call('update')
        ->assertHasErrors(['interestRate']);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('interestRate', '101')
        ->call('update')
        ->assertHasErrors(['interestRate']);
});

test('validates minimum payment is numeric and non-negative', function () {
    $debt = Debt::factory()->create();

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('minimumPayment', '-50')
        ->call('update')
        ->assertHasErrors(['minimumPayment']);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('minimumPayment', 'invalid')
        ->call('update')
        ->assertHasErrors(['minimumPayment']);
});

test('edit button links to correct route', function () {
    $debt = Debt::factory()->create(['name' => 'Test Debt']);

    $response = $this->get('/');

    $response->assertSee("/debts/{$debt->id}/edit");
});
