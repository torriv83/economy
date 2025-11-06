<?php

use App\Rules\MinimumPaymentRule;
use Illuminate\Support\Facades\Validator;

describe('MinimumPaymentRule for kredittkort', function () {
    it('passes validation when minimum meets exactly 300 kr floor', function () {
        $rule = new MinimumPaymentRule('kredittkort', 1000, 20);

        $validator = Validator::make(
            ['minimumPayment' => 300],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation when minimum meets 3% requirement', function () {
        $rule = new MinimumPaymentRule('kredittkort', 50000, 20);

        $validator = Validator::make(
            ['minimumPayment' => 1500],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('fails validation when minimum is below 300 kr', function () {
        $rule = new MinimumPaymentRule('kredittkort', 5000, 20);

        $validator = Validator::make(
            ['minimumPayment' => 250],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails validation when minimum is below 3% for high balance', function () {
        $rule = new MinimumPaymentRule('kredittkort', 50000, 20);

        $validator = Validator::make(
            ['minimumPayment' => 1000],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('passes with minimum slightly above requirement', function () {
        $rule = new MinimumPaymentRule('kredittkort', 10000, 20);

        $validator = Validator::make(
            ['minimumPayment' => 350],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->passes())->toBeTrue();
    });
});

describe('MinimumPaymentRule for forbrukslån', function () {
    it('passes validation for 60-month payment with zero interest', function () {
        $balance = 60000;
        $expectedMinimum = $balance / 60;

        $rule = new MinimumPaymentRule('forbrukslån', $balance, 0);

        $validator = Validator::make(
            ['minimumPayment' => $expectedMinimum],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for 60-month amortized payment with interest', function () {
        $balance = 100000;
        $interestRate = 12;
        $monthlyRate = ($interestRate / 100) / 12;
        $calculatedMinimum = ($monthlyRate * $balance) / (1 - pow(1 + $monthlyRate, -60));

        $rule = new MinimumPaymentRule('forbrukslån', $balance, $interestRate);

        $validator = Validator::make(
            ['minimumPayment' => ceil($calculatedMinimum)],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('fails validation when payment is insufficient for 60-month payoff', function () {
        $rule = new MinimumPaymentRule('forbrukslån', 100000, 15);

        $validator = Validator::make(
            ['minimumPayment' => 1500],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('handles high interest rates correctly', function () {
        $balance = 50000;
        $interestRate = 25;
        $monthlyRate = ($interestRate / 100) / 12;
        $calculatedMinimum = ($monthlyRate * $balance) / (1 - pow(1 + $monthlyRate, -60));

        $rule = new MinimumPaymentRule('forbrukslån', $balance, $interestRate);

        $validator = Validator::make(
            ['minimumPayment' => ceil($calculatedMinimum)],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('handles low interest rates correctly', function () {
        $balance = 30000;
        $interestRate = 3;
        $monthlyRate = ($interestRate / 100) / 12;
        $calculatedMinimum = ($monthlyRate * $balance) / (1 - pow(1 + $monthlyRate, -60));

        $rule = new MinimumPaymentRule('forbrukslån', $balance, $interestRate);

        $validator = Validator::make(
            ['minimumPayment' => ceil($calculatedMinimum)],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->passes())->toBeTrue();
    });
});

describe('MinimumPaymentRule edge cases', function () {
    it('handles very small balances for kredittkort', function () {
        $rule = new MinimumPaymentRule('kredittkort', 100, 20);

        $validator = Validator::make(
            ['minimumPayment' => 300],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('handles very large balances for kredittkort', function () {
        $balance = 1000000;
        $expectedMinimum = $balance * 0.03;

        $rule = new MinimumPaymentRule('kredittkort', $balance, 20);

        $validator = Validator::make(
            ['minimumPayment' => $expectedMinimum],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('handles boundary at 10000 kr for kredittkort', function () {
        $balance = 10000;
        $expectedMinimum = max($balance * 0.03, 300); // 300 kr

        $rule = new MinimumPaymentRule('kredittkort', $balance, 20);

        $validator = Validator::make(
            ['minimumPayment' => $expectedMinimum],
            ['minimumPayment' => [$rule]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('returns appropriate error message for kredittkort', function () {
        $rule = new MinimumPaymentRule('kredittkort', 10000, 20);

        $validator = Validator::make(
            ['minimumPayment' => 200],
            ['minimumPayment' => [$rule]]
        );

        $validator->fails();
        $errors = $validator->errors()->get('minimumPayment');

        expect($errors)->toHaveCount(1);
        expect($errors[0])->toContain('300');
    });

    it('returns appropriate error message for forbrukslån', function () {
        $rule = new MinimumPaymentRule('forbrukslån', 100000, 12);

        $validator = Validator::make(
            ['minimumPayment' => 1000],
            ['minimumPayment' => [$rule]]
        );

        $validator->fails();
        $errors = $validator->errors()->get('minimumPayment');

        expect($errors)->toHaveCount(1);
        expect($errors[0])->toContain('kr');
    });
});
