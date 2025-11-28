<?php

use App\Livewire\CreateDebt;
use App\Livewire\EditDebt;
use App\Models\Debt;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

describe('kredittkort minimum payment validation', function () {
    it('accepts minimum payment of exactly 300 kr for low balance', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Card')
            ->set('type', 'kredittkort')
            ->set('balance', '1000')
            ->set('interestRate', '20')
            ->set('minimumPayment', '300')
            ->call('save')
            ->assertHasNoErrors();

        assertDatabaseHas('debts', [
            'name' => 'Test Card',
            'type' => 'kredittkort',
            'minimum_payment' => 300,
        ]);
    });

    it('accepts minimum payment of 3% for balance above 10000', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Card')
            ->set('type', 'kredittkort')
            ->set('balance', '50000')
            ->set('interestRate', '20')
            ->set('minimumPayment', '1500')
            ->call('save')
            ->assertHasNoErrors();

        assertDatabaseHas('debts', [
            'name' => 'Test Card',
            'type' => 'kredittkort',
            'minimum_payment' => 1500,
        ]);
    });

    it('rejects minimum payment below 300 kr', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Card')
            ->set('type', 'kredittkort')
            ->set('balance', '5000')
            ->set('interestRate', '20')
            ->set('minimumPayment', '250')
            ->call('save')
            ->assertHasErrors(['minimumPayment']);
    });

    it('rejects minimum payment below 3% for high balance', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Card')
            ->set('type', 'kredittkort')
            ->set('balance', '50000')
            ->set('interestRate', '20')
            ->set('minimumPayment', '1000')
            ->call('save')
            ->assertHasErrors(['minimumPayment']);
    });

    it('accepts minimum payment slightly above requirement', function () {
        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Card')
            ->set('type', 'kredittkort')
            ->set('balance', '10000')
            ->set('interestRate', '20')
            ->set('minimumPayment', '350')
            ->call('save')
            ->assertHasNoErrors();
    });
});

describe('forbrukslån minimum payment validation', function () {
    it('accepts payment that covers monthly interest with 0% interest', function () {
        $balance = 60000;
        // With 0% interest, monthly interest is 0, so any positive payment works
        $minimumPayment = 1;

        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Loan')
            ->set('type', 'forbrukslån')
            ->set('balance', (string) $balance)
            ->set('interestRate', '0')
            ->set('minimumPayment', (string) $minimumPayment)
            ->call('save')
            ->assertHasNoErrors();
    });

    it('accepts payment that covers monthly interest with interest', function () {
        $balance = 100000;
        $interestRate = 12;
        // Monthly interest = 100000 * (12 / 100) / 12 = 1000
        $monthlyInterest = ($balance * ($interestRate / 100)) / 12;

        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Loan')
            ->set('type', 'forbrukslån')
            ->set('balance', (string) $balance)
            ->set('interestRate', (string) $interestRate)
            ->set('minimumPayment', (string) ($monthlyInterest + 1))
            ->call('save')
            ->assertHasNoErrors();
    });

    it('rejects payment below monthly interest', function () {
        // Monthly interest = 100000 * (15 / 100) / 12 = 1250
        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Loan')
            ->set('type', 'forbrukslån')
            ->set('balance', '100000')
            ->set('interestRate', '15')
            ->set('minimumPayment', '1250') // Equal to monthly interest, should fail
            ->call('save')
            ->assertHasErrors(['minimumPayment']);
    });

    it('handles high interest rate calculation correctly', function () {
        $balance = 50000;
        $interestRate = 25;
        // Monthly interest = 50000 * (25 / 100) / 12 = 1041.67
        $monthlyInterest = ($balance * ($interestRate / 100)) / 12;

        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Loan')
            ->set('type', 'forbrukslån')
            ->set('balance', (string) $balance)
            ->set('interestRate', (string) $interestRate)
            ->set('minimumPayment', (string) ceil($monthlyInterest + 1))
            ->call('save')
            ->assertHasNoErrors();
    });
});

