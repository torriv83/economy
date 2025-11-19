<?php

use App\Models\SelfLoan\SelfLoan;
use App\Models\SelfLoan\SelfLoanRepayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('self loan has repayments relationship', function () {
    $loan = SelfLoan::factory()->create();

    SelfLoanRepayment::factory()->count(3)->create([
        'self_loan_id' => $loan->id,
    ]);

    expect($loan->repayments()->count())->toBe(3);
});

test('isPaidOff returns true when balance is zero', function () {
    $loan = SelfLoan::factory()->create(['current_balance' => 0]);

    expect($loan->isPaidOff())->toBeTrue();
});

test('isPaidOff returns false when balance is positive', function () {
    $loan = SelfLoan::factory()->create(['current_balance' => 1000]);

    expect($loan->isPaidOff())->toBeFalse();
});

test('getTotalRepaidAmount calculates correctly', function () {
    $loan = SelfLoan::factory()->create([
        'original_amount' => 10000,
        'current_balance' => 3000,
    ]);

    expect($loan->getTotalRepaidAmount())->toBe(7000.0);
});

test('getProgressPercentage calculates correctly', function () {
    $loan = SelfLoan::factory()->create([
        'original_amount' => 10000,
        'current_balance' => 3000,
    ]);

    expect($loan->getProgressPercentage())->toBe(70.0);
});

test('getProgressPercentage returns zero when original amount is zero', function () {
    $loan = SelfLoan::factory()->create([
        'original_amount' => 0,
        'current_balance' => 0,
    ]);

    expect($loan->getProgressPercentage())->toBe(0.0);
});

test('self loan repayment belongs to self loan', function () {
    $loan = SelfLoan::factory()->create();
    $repayment = SelfLoanRepayment::factory()->create(['self_loan_id' => $loan->id]);

    expect($repayment->selfLoan->id)->toBe($loan->id);
});

test('deleting self loan cascades to repayments', function () {
    $loan = SelfLoan::factory()->create();
    SelfLoanRepayment::factory()->count(3)->create(['self_loan_id' => $loan->id]);

    expect(SelfLoanRepayment::count())->toBe(3);

    $loan->delete();

    expect(SelfLoanRepayment::count())->toBe(0);
});
