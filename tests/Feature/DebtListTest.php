<?php

use App\Livewire\DebtList;
use App\Models\Debt;
use App\Services\DebtCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    DebtCacheService::clearCache();
});

test('debt list component renders successfully', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
});

test('displays mock debts correctly', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);
    Debt::factory()->create(['name' => 'Studielån', 'type' => 'forbrukslån', 'balance' => 200000, 'interest_rate' => 2.5, 'minimum_payment' => 3500]);
    Debt::factory()->create(['name' => 'Billån', 'type' => 'forbrukslån', 'balance' => 75000, 'interest_rate' => 5.0, 'minimum_payment' => 1500]);

    Livewire::test(DebtList::class)
        ->assertSee('325 000')
        ->assertSee('Kredittkort')
        ->assertSee('50 000')
        ->assertSee('Studielån')
        ->assertSee('200 000')
        ->assertSee('Billån')
        ->assertSee('75 000');
});

test('displays debt details correctly', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);
    Debt::factory()->create(['name' => 'Studielån', 'type' => 'forbrukslån', 'balance' => 200000, 'interest_rate' => 2.5, 'minimum_payment' => 3500]);
    Debt::factory()->create(['name' => 'Billån', 'type' => 'forbrukslån', 'balance' => 75000, 'interest_rate' => 5.0, 'minimum_payment' => 1500]);

    Livewire::test(DebtList::class)
        ->assertSee('8,5%')
        ->assertSee('2,5%')
        ->assertSee('5,0%')
        ->assertSee('1 500 kr')
        ->assertSee('3 500 kr');
});

test('can delete a debt', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);
    Debt::factory()->create(['name' => 'Studielån', 'type' => 'forbrukslån', 'balance' => 200000, 'interest_rate' => 2.5, 'minimum_payment' => 3500]);
    $billaan = Debt::factory()->create(['name' => 'Billån', 'type' => 'forbrukslån', 'balance' => 75000, 'interest_rate' => 5.0, 'minimum_payment' => 1500]);

    Livewire::test(DebtList::class)
        ->assertSee('Billån')
        ->call('confirmDelete', $billaan->id, 'Billån')
        ->call('executeDelete')
        ->assertDontSee('Billån')
        ->assertSee('250 000')
        ->assertSee('kr');
});

test('shows correct debts count with pluralization', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);
    Debt::factory()->create(['name' => 'Studielån', 'type' => 'forbrukslån', 'balance' => 200000, 'interest_rate' => 2.5, 'minimum_payment' => 3500]);
    Debt::factory()->create(['name' => 'Billån', 'type' => 'forbrukslån', 'balance' => 75000, 'interest_rate' => 5.0, 'minimum_payment' => 1500]);

    $component = Livewire::test(DebtList::class);

    expect($component->get('debtsCount'))->toBe(3);
    $component->assertSee('debt');
});

test('calculates total debt correctly', function () {
    Debt::factory()->create(['name' => 'Kredittkort', 'type' => 'kredittkort', 'balance' => 50000, 'interest_rate' => 8.5, 'minimum_payment' => 1500]);
    Debt::factory()->create(['name' => 'Studielån', 'type' => 'forbrukslån', 'balance' => 200000, 'interest_rate' => 2.5, 'minimum_payment' => 3500]);
    Debt::factory()->create(['name' => 'Billån', 'type' => 'forbrukslån', 'balance' => 75000, 'interest_rate' => 5.0, 'minimum_payment' => 1500]);

    $component = Livewire::test(DebtList::class);

    expect($component->get('totalDebt'))->toBe(325000.0);
    expect($component->get('debtsCount'))->toBe(3);
});

test('shows empty state when no debts exist', function () {
    app()->setLocale('en');

    Livewire::test(DebtList::class)
        ->assertSee('No debts registered')
        ->assertSee('Add first debt')
        ->assertDontSee('Total Debt');
});
