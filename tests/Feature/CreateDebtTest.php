<?php

use App\Livewire\CreateDebt;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('CreateDebt Component', function () {
    it('renders successfully', function () {
        $response = $this->get(route('debts.create'));

        $response->assertSuccessful();
        $response->assertSee('Create Debt');
    });

    it('creates a debt with valid data', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '50000')
            ->set('interestRate', '8.5')
            ->set('minimumPayment', '500')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('debts', [
            'name' => 'Kredittkort',
            'balance' => 50000,
            'interest_rate' => 8.5,
            'minimum_payment' => 500,
        ]);
    });

    it('creates a debt without minimum payment', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Studielån')
            ->set('balance', '200000')
            ->set('interestRate', '2.5')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('debts', [
            'name' => 'Studielån',
            'balance' => 200000,
            'interest_rate' => 2.5,
            'minimum_payment' => null,
        ]);
    });

    it('sets flash message after successful creation', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Billån')
            ->set('balance', '75000')
            ->set('interestRate', '5.0')
            ->call('save')
            ->assertSessionHas('message', 'Gjeld lagt til.');
    });

    it('sets original_balance equal to balance on creation', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Debt')
            ->set('balance', '10000')
            ->set('interestRate', '10')
            ->call('save');

        $debt = Debt::where('name', 'Test Debt')->first();
        expect($debt->original_balance)->toBe(10000.0);
        expect($debt->balance)->toBe(10000.0);
    });
});

describe('CreateDebt Validation', function () {
    it('requires name', function () {
        Livewire::test(CreateDebt::class)
            ->set('balance', '50000')
            ->set('interestRate', '8.5')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    });

    it('requires name to be max 255 characters', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', str_repeat('a', 256))
            ->set('balance', '50000')
            ->set('interestRate', '8.5')
            ->call('save')
            ->assertHasErrors(['name' => 'max']);
    });

    it('requires balance', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('interestRate', '8.5')
            ->call('save')
            ->assertHasErrors(['balance' => 'required']);
    });

    it('requires balance to be numeric', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', 'not-a-number')
            ->set('interestRate', '8.5')
            ->call('save')
            ->assertHasErrors(['balance' => 'numeric']);
    });

    it('requires balance to be at least 0.01', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '0')
            ->set('interestRate', '8.5')
            ->call('save')
            ->assertHasErrors(['balance' => 'min']);
    });

    it('accepts balance of 0.01', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '0.01')
            ->set('interestRate', '0')
            ->call('save')
            ->assertHasNoErrors();
    });

    it('rejects negative balance', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '-100')
            ->set('interestRate', '8.5')
            ->call('save')
            ->assertHasErrors(['balance' => 'min']);
    });

    it('requires interest rate', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '50000')
            ->call('save')
            ->assertHasErrors(['interestRate' => 'required']);
    });

    it('requires interest rate to be numeric', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '50000')
            ->set('interestRate', 'not-a-number')
            ->call('save')
            ->assertHasErrors(['interestRate' => 'numeric']);
    });

    it('requires interest rate to be at least 0', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '50000')
            ->set('interestRate', '-1')
            ->call('save')
            ->assertHasErrors(['interestRate' => 'min']);
    });

    it('accepts zero interest rate', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'No Interest Debt')
            ->set('balance', '10000')
            ->set('interestRate', '0')
            ->call('save')
            ->assertHasNoErrors();
    });

    it('requires interest rate to be max 100', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '50000')
            ->set('interestRate', '101')
            ->call('save')
            ->assertHasErrors(['interestRate' => 'max']);
    });

    it('accepts 100 percent interest rate', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'High Rate Debt')
            ->set('balance', '10000')
            ->set('interestRate', '100')
            ->call('save')
            ->assertHasNoErrors();
    });

    it('allows minimum payment to be optional', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '50000')
            ->set('interestRate', '8.5')
            ->set('minimumPayment', '')
            ->call('save')
            ->assertHasNoErrors();
    });

    it('requires minimum payment to be numeric if provided', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '50000')
            ->set('interestRate', '8.5')
            ->set('minimumPayment', 'not-a-number')
            ->call('save')
            ->assertHasErrors(['minimumPayment' => 'numeric']);
    });

    it('requires minimum payment to be at least 0', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '50000')
            ->set('interestRate', '8.5')
            ->set('minimumPayment', '-100')
            ->call('save')
            ->assertHasErrors(['minimumPayment' => 'min']);
    });

    it('accepts zero minimum payment', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Kredittkort')
            ->set('balance', '50000')
            ->set('interestRate', '8.5')
            ->set('minimumPayment', '0')
            ->call('save')
            ->assertHasNoErrors();
    });
});

describe('CreateDebt Validation Messages', function () {
    it('shows custom error message for required name', function () {
        Livewire::test(CreateDebt::class)
            ->set('balance', '50000')
            ->set('interestRate', '8.5')
            ->call('save')
            ->assertHasErrors(['name'])
            ->assertSee('Navn er påkrevd.');
    });

    it('shows custom error message for minimum balance', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Test')
            ->set('balance', '0')
            ->set('interestRate', '8.5')
            ->call('save')
            ->assertHasErrors(['balance'])
            ->assertSee('Saldo må være minst 0,01 kr.');
    });

    it('shows custom error message for negative interest rate', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Test')
            ->set('balance', '50000')
            ->set('interestRate', '-1')
            ->call('save')
            ->assertHasErrors(['interestRate'])
            ->assertSee('Rente kan ikke være negativ.');
    });

    it('shows custom error message for interest rate over 100%', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Test')
            ->set('balance', '50000')
            ->set('interestRate', '101')
            ->call('save')
            ->assertHasErrors(['interestRate'])
            ->assertSee('Rente kan ikke være mer enn 100%.');
    });
});

describe('CreateDebt Edge Cases', function () {
    it('handles very large balance values', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Massive Debt')
            ->set('balance', '9999999999.99')
            ->set('interestRate', '5')
            ->call('save')
            ->assertHasNoErrors();
    });

    it('handles decimal balance values', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Precise Debt')
            ->set('balance', '12345.67')
            ->set('interestRate', '5.5')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('debts', [
            'name' => 'Precise Debt',
            'balance' => 12345.67,
        ]);
    });

    it('handles decimal interest rate values', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Debt')
            ->set('balance', '10000')
            ->set('interestRate', '8.75')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('debts', [
            'name' => 'Test Debt',
            'interest_rate' => 8.75,
        ]);
    });

    it('trims whitespace from name', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', '  Kredittkort  ')
            ->set('balance', '50000')
            ->set('interestRate', '8.5')
            ->call('save')
            ->assertHasNoErrors();
    });
});
