<?php

use Tests\Support\DebtTestData;

describe('DebtTestData utility class', function () {
    it('provides valid credit card data', function () {
        $data = DebtTestData::validCreditCardData();

        expect($data)
            ->toHaveKey('name')
            ->toHaveKey('type')
            ->toHaveKey('balance')
            ->toHaveKey('interestRate')
            ->toHaveKey('minimumPayment');

        expect($data['type'])->toBe('kredittkort');
    });

    it('provides valid consumer loan data', function () {
        $data = DebtTestData::validConsumerLoanData();

        expect($data)
            ->toHaveKey('name')
            ->toHaveKey('type')
            ->toHaveKey('balance')
            ->toHaveKey('interestRate')
            ->toHaveKey('minimumPayment');

        expect($data['type'])->toBe('forbrukslån');
    });

    it('provides minimal valid debt data', function () {
        $data = DebtTestData::minimalValidDebtData();

        expect($data)
            ->toHaveKey('name')
            ->toHaveKey('type')
            ->toHaveKey('balance')
            ->toHaveKey('interestRate')
            ->toHaveKey('minimumPayment');

        expect($data['interestRate'])->toBe('0');
        expect($data['minimumPayment'])->toBe('300');
    });

    it('provides required fields dataset', function () {
        $dataset = DebtTestData::requiredFieldsDataset();

        expect($dataset)->toBeArray();
        expect($dataset)->toHaveKey('name is required');
        expect($dataset['name is required'])->toHaveKey('field');
        expect($dataset['name is required'])->toHaveKey('rule');
    });

    it('provides numeric fields dataset', function () {
        $dataset = DebtTestData::numericFieldsDataset();

        expect($dataset)->toBeArray();
        expect($dataset)->toHaveKey('balance must be numeric');
        expect($dataset['balance must be numeric'])->toHaveKey('field');
        expect($dataset['balance must be numeric'])->toHaveKey('invalidValue');
    });

    it('provides interest rate boundary dataset', function () {
        $dataset = DebtTestData::interestRateBoundaryDataset();

        expect($dataset)->toBeArray();
        expect($dataset)->toHaveKey('zero interest is valid');
        expect($dataset['zero interest is valid'])->toHaveKey('value');
        expect($dataset['zero interest is valid'])->toHaveKey('shouldPass');
    });

    it('calculates credit card minimum correctly for low balance', function () {
        $minimum = DebtTestData::calculateCreditCardMinimum(5000);

        expect($minimum)->toBe(300.0); // Should use minimum_amount, not 3% (which would be 150)
    });

    it('calculates credit card minimum correctly for high balance', function () {
        $minimum = DebtTestData::calculateCreditCardMinimum(50000);

        expect($minimum)->toBe(1500.0); // 3% of 50000
    });

    it('calculates consumer loan minimum correctly', function () {
        $balance = 100000;
        $interestRate = 12;
        $minimum = DebtTestData::calculateConsumerLoanMinimum($balance, $interestRate);

        // Using amortization formula: P = (r * PV) / (1 - (1 + r)^-n)
        // where r = 0.01 (monthly), PV = 100000, n = 60
        $monthlyRate = ($interestRate / 100) / 12;
        $expected = ($monthlyRate * $balance) / (1 - pow(1 + $monthlyRate, -60));

        expect($minimum)->toBe($expected);
    });

    it('calculates consumer loan minimum correctly with zero interest', function () {
        $minimum = DebtTestData::calculateConsumerLoanMinimum(60000, 0);

        // With 0% interest, minimum is balance / 60 months = 1000
        expect($minimum)->toBe(1000.0);
    });
});
