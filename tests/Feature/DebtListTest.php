<?php

use App\Livewire\DebtList;
use Livewire\Livewire;

test('debt list component renders successfully', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
});

test('displays mock debts correctly', function () {
    Livewire::test(DebtList::class)
        ->assertSee('325 000 kr')
        ->assertSee('Kredittkort')
        ->assertSee('50 000 kr')
        ->assertSee('Studielån')
        ->assertSee('200 000 kr')
        ->assertSee('Billån')
        ->assertSee('75 000 kr');
});

test('displays debt details correctly', function () {
    Livewire::test(DebtList::class)
        ->assertSee('8,5 %')
        ->assertSee('2,5 %')
        ->assertSee('5,0 %')
        ->assertSee('500 kr')
        ->assertSee('1 200 kr');
});

test('can delete a debt', function () {
    Livewire::test(DebtList::class)
        ->assertSee('Kredittkort')
        ->call('deleteDebt', 1)
        ->assertDontSee('Kredittkort')
        ->assertSee('275 000 kr');
});

test('shows correct debts count with pluralization', function () {
    $component = Livewire::test(DebtList::class);

    expect($component->get('debtsCount'))->toBe(3);
    $component->assertSee('debt');
});

test('calculates total debt correctly', function () {
    $component = Livewire::test(DebtList::class);

    expect($component->get('totalDebt'))->toBe(325000);
    expect($component->get('debtsCount'))->toBe(3);
});

test('shows empty state when no debts exist', function () {
    Livewire::test(DebtList::class)
        ->set('debts', [])
        ->assertSee('No debts registered')
        ->assertSee('Add first debt')
        ->assertDontSee('Total Debt');
});
