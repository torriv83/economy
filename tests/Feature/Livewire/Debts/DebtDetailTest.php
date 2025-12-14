<?php

declare(strict_types=1);

use App\Livewire\Debts\DebtDetail;
use App\Models\Debt;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays debt details', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Credit Card',
        'balance' => 50000,
        'original_balance' => 75000,
        'interest_rate' => 19.9,
        'minimum_payment' => 1500,
        'type' => 'kredittkort',
    ]);

    Livewire::test(DebtDetail::class, ['debt' => $debt, 'embedded' => true])
        ->call('loadData')
        ->assertSee('Test Credit Card')
        ->assertSee('50 000')
        ->assertSee('kr')
        ->assertSee('19,90%')
        ->assertSee('1 500 kr');
});

it('shows recent payments', function () {
    $debt = Debt::factory()->create(['name' => 'Test Debt']);

    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 5000,
        'payment_date' => now()->subDays(5),
    ]);

    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 3000,
        'payment_date' => now()->subDays(10),
    ]);

    Livewire::test(DebtDetail::class, ['debt' => $debt, 'embedded' => true])
        ->call('loadData')
        ->assertSee('5 000 kr')
        ->assertSee('3 000 kr');
});

it('shows no payments message when no payments exist', function () {
    $debt = Debt::factory()->create(['name' => 'Test Debt']);

    Livewire::test(DebtDetail::class, ['debt' => $debt, 'embedded' => true])
        ->call('loadData')
        ->assertSee(__('app.no_payments_yet'));
});

it('calculates what-if scenario', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Loan',
        'balance' => 100000,
        'interest_rate' => 15,
        'minimum_payment' => 2000,
    ]);

    Livewire::test(DebtDetail::class, ['debt' => $debt, 'embedded' => true])
        ->call('loadData')
        ->set('whatIfAmount', 1000)
        ->assertSet('whatIfResult.months_saved', fn ($value) => $value >= 0)
        ->assertSet('whatIfResult.interest_saved', fn ($value) => $value >= 0);
});

it('clears what-if result when amount is zero', function () {
    $debt = Debt::factory()->create(['name' => 'Test Debt']);

    Livewire::test(DebtDetail::class, ['debt' => $debt, 'embedded' => true])
        ->call('loadData')
        ->set('whatIfAmount', 1000)
        ->assertSet('whatIfResult', fn ($value) => $value !== null)
        ->set('whatIfAmount', 0)
        ->assertSet('whatIfResult', null);
});

it('shows edit button', function () {
    $debt = Debt::factory()->create(['name' => 'Test Debt']);

    Livewire::test(DebtDetail::class, ['debt' => $debt, 'embedded' => true])
        ->call('loadData')
        ->assertSee(__('app.edit'));
});

it('calculates total paid from payments', function () {
    $debt = Debt::factory()->create(['name' => 'Test Debt']);

    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 5000,
    ]);

    Payment::factory()->create([
        'debt_id' => $debt->id,
        'actual_amount' => 3000,
    ]);

    $component = Livewire::test(DebtDetail::class, ['debt' => $debt, 'embedded' => true])
        ->call('loadData');

    expect($component->get('totalPaid'))->toBe(8000.0);
});

it('can be accessed via DebtLayout', function () {
    $this->actingAs(User::factory()->create());
    $debt = Debt::factory()->create(['name' => 'Accessible Debt']);

    // HTTP test verifies page loads successfully
    $this->get(route('debts', ['view' => 'detail', 'debtId' => $debt->id]))
        ->assertStatus(200);

    // Livewire test verifies content is shown after loadData
    Livewire::test(DebtDetail::class, ['debt' => $debt, 'embedded' => true])
        ->call('loadData')
        ->assertSee('Accessible Debt');
});
