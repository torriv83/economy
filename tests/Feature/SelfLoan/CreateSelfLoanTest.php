<?php

use App\Livewire\SelfLoans\CreateSelfLoan;
use App\Models\SelfLoan\SelfLoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('can create a self-loan with all fields', function () {
    Livewire::test(CreateSelfLoan::class)
        ->set('name', 'Emergency Fund Withdrawal')
        ->set('description', 'Borrowed for urgent car repair')
        ->set('amount', 5000)
        ->call('createLoan');

    $loan = SelfLoan::first();

    expect($loan)->not->toBeNull();
    expect($loan->name)->toBe('Emergency Fund Withdrawal');
    expect($loan->description)->toBe('Borrowed for urgent car repair');
    expect($loan->original_amount)->toBe(5000.0);
    expect($loan->current_balance)->toBe(5000.0);
});

test('can create a self-loan without description', function () {
    Livewire::test(CreateSelfLoan::class)
        ->set('name', 'Quick Loan')
        ->set('amount', 1000)
        ->call('createLoan');

    $loan = SelfLoan::first();

    expect($loan)->not->toBeNull();
    expect($loan->name)->toBe('Quick Loan');
    expect($loan->description)->toBe('');
});

test('name is required', function () {
    Livewire::test(CreateSelfLoan::class)
        ->set('amount', 5000)
        ->call('createLoan')
        ->assertHasErrors(['name']);
});

test('amount is required', function () {
    Livewire::test(CreateSelfLoan::class)
        ->set('name', 'Test Loan')
        ->call('createLoan')
        ->assertHasErrors(['amount']);
});

test('amount must be numeric', function () {
    Livewire::test(CreateSelfLoan::class)
        ->set('name', 'Test Loan')
        ->set('amount', 'not-a-number')
        ->call('createLoan')
        ->assertHasErrors(['amount']);
});

test('amount must be positive', function () {
    Livewire::test(CreateSelfLoan::class)
        ->set('name', 'Test Loan')
        ->set('amount', -100)
        ->call('createLoan')
        ->assertHasErrors(['amount']);
});

test('form resets after successful creation', function () {
    $component = Livewire::test(CreateSelfLoan::class)
        ->set('name', 'Test Loan')
        ->set('description', 'Test description')
        ->set('amount', 5000)
        ->call('createLoan');

    expect($component->get('name'))->toBe('');
    expect($component->get('description'))->toBe('');
    expect($component->get('amount'))->toBe('');
});