describe('editing debts with validation', function () {
    it('allows updating compliant debt with new compliant values', function () {
        $debt = Debt::factory()->create([
            'name' => 'Old Name',
            'type' => 'kredittkort',
            'balance' => 10000,
            'interest_rate' => 20,
            'minimum_payment' => 300,
        ]);

        Livewire::test(EditDebt::class, ['debt' => $debt])
            ->set('name', 'New Name')
            // Balance is read-only, not editable
            ->set('minimumPayment', '450')
            ->call('update')
            ->assertHasNoErrors();

        expect($debt->fresh())
            ->name->toBe('New Name')
            ->balance->toBe(10000.0) // Balance should not change
            ->minimum_payment->toBe(450.0);
    });

    it('prevents updating debt to non-compliant values', function () {
        $debt = Debt::factory()->create([
            'type' => 'kredittkort',
            'balance' => 50000, // Set high balance for testing validation
            'interest_rate' => 20,
            'minimum_payment' => 1500,
        ]);

        // For kredittkort with 50000 balance, minimum payment must be at least 1500 (3% of 50000)
        Livewire::test(EditDebt::class, ['debt' => $debt])
            // Balance is read-only, uses debt->balance (50000) for validation
            ->set('minimumPayment', '500') // Too low for 50000 balance
            ->call('update')
            ->assertHasErrors(['minimumPayment']);
    });

    it('allows changing debt type and adjusting minimum payment', function () {
        $debt = Debt::factory()->create([
            'type' => 'kredittkort',
            'balance' => 10000,
            'interest_rate' => 5,
            'minimum_payment' => 300,
        ]);

        // For forbrukslån with 10000 balance and 5% interest, calculate proper minimum
        $monthlyRate = (5 / 100) / 12;
        $requiredMinimum = ($monthlyRate * 10000) / (1 - pow(1 + $monthlyRate, -60));

        Livewire::test(EditDebt::class, ['debt' => $debt])
            ->set('type', 'forbrukslån')
            ->set('minimumPayment', (string) ceil($requiredMinimum))
            ->call('update')
            ->assertHasNoErrors();

        expect($debt->fresh())
            ->type->toBe('forbrukslån');
    });
});

describe('debt model compliance methods', function () {
    it('correctly identifies compliant kredittkort debt', function () {
        $debt = Debt::factory()->create([
            'type' => 'kredittkort',
            'balance' => 10000,
            'interest_rate' => 20,
            'minimum_payment' => 300,
        ]);

        expect($debt->isMinimumPaymentCompliant())->toBeTrue();
        expect($debt->getMinimumPaymentWarning())->toBeNull();
    });

    it('correctly identifies non-compliant kredittkort debt', function () {
        $debt = Debt::factory()->create([
            'type' => 'kredittkort',
            'balance' => 10000,
            'interest_rate' => 20,
            'minimum_payment' => 200,
        ]);

        expect($debt->isMinimumPaymentCompliant())->toBeFalse();
        expect($debt->getMinimumPaymentWarning())->not->toBeNull();
    });

    it('correctly calculates minimum for kredittkort with low balance', function () {
        $debt = Debt::factory()->create([
            'type' => 'kredittkort',
            'balance' => 5000,
            'interest_rate' => 20,
        ]);

        expect($debt->calculateMinimumPaymentForType())->toBe(300.0);
    });

    it('correctly calculates minimum for kredittkort with high balance', function () {
        $debt = Debt::factory()->create([
            'type' => 'kredittkort',
            'balance' => 50000,
            'interest_rate' => 20,
        ]);

        expect($debt->calculateMinimumPaymentForType())->toBe(1500.0);
    });

    it('correctly calculates minimum for forbrukslån with no interest', function () {
        $debt = Debt::factory()->create([
            'type' => 'forbrukslån',
            'balance' => 60000,
            'interest_rate' => 0,
        ]);

        // With 0% interest, minimum is balance / 60 months = 1000
        expect($debt->calculateMinimumPaymentForType())->toBe(1000.0);
    });

    it('correctly calculates minimum for forbrukslån with interest', function () {
        $debt = Debt::factory()->create([
            'type' => 'forbrukslån',
            'balance' => 100000,
            'interest_rate' => 12,
        ]);

        // Using amortization formula: P = (r * PV) / (1 - (1 + r)^-n)
        // where r = 0.01 (monthly), PV = 100000, n = 60
        $monthlyRate = (12 / 100) / 12;
        $expected = round(($monthlyRate * 100000) / (1 - pow(1 + $monthlyRate, -60)), 2);

        expect($debt->calculateMinimumPaymentForType())->toBe($expected);
    });
});
