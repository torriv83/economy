<?php

use App\Livewire\StrategyComparison;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('strategy comparison component renders successfully', function () {
    app()->setLocale('en');

    $response = $this->get('/strategies');

    $response->assertSuccessful();
    $response->assertSee('Payoff Strategies');
});

test('displays minimum payment months when debts exist', function () {
    Debt::factory()->create([
        'name' => 'Credit Card',
        'type' => 'kredittkort',
        'balance' => 5000,
        'original_balance' => 5000,
        'interest_rate' => 18.0,
        'minimum_payment' => 300,
    ]);

    $component = Livewire::test(StrategyComparison::class);

    $minimumMonths = $component->get('minimumPaymentMonths');

    expect($minimumMonths)->toBeInt()
        ->and($minimumMonths)->toBeGreaterThan(0);
});

test('displays minimum payment interest when debts exist', function () {
    Debt::factory()->create([
        'name' => 'Credit Card',
        'type' => 'kredittkort',
        'balance' => 5000,
        'original_balance' => 5000,
        'interest_rate' => 18.0,
        'minimum_payment' => 300,
    ]);

    $component = Livewire::test(StrategyComparison::class);

    $minimumInterest = $component->get('minimumPaymentInterest');

    expect($minimumInterest)->toBeFloat()
        ->and($minimumInterest)->toBeGreaterThan(0);
});

test('calculates snowball savings correctly', function () {
    Debt::factory()->create([
        'name' => 'Small Debt',
        'type' => 'kredittkort',
        'balance' => 2000,
        'original_balance' => 2000,
        'interest_rate' => 15.0,
        'minimum_payment' => 300,
    ]);

    Debt::factory()->create([
        'name' => 'Large Debt',
        'type' => 'forbrukslån',
        'balance' => 8000,
        'original_balance' => 8000,
        'interest_rate' => 10.0,
        'minimum_payment' => 200,
    ]);

    $component = Livewire::test(StrategyComparison::class)
        ->set('extraPayment', 500);

    $snowballSavings = $component->get('snowballSavings');

    expect($snowballSavings)->toBeArray()
        ->and($snowballSavings)->toHaveKeys(['monthsSaved', 'yearsSaved', 'remainingMonths', 'interestSaved'])
        ->and($snowballSavings['monthsSaved'])->toBeInt()
        ->and($snowballSavings['yearsSaved'])->toBeInt()
        ->and($snowballSavings['remainingMonths'])->toBeInt()
        ->and($snowballSavings['interestSaved'])->toBeFloat()
        ->and($snowballSavings['monthsSaved'])->toBeGreaterThanOrEqual(0)
        ->and($snowballSavings['interestSaved'])->toBeGreaterThanOrEqual(0);
});

test('calculates avalanche savings correctly', function () {
    Debt::factory()->create([
        'name' => 'Low Interest',
        'type' => 'forbrukslån',
        'balance' => 8000,
        'original_balance' => 8000,
        'interest_rate' => 5.0,
        'minimum_payment' => 170,
    ]);

    Debt::factory()->create([
        'name' => 'High Interest',
        'type' => 'kredittkort',
        'balance' => 3000,
        'original_balance' => 3000,
        'interest_rate' => 20.0,
        'minimum_payment' => 300,
    ]);

    $component = Livewire::test(StrategyComparison::class)
        ->set('extraPayment', 500);

    $avalancheSavings = $component->get('avalancheSavings');

    expect($avalancheSavings)->toBeArray()
        ->and($avalancheSavings)->toHaveKeys(['monthsSaved', 'yearsSaved', 'remainingMonths', 'interestSaved'])
        ->and($avalancheSavings['monthsSaved'])->toBeInt()
        ->and($avalancheSavings['yearsSaved'])->toBeInt()
        ->and($avalancheSavings['remainingMonths'])->toBeInt()
        ->and($avalancheSavings['interestSaved'])->toBeFloat()
        ->and($avalancheSavings['monthsSaved'])->toBeGreaterThanOrEqual(0)
        ->and($avalancheSavings['interestSaved'])->toBeGreaterThanOrEqual(0);
});

