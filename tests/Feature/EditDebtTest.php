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
        'type' => 'kredittkort',
        'balance' => 50000,
        'interest_rate' => 8.5,
        'minimum_payment' => 1500,
    ]);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->assertSet('name', 'Test Credit Card')
        ->assertSet('type', 'kredittkort')
        ->assertSet('balance', '50000')
        ->assertSet('interestRate', '8.5')
        ->assertSet('minimumPayment', '1500')
        ->assertSee('Edit Debt');
});

test('can update debt with valid data', function () {
    $debt = Debt::factory()->create([
        'name' => 'Old Name',
        'type' => 'kredittkort',
        'balance' => 10000,
        'interest_rate' => 5.0,
        'minimum_payment' => 300,
    ]);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('name', 'New Name')
        ->set('type', 'kredittkort')
        ->set('balance', '15000')
        ->set('interestRate', '7.5')
        ->set('minimumPayment', '450')
        ->call('update')
        ->assertRedirect(route('home'));

    $debt->refresh();

    expect($debt->name)->toBe('New Name');
    expect($debt->type)->toBe('kredittkort');
    expect($debt->balance)->toBe(15000.0);
    expect($debt->interest_rate)->toBe(7.5);
    expect($debt->minimum_payment)->toBe(450.0);
});

test('requires minimum payment when updating debt', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'type' => 'kredittkort',
        'balance' => 10000,
        'interest_rate' => 5.0,
        'minimum_payment' => 300,
    ]);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('name', 'Updated Debt')
        ->set('type', 'kredittkort')
        ->set('balance', '12000')
        ->set('interestRate', '6.0')
        ->set('minimumPayment', '')
        ->call('update')
        ->assertHasErrors(['minimumPayment' => 'required']);
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

test('validates minimum payment is numeric and meets requirements', function () {
    $debt = Debt::factory()->create([
        'type' => 'kredittkort',
        'balance' => 50000,
        'interest_rate' => 8.5,
        'minimum_payment' => 1500,
    ]);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('minimumPayment', '-50')
        ->call('update')
        ->assertHasErrors(['minimumPayment']);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('minimumPayment', 'invalid')
        ->call('update')
        ->assertHasErrors(['minimumPayment']);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('minimumPayment', '200')
        ->call('update')
        ->assertHasErrors(['minimumPayment']);
});

test('edit button links to correct route', function () {
    $debt = Debt::factory()->create(['name' => 'Test Debt']);

    $response = $this->get('/');

    $response->assertSee("/debts/{$debt->id}/edit");
});

test('validation blocks save when minimum payment is below calculated minimum for forbrukslån', function () {
    $debt = Debt::factory()->create([
        'name' => 'Studielån',
        'type' => 'forbrukslån',
        'balance' => 10000,
        'interest_rate' => 10.0,
        'minimum_payment' => 500,
    ]);

    // Try to update with minimum payment below the calculated minimum
    // For a forbrukslån with 10000 balance and 10% interest over 60 months,
    // the required minimum is approximately 212 kr
    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('type', 'forbrukslån')
        ->set('balance', '10000')
        ->set('interestRate', '10.0')
        ->set('minimumPayment', '2') // Way below required minimum
        ->call('update')
        ->assertHasErrors(['minimumPayment']);
});

test('validation uses current balance for forbrukslån (not original balance)', function () {
    // Create a debt with original_balance of 100000, but current balance of 10000 (partially paid)
    $debt = Debt::factory()->create([
        'name' => 'Studielån',
        'type' => 'forbrukslån',
        'original_balance' => 100000, // Original loan amount
        'balance' => 10000, // Only 10k remaining after payments
        'interest_rate' => 10.0,
        'minimum_payment' => 2122, // Based on original 100k amount
    ]);

    // Monthly interest on current balance: 10000 * (10 / 100) / 12 = 83.33 kr
    // Should FAIL: Using minimum that doesn't cover monthly interest
    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('type', 'forbrukslån')
        ->set('balance', '10000')
        ->set('interestRate', '10.0')
        ->set('minimumPayment', '83') // Less than monthly interest
        ->call('update')
        ->assertHasErrors(['minimumPayment']);

    // Should SUCCEED: Using minimum that covers monthly interest
    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('type', 'forbrukslån')
        ->set('balance', '10000')
        ->set('interestRate', '10.0')
        ->set('minimumPayment', '84') // More than monthly interest (83.33)
        ->call('update')
        ->assertHasNoErrors();
});
