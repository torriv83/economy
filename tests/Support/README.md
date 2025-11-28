# Test Support Utilities

This directory contains shared test utilities and helper classes used across the test suite to reduce duplication and maintain consistency.

## DebtTestData

The `DebtTestData` class provides shared test data and calculation helpers for debt-related tests.

### Purpose

- **Reduce duplication**: Avoid repeating the same test data across multiple test files
- **Maintain consistency**: Ensure all tests use the same valid data structures
- **Simplify calculations**: Provide helpers for minimum payment calculations
- **Dataset providers**: Offer reusable datasets for validation testing

### Available Methods

#### Valid Data Helpers

```php
// Get valid credit card debt data
$data = DebtTestData::validCreditCardData();
// Returns: ['name' => '...', 'type' => 'kredittkort', 'balance' => '...', ...]

// Get valid consumer loan data
$data = DebtTestData::validConsumerLoanData();
// Returns: ['name' => '...', 'type' => 'forbrukslån', 'balance' => '...', ...]

// Get minimal valid debt data (for edge case testing)
$data = DebtTestData::minimalValidDebtData();
// Returns: minimal valid data with zero interest and minimum values
```

#### Dataset Providers

```php
// Get dataset for testing required fields
$dataset = DebtTestData::requiredFieldsDataset();
// Returns: ['name is required' => ['field' => 'name', 'rule' => 'required'], ...]

// Get dataset for testing numeric validation
$dataset = DebtTestData::numericFieldsDataset();
// Returns: ['balance must be numeric' => ['field' => 'balance', 'invalidValue' => '...'], ...]

// Get dataset for testing interest rate boundaries
$dataset = DebtTestData::interestRateBoundaryDataset();
// Returns: ['zero interest is valid' => ['value' => '0', 'shouldPass' => true], ...]
```

#### Calculation Helpers

```php
// Calculate minimum payment for credit card (3% or 300 kr, whichever is higher)
$minimum = DebtTestData::calculateCreditCardMinimum(50000);
// Returns: 1500.0

// Calculate minimum payment for consumer loan (monthly interest * 1.1)
$minimum = DebtTestData::calculateConsumerLoanMinimum(100000, 12);
// Returns: 1100.0 (monthly interest = 1000, buffered by 10%)
```

### Usage Examples

#### Using Valid Data Helpers

```php
it('creates a debt with valid credit card data', function () {
    $data = DebtTestData::validCreditCardData();

    Livewire::test(CreateDebt::class)
        ->set('name', $data['name'])
        ->set('type', $data['type'])
        ->set('balance', $data['balance'])
        ->set('interestRate', $data['interestRate'])
        ->set('minimumPayment', $data['minimumPayment'])
        ->call('save')
        ->assertHasNoErrors();
});
```

#### Using Calculation Helpers

```php
it('validates minimum payment for credit card', function () {
    $balance = 50000;
    $minimum = DebtTestData::calculateCreditCardMinimum($balance);

    Livewire::test(CreateDebt::class)
        ->set('type', 'kredittkort')
        ->set('balance', (string) $balance)
        ->set('minimumPayment', (string) $minimum)
        ->call('save')
        ->assertHasNoErrors();
});
```

#### Using Dataset Providers

```php
it('validates all required fields', function () {
    $requiredFields = DebtTestData::requiredFieldsDataset();

    foreach ($requiredFields as $testCase) {
        $field = $testCase['field'];
        $data = DebtTestData::validCreditCardData();
        unset($data[$field]);

        $test = Livewire::test(CreateDebt::class);
        foreach ($data as $key => $value) {
            $test->set($key, $value);
        }
        $test->call('save')->assertHasErrors([$field]);
    }
});
```

### Benefits

1. **DRY Principle**: Don't repeat yourself - define test data once, use everywhere
2. **Easy Updates**: Change test data in one place when requirements change
3. **Consistency**: All tests use the same valid data structures
4. **Type Safety**: Proper PHPDoc annotations for better IDE support
5. **Calculation Accuracy**: Shared calculation methods ensure consistency with business logic

### Adding New Helpers

When adding new test data helpers to this class:

1. Follow the existing naming conventions (`valid*Data`, `calculate*`, `*Dataset`)
2. Add proper PHPDoc comments with parameter and return types
3. Include example usage in this README
4. Write tests for the new helper in `tests/Unit/Support/DebtTestDataTest.php`

### Related Files

- `tests/Unit/Support/DebtTestDataTest.php` - Tests for this utility class
- `tests/Feature/DebtTestDataUsageExample.php` - Usage examples
- `config/debt.php` - Configuration used by calculation methods
