<?php

use App\Livewire\Debts\DebtLayout;
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
    app()->setLocale('en');

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
        ->assertSee('Update Debt'); // Button text instead of header
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
        // Balance is read-only, not editable
        ->set('interestRate', '7.5')
        ->set('minimumPayment', '450')
        ->call('update')
        ->assertDispatched('debtUpdated'); // SPA mode dispatches event instead of redirect

    $debt->refresh();

    expect($debt->name)->toBe('New Name');
    expect($debt->type)->toBe('kredittkort');
    expect($debt->balance)->toBe(10000.0); // Balance should not change
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
        // Balance is read-only, not editable
        ->set('interestRate', '6.0')
        ->set('minimumPayment', '')
        ->call('update')
        ->assertHasErrors(['minimumPayment' => 'required']);
});

test('validates required fields', function () {
    $debt = Debt::factory()->create();

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('name', '')
        // Balance is read-only, not validated
        ->set('interestRate', '')
        ->call('update')
        ->assertHasErrors(['name', 'interestRate']);
});

// Note: Balance validation test removed - balance is now read-only and calculated from payments

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

test('edit button uses SPA mode with parent editDebt call', function () {
    $debt = Debt::factory()->create(['name' => 'Test Debt']);

    $response = $this->get('/');

    // SPA mode uses wire:click to call $parent.editDebt() instead of link
    $response->assertSee('$parent.editDebt('.$debt->id.')');
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
        // Balance is read-only, uses debt->balance for validation
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
        // Balance is read-only, uses debt->balance (10000) for validation
        ->set('interestRate', '10.0')
        ->set('minimumPayment', '83') // Less than monthly interest
        ->call('update')
        ->assertHasErrors(['minimumPayment']);

    // Should SUCCEED: Using minimum that covers monthly interest
    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('type', 'forbrukslån')
        // Balance is read-only, uses debt->balance (10000) for validation
        ->set('interestRate', '10.0')
        ->set('minimumPayment', '84') // More than monthly interest (83.33)
        ->call('update')
        ->assertHasNoErrors();
});

test('balance field is read-only and cannot be changed', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'type' => 'kredittkort',
        'balance' => 10000,
        'interest_rate' => 5.0,
        'minimum_payment' => 300,
    ]);

    // Try to update the balance field - it should not be included in the update
    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('name', 'Updated Name')
        ->set('balance', '50000') // Try to change balance
        ->set('interestRate', '6.0')
        ->set('minimumPayment', '400')
        ->call('update')
        ->assertDispatched('debtUpdated'); // SPA mode dispatches event instead of redirect

    $debt->refresh();

    // Balance should remain unchanged
    expect($debt->balance)->toBe(10000.0);
    // Other fields should update normally
    expect($debt->name)->toBe('Updated Name');
    expect($debt->interest_rate)->toBe(6.0);
    expect($debt->minimum_payment)->toBe(400.0);
});

test('balance field displays current debt balance', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 25000.50,
    ]);

    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->assertSet('balance', '25000.5')
        ->assertSee('25000.5'); // Should display the balance value
});

test('minimum payment validation displays rounded up value that will actually pass', function () {
    // Test case: Balance of 23175 kr requires 695.25 kr minimum (3% of balance)
    // The error message should show 696 kr (rounded up), not 695 kr
    $debt = Debt::factory()->create([
        'name' => 'Test Kredittkort',
        'type' => 'kredittkort',
        'balance' => 23175, // 3% = 695.25
        'interest_rate' => 15.0,
        'minimum_payment' => 700,
    ]);

    // Entering 695 should fail with error showing 696
    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('type', 'kredittkort')
        ->set('interestRate', '15.0')
        ->set('minimumPayment', '695')
        ->call('update')
        ->assertHasErrors(['minimumPayment'])
        ->assertSee('696'); // Should display rounded up value

    // Entering 696 should pass
    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->set('type', 'kredittkort')
        ->set('interestRate', '15.0')
        ->set('minimumPayment', '696')
        ->call('update')
        ->assertHasNoErrors();
});

test('edit debt component can be initialized with debt model parameter', function () {
    $debt = Debt::factory()->create([
        'name' => 'SPA Mode Test',
        'type' => 'kredittkort',
        'balance' => 5000,
        'interest_rate' => 10.0,
        'minimum_payment' => 300,
    ]);

    // Test initialization with debt model (both SPA mode via view and direct URL use this)
    // The debtId parameter is resolved by Livewire when rendered in the view
    Livewire::test(EditDebt::class, ['debt' => $debt])
        ->assertSet('name', 'SPA Mode Test')
        ->assertSet('type', 'kredittkort')
        ->assertSet('balance', '5000')
        ->assertSet('interestRate', '10')
        ->assertSet('minimumPayment', '300');
});

test('DebtLayout component editDebt sets correct state', function () {
    $debt = Debt::factory()->create();

    Livewire::test(DebtLayout::class)
        ->assertSet('currentView', 'overview')
        ->assertSet('editingDebtId', null)
        ->call('editDebt', $debt->id)
        ->assertSet('currentView', 'edit')
        ->assertSet('editingDebtId', $debt->id);
});

test('DebtLayout component cancelEdit returns to overview', function () {
    $debt = Debt::factory()->create();

    Livewire::test(DebtLayout::class)
        ->call('editDebt', $debt->id)
        ->assertSet('currentView', 'edit')
        ->call('cancelEdit')
        ->assertSet('currentView', 'overview')
        ->assertSet('editingDebtId', null);
});

test('DebtLayout component onDebtUpdated returns to overview', function () {
    $debt = Debt::factory()->create();

    Livewire::test(DebtLayout::class)
        ->call('editDebt', $debt->id)
        ->assertSet('currentView', 'edit')
        ->call('onDebtUpdated')
        ->assertSet('currentView', 'overview')
        ->assertSet('editingDebtId', null);
});
