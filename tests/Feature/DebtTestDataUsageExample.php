<?php

use App\Livewire\CreateDebt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\DebtTestData;

uses(RefreshDatabase::class);

describe('DebtTestData usage examples', function () {
    it('can create debt using validCreditCardData helper', function () {
        $data = DebtTestData::validCreditCardData();

        Livewire::test(CreateDebt::class)
            ->set('name', $data['name'])
            ->set('type', $data['type'])
            ->set('balance', $data['balance'])
            ->set('interestRate', $data['interestRate'])
            ->set('minimumPayment', $data['minimumPayment'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('debts', [
            'name' => $data['name'],
            'type' => $data['type'],
        ]);
    });

    it('can create debt using validConsumerLoanData helper', function () {
        $data = DebtTestData::validConsumerLoanData();

        Livewire::test(CreateDebt::class)
            ->set('name', $data['name'])
            ->set('type', $data['type'])
            ->set('balance', $data['balance'])
            ->set('interestRate', $data['interestRate'])
            ->set('minimumPayment', $data['minimumPayment'])
            ->call('save')
            ->assertHasNoErrors();
    });

    it('can use minimalValidDebtData for edge cases', function () {
        $data = DebtTestData::minimalValidDebtData();

        Livewire::test(CreateDebt::class)
            ->set('name', $data['name'])
            ->set('type', $data['type'])
            ->set('balance', $data['balance'])
            ->set('interestRate', $data['interestRate'])
            ->set('minimumPayment', $data['minimumPayment'])
            ->call('save')
            ->assertHasNoErrors();
    });

    it('can use calculateCreditCardMinimum helper for validation tests', function () {
        $balance = 50000;
        $calculatedMinimum = DebtTestData::calculateCreditCardMinimum($balance);

        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Card')
            ->set('type', 'kredittkort')
            ->set('balance', (string) $balance)
            ->set('interestRate', '8.5')
            ->set('minimumPayment', (string) $calculatedMinimum)
            ->call('save')
            ->assertHasNoErrors();
    });

    it('can use calculateConsumerLoanMinimum helper for validation tests', function () {
        $balance = 100000;
        $interestRate = 12;
        $calculatedMinimum = DebtTestData::calculateConsumerLoanMinimum($balance, $interestRate);

        Livewire::test(CreateDebt::class)
            ->set('name', 'Test Loan')
            ->set('type', 'forbrukslån')
            ->set('balance', (string) $balance)
            ->set('interestRate', (string) $interestRate)
            ->set('minimumPayment', (string) ceil($calculatedMinimum))
            ->call('save')
            ->assertHasNoErrors();
    });
});

describe('Using datasets from DebtTestData', function () {
    it('demonstrates how to use requiredFieldsDataset', function () {
        $requiredFields = DebtTestData::requiredFieldsDataset();

        foreach ($requiredFields as $testCase) {
            $field = $testCase['field'];
            $data = DebtTestData::validCreditCardData();
            unset($data[$field]);

            $test = Livewire::test(CreateDebt::class);
            foreach ($data as $key => $value) {
                $test->set($key, $value);
            }
            $test->call('save')->assertHasErrors([$field => $testCase['rule']]);
        }
    });

    it('demonstrates how to use numericFieldsDataset', function () {
        $numericFields = DebtTestData::numericFieldsDataset();

        foreach ($numericFields as $testCase) {
            $field = $testCase['field'];
            $invalidValue = $testCase['invalidValue'];

            $data = DebtTestData::validCreditCardData();
            $data[$field] = $invalidValue;

            $test = Livewire::test(CreateDebt::class);
            foreach ($data as $key => $value) {
                $test->set($key, $value);
            }
            $test->call('save')->assertHasErrors([$field]);
        }
    });

    it('demonstrates how to use interestRateBoundaryDataset', function () {
        $boundaries = DebtTestData::interestRateBoundaryDataset();

        foreach ($boundaries as $testCase) {
            $value = $testCase['value'];
            $shouldPass = $testCase['shouldPass'];

            $data = DebtTestData::validCreditCardData();
            $data['interestRate'] = $value;

            $test = Livewire::test(CreateDebt::class);
            foreach ($data as $key => $val) {
                $test->set($key, $val);
            }

            if ($shouldPass) {
                $test->call('save')->assertHasNoErrors();
            } else {
                $test->call('save')->assertHasErrors(['interestRate']);
            }
        }
    });
});
