<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Shared test data for debt-related tests.
 *
 * Provides consistent test data and validation scenarios
 * to avoid duplication across test files.
 */
class DebtTestData
{
    /**
     * Get valid data for creating a credit card debt.
     *
     * @return array<string, string>
     */
    public static function validCreditCardData(): array
    {
        return [
            'name' => 'Test Credit Card',
            'type' => 'kredittkort',
            'balance' => '50000',
            'interestRate' => '8.5',
            'minimumPayment' => '1500',
        ];
    }

    /**
     * Get valid data for creating a consumer loan.
     *
     * @return array<string, string>
     */
    public static function validConsumerLoanData(): array
    {
        return [
            'name' => 'Test Consumer Loan',
            'type' => 'forbrukslån',
            'balance' => '200000',
            'interestRate' => '10.0',
            'minimumPayment' => '2000',
        ];
    }

    /**
     * Get minimal valid debt data (credit card with minimum values).
     *
     * @return array<string, string>
     */
    public static function minimalValidDebtData(): array
    {
        return [
            'name' => 'Minimal Debt',
            'type' => 'kredittkort',
            'balance' => '10000',
            'interestRate' => '0',
            'minimumPayment' => '300',
        ];
    }

    /**
     * Get data provider for required field validation tests.
     *
     * @return array<string, array{field: string, rule: string}>
     */
    public static function requiredFieldsDataset(): array
    {
        return [
            'name is required' => ['field' => 'name', 'rule' => 'required'],
            'balance is required' => ['field' => 'balance', 'rule' => 'required'],
            'interestRate is required' => ['field' => 'interestRate', 'rule' => 'required'],
            'minimumPayment is required' => ['field' => 'minimumPayment', 'rule' => 'required'],
        ];
    }

    /**
     * Get data provider for numeric field validation tests.
     *
     * @return array<string, array{field: string, invalidValue: string}>
     */
    public static function numericFieldsDataset(): array
    {
        return [
            'balance must be numeric' => ['field' => 'balance', 'invalidValue' => 'not-a-number'],
            'interestRate must be numeric' => ['field' => 'interestRate', 'invalidValue' => 'not-a-number'],
            'minimumPayment must be numeric' => ['field' => 'minimumPayment', 'invalidValue' => 'not-a-number'],
        ];
    }

    /**
     * Get data provider for interest rate boundary tests.
     *
     * @return array<string, array{value: string, shouldPass: bool}>
     */
    public static function interestRateBoundaryDataset(): array
    {
        return [
            'zero interest is valid' => ['value' => '0', 'shouldPass' => true],
            'negative interest is invalid' => ['value' => '-1', 'shouldPass' => false],
            '100% interest is valid' => ['value' => '100', 'shouldPass' => true],
            '101% interest is invalid' => ['value' => '101', 'shouldPass' => false],
        ];
    }

    /**
     * Calculate the minimum payment for a credit card.
     *
     * @param  float  $balance  The balance amount
     * @return float The calculated minimum payment
     */
    public static function calculateCreditCardMinimum(float $balance): float
    {
        return max($balance * config('debt.minimum_payment.kredittkort.percentage', 0.03),
            config('debt.minimum_payment.kredittkort.minimum_amount', 300));
    }

    /**
     * Calculate the minimum payment for a consumer loan.
     *
     * @param  float  $balance  The balance amount
     * @param  float  $interestRate  The annual interest rate as a percentage
     * @return float The calculated minimum payment
     */
    public static function calculateConsumerLoanMinimum(float $balance, float $interestRate): float
    {
        $monthlyInterest = ($balance * ($interestRate / 100)) / 12;

        return $monthlyInterest * config('debt.minimum_payment.forbrukslån.buffer_percentage', 1.1);
    }
}