test('avalanche saves more interest than snowball', function () {
    Debt::factory()->create([
        'name' => 'Low Interest Large',
        'type' => 'forbrukslån',
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 5.0,
        'minimum_payment' => 210,
    ]);

    Debt::factory()->create([
        'name' => 'High Interest Small',
        'type' => 'kredittkort',
        'balance' => 2000,
        'original_balance' => 2000,
        'interest_rate' => 25.0,
        'minimum_payment' => 300,
    ]);

    $component = Livewire::test(StrategyComparison::class)
        ->set('extraPayment', 300);

    $snowballSavings = $component->get('snowballSavings');
    $avalancheSavings = $component->get('avalancheSavings');

    // Avalanche should save more or equal interest than snowball
    expect($avalancheSavings['interestSaved'])->toBeGreaterThanOrEqual($snowballSavings['interestSaved']);
});

test('handles empty debts gracefully', function () {
    $component = Livewire::test(StrategyComparison::class);

    expect($component->get('minimumPaymentMonths'))->toBe(0)
        ->and($component->get('minimumPaymentInterest'))->toBe(0.0)
        ->and($component->get('snowballSavings'))->toBeArray()
        ->and($component->get('avalancheSavings'))->toBeArray();
});

test('savings update when extra payment changes', function () {
    Debt::factory()->create([
        'name' => 'Credit Card',
        'type' => 'kredittkort',
        'balance' => 5000,
        'original_balance' => 5000,
        'interest_rate' => 18.0,
        'minimum_payment' => 300,
    ]);

    $component = Livewire::test(StrategyComparison::class)
        ->set('extraPayment', 200);

    $initialSnowballSavings = $component->get('snowballSavings');

    $component->set('extraPayment', 500);

    $updatedSnowballSavings = $component->get('snowballSavings');

    // With more extra payment, should save more months
    expect($updatedSnowballSavings['monthsSaved'])->toBeGreaterThan($initialSnowballSavings['monthsSaved']);
});

test('years and remaining months are calculated correctly', function () {
    Debt::factory()->create([
        'name' => 'Test Debt',
        'type' => 'forbrukslån',
        'balance' => 10000,
        'original_balance' => 10000,
        'interest_rate' => 12.0,
        'minimum_payment' => 250,
    ]);

    $component = Livewire::test(StrategyComparison::class)
        ->set('extraPayment', 500);

    $snowballSavings = $component->get('snowballSavings');

    // Verify math: monthsSaved = yearsSaved * 12 + remainingMonths
    $calculatedMonths = ($snowballSavings['yearsSaved'] * 12) + $snowballSavings['remainingMonths'];
    expect($calculatedMonths)->toBe($snowballSavings['monthsSaved']);
});

test('displays savings in UI when debts exist', function () {
    app()->setLocale('en');

    Debt::factory()->create([
        'name' => 'Credit Card',
        'type' => 'kredittkort',
        'balance' => 5000,
        'original_balance' => 5000,
        'interest_rate' => 18.0,
        'minimum_payment' => 300,
    ]);

    $component = Livewire::test(StrategyComparison::class)
        ->set('extraPayment', 500);

    // Check if savings are displayed in the UI
    $component->assertSee('Faster than minimum payments')
        ->assertSee('Interest Saved');
});

test('uses current balance for projections', function () {
    // Create debt with different balance and original_balance
    Debt::factory()->create([
        'name' => 'Partially Paid',
        'type' => 'forbrukslån',
        'balance' => 5000,
        'original_balance' => 10000,
        'interest_rate' => 15.0,
        'minimum_payment' => 250,
    ]);

    $component = Livewire::test(StrategyComparison::class);

    $minimumMonths = $component->get('minimumPaymentMonths');
    $minimumInterest = $component->get('minimumPaymentInterest');

    // Should calculate based on current balance (5000), not original_balance (10000)
    // 5000 at 15% with 250/month ≈ 24 months, interest ≈ 1000
    expect($minimumMonths)->toBeGreaterThan(20)
        ->and($minimumMonths)->toBeLessThan(30)
        ->and($minimumInterest)->toBeGreaterThan(900)
        ->and($minimumInterest)->toBeLessThan(1100);
});

test('validates extra payment input', function () {
    // Test negative value
    Livewire::test(StrategyComparison::class)
        ->set('extraPayment', -100)
        ->assertHasErrors('extraPayment');

    // Test value above maximum
    Livewire::test(StrategyComparison::class)
        ->set('extraPayment', 1500000)
        ->assertHasErrors('extraPayment');

    // Test valid value
    Livewire::test(StrategyComparison::class)
        ->set('extraPayment', 2000)
        ->assertHasNoErrors('extraPayment');
});
