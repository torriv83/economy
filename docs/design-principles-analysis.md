# Design Principles Analysis Report

**Date:** 2025-11-28
**Scope:** Full codebase analysis for DRY, KISS, and SOLID principle violations

---

## Executive Summary

This report documents violations of software design principles found in the Laravel TALL stack (Tailwind, Alpine, Livewire, Laravel) application. The analysis covers DRY (Don't Repeat Yourself), KISS (Keep It Simple, Stupid), and SOLID principles.

### Severity Overview

| Severity | Count | Description |
|----------|-------|-------------|
| Critical | 3 | Must fix - significantly impacts maintainability |
| High | 8 | Should fix soon - causes code duplication or complexity |
| Medium | 6 | Fix eventually - minor improvements |
| Low | 4 | Nice to have - polish and consistency |

---

## 1. DRY (Don't Repeat Yourself) Violations

### 1.1 Duplicated Validation Rules - Critical ✅ FIXED

**Files Affected:**
- `app/Livewire/CreateDebt.php` (Lines 28-58)
- `app/Livewire/EditDebt.php` (Lines 64-95)

**Issue:** Identical validation rules and messages duplicated across CreateDebt and EditDebt components.

**Code Snippet:**
```php
// CreateDebt.php - Lines 28-58
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'type' => ['required', 'in:forbrukslån,kredittkort'],
        'balance' => ['required', 'numeric', 'min:0.01'],
        'interestRate' => ['required', 'numeric', 'min:0', 'max:100'],
        'minimumPayment' => [
            'required',
            'numeric',
            'min:0.01',
            function ($attribute, $value, $fail) {
                if ($this->type === 'kredittkort') {
                    $rule = new MinimumPaymentRule(...);
                    $rule->validate($attribute, $value, $fail);
                } else {
                    $monthlyInterest = ((float) $this->balance * ((float) $this->interestRate / 100)) / 12;
                    if ((float) $value <= $monthlyInterest) {
                        $fail(__('validation.minimum_payment_must_cover_interest', [...]));
                    }
                }
            },
        ],
        'dueDay' => ['nullable', 'integer', 'min:1', 'max:31'],
    ];
}

// EditDebt.php - Lines 64-95 (nearly identical, minus balance rule)
```

**Suggested Fix:** Extract to a reusable trait

```php
// app/Livewire/Concerns/HasDebtValidation.php
trait HasDebtValidation
{
    public function debtValidationRules(bool $isEdit = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:forbrukslån,kredittkort'],
            'interestRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimumPayment' => $this->minimumPaymentRules(),
            'dueDay' => ['nullable', 'integer', 'min:1', 'max:31'],
        ];

        if (!$isEdit) {
            $rules['balance'] = ['required', 'numeric', 'min:0.01'];
        }

        return $rules;
    }

    public function debtValidationMessages(): array
    {
        return [
            'name.required' => 'Navn er påkrevd.',
            // ... common messages
        ];
    }
}
```

---

### 1.2 Duplicated Modal Management Logic - High

**Files Affected:**
- `app/Livewire/SelfLoans/Overview.php` (Lines 58-110)
- `app/Livewire/SelfLoans/History.php` (Lines 51-109)

**Issue:** Both components duplicate modal state management (open/close/reset patterns) for multiple modals.

**Code Snippet (Overview.php):**
```php
public function openRepaymentModal(int $loanId): void
{
    $this->selectedLoanId = $loanId;
    $this->repaymentAmount = 0;
    $this->repaymentNotes = '';
    $this->repaymentDate = now()->format('d.m.Y');
    $this->showRepaymentModal = true;
}

public function closeRepaymentModal(): void
{
    $this->showRepaymentModal = false;
    $this->selectedLoanId = 0;
    $this->repaymentAmount = 0;
    $this->repaymentNotes = '';
    $this->repaymentDate = '';
    $this->resetValidation();
}

// Similar methods for withdrawal, edit modals...
```

**Suggested Fix:** Create a reusable modal trait

```php
// app/Livewire/Concerns/HasModalManagement.php
trait HasModalManagement
{
    public function openModal(string $modalName, array $data = []): void
    {
        $this->{"show{$modalName}Modal"} = true;
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function closeModal(string $modalName, array $resetFields = []): void
    {
        $this->{"show{$modalName}Modal"} = false;
        foreach ($resetFields as $field) {
            if (property_exists($this, $field)) {
                $this->$field = match (true) {
                    is_string($this->$field) => '',
                    is_numeric($this->$field) => 0,
                    is_bool($this->$field) => false,
                    default => null,
                };
            }
        }
        $this->resetValidation();
    }
}
```

---

### 1.3 Duplicated Date Parsing Logic - Medium ✅ FIXED

**Files Affected:**
- `app/Livewire/DebtList.php` (Line 625)
- `app/Livewire/SelfLoans/Overview.php` (Line 150)
- `app/Livewire/SelfLoans/History.php` (Line 133)
- `app/Livewire/Concerns/HasReconciliationModals.php` (Line 79)

**Issue:** Norwegian date format (DD.MM.YYYY) parsing repeated across multiple components.

**Code Snippet:**
```php
// DebtList.php - Line 625
$dateObject = \DateTime::createFromFormat('d.m.Y', $this->reconciliationDates[$debtId]);
$databaseDate = $dateObject ? $dateObject->format('Y-m-d') : now()->format('Y-m-d');

// HasReconciliationModals.php - Line 79
$dateObject = \DateTime::createFromFormat('d.m.Y', $this->editDate);
$databaseDate = $dateObject ? $dateObject->format('Y-m-d') : now()->format('Y-m-d');
```

**Suggested Fix:** Create a utility class

```php
// app/Support/DateFormatter.php
class DateFormatter
{
    public const NORWEGIAN_FORMAT = 'd.m.Y';
    public const DATABASE_FORMAT = 'Y-m-d';

    public static function norwegianToDatabase(string $norwegianDate): string
    {
        $dateObject = \DateTime::createFromFormat(self::NORWEGIAN_FORMAT, $norwegianDate);
        return $dateObject ? $dateObject->format(self::DATABASE_FORMAT) : now()->format(self::DATABASE_FORMAT);
    }

    public static function databaseToNorwegian(\DateTime|Carbon $date): string
    {
        return $date->format(self::NORWEGIAN_FORMAT);
    }
}

// Usage
$databaseDate = DateFormatter::norwegianToDatabase($this->reconciliationDates[$debtId]);
```

---

### 1.4 Duplicated Modal HTML Patterns - High

**Files Affected:**
- `resources/views/livewire/create-debt.blade.php` (Lines 1-28)
- `resources/views/livewire/edit-debt.blade.php` (Lines 1-14)
- Other form views

**Issue:** Nearly identical form card structure and styling repeated across views.

**Code Snippet:**
```blade
<!-- Both CreateDebt and EditDebt start with: -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-colors duration-200">
    <form wire:submit="save" class="p-6 sm:p-8">
        <div class="grid grid-cols-1 gap-6">
            <!-- repeated form fields -->
        </div>

        <!-- Form Actions (also repeated) -->
        <div class="mt-8 flex flex-col sm:flex-row gap-3">
            <button class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                <!-- button content -->
            </button>
        </div>
    </form>
</div>
```

**Suggested Fix:** Create Blade component

```blade
<!-- resources/views/components/form/card.blade.php -->
@props(['action' => 'save', 'submitText' => 'Save'])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-colors duration-200">
    <form wire:submit="{{ $action }}" class="p-6 sm:p-8">
        <div class="grid grid-cols-1 gap-6">
            {{ $slot }}
        </div>

        <div class="mt-8 flex flex-col sm:flex-row gap-3">
            {{ $actions ?? '' }}
        </div>
    </form>
</div>
```

---

### 1.5 Duplicated Minimum Payment Calculation Logic - High ✅ FIXED

**Files Affected:**
- `resources/views/livewire/create-debt.blade.php` (Lines 116-131)
- `resources/views/livewire/edit-debt.blade.php` (Lines 75-90)

**Issue:** Identical JavaScript calculation logic duplicated in both form views.

**Code Snippet:**
```javascript
// Both files have this duplicated:
x-data="{
    type: @entangle('type'),
    balance: @entangle('balance'),
    interestRate: @entangle('interestRate'),
    calculatedMinimum: 0,
    updateCalculatedMinimum() {
        const balance = parseFloat(this.balance) || 0;
        const interestRate = parseFloat(this.interestRate) || 0;

        if (balance <= 0) {
            this.calculatedMinimum = 0;
            return;
        }

        if (this.type === 'kredittkort') {
            this.calculatedMinimum = Math.ceil(Math.max(balance * 0.03, 300));
        } else {
            const monthlyInterest = (balance * (interestRate / 100)) / 12;
            this.calculatedMinimum = Math.ceil(monthlyInterest * 1.1);
        }
    }
}"
```

**Suggested Fix:** Extract to Alpine.js component

```javascript
// resources/js/alpine/debt-calculator.js
document.addEventListener('alpine:init', () => {
    Alpine.data('debtTypeCalculator', (type, balance, interestRate) => ({
        type,
        balance,
        interestRate,
        calculatedMinimum: 0,

        init() {
            this.updateCalculatedMinimum();
            this.$watch('type', () => this.updateCalculatedMinimum());
            this.$watch('balance', () => this.updateCalculatedMinimum());
            this.$watch('interestRate', () => this.updateCalculatedMinimum());
        },

        updateCalculatedMinimum() {
            const balance = parseFloat(this.balance) || 0;
            const rate = parseFloat(this.interestRate) || 0;

            if (balance <= 0) {
                this.calculatedMinimum = 0;
                return;
            }

            if (this.type === 'kredittkort') {
                this.calculatedMinimum = Math.ceil(Math.max(balance * 0.03, 300));
            } else {
                const monthlyInterest = (balance * (rate / 100)) / 12;
                this.calculatedMinimum = Math.ceil(monthlyInterest * 1.1);
            }
        }
    }));
});
```

---

### 1.6 Duplicated Delete Confirmation Logic - High ✅ FIXED

**Files Affected:**
- `app/Livewire/DebtList.php` (Lines 244-257)
- `app/Livewire/SelfLoans/Overview.php` (Lines 234-247)
- `app/Livewire/SelfLoans/History.php` (Lines 123-137)

**Issue:** Nearly identical delete confirmation pattern repeated across multiple components.

**Code Snippet:**
```php
// DebtList.php
public function confirmDelete(int $id, string $name): void
{
    $this->debtToDelete = $id;
    $this->debtNameToDelete = $name;
    $this->showDeleteModal = true;
}

public function deleteDebt(): void
{
    if ($this->debtToDelete) {
        $debt = Debt::find($this->debtToDelete);
        if ($debt) {
            $debt->delete();
            session()->flash('message', 'Gjeld slettet.');
        }
    }
    $this->showDeleteModal = false;
    $this->debtToDelete = null;
    $this->debtNameToDelete = '';
}
```

**Suggested Fix:** Create a reusable concern trait

```php
// app/Livewire/Concerns/HasDeleteConfirmation.php
trait HasDeleteConfirmation
{
    public bool $showDeleteModal = false;
    public ?int $recordToDelete = null;
    public string $recordNameToDelete = '';

    public function confirmDelete(int $id, string $name): void
    {
        $this->recordToDelete = $id;
        $this->recordNameToDelete = $name;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->recordToDelete = null;
        $this->recordNameToDelete = '';
    }

    abstract protected function performDelete(int $id): void;

    public function executeDelete(): void
    {
        if ($this->recordToDelete) {
            $this->performDelete($this->recordToDelete);
        }
        $this->cancelDelete();
    }
}
```

---

### 1.7 Duplicated Input Classes in Blade Components - Medium ⏸️ NOT FIXED (Intentional)

**Files Affected:**
- `resources/views/components/form/text-input.blade.php` (Line 11)
- `resources/views/components/form/amount-input.blade.php` (Line 11)
- `resources/views/components/form/textarea.blade.php` (Line 11)

**Issue:** Identical Tailwind classes for input styling repeated across three form components.

**Code Snippet:**
```blade
<!-- All three components define: -->
$inputClasses = 'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white';
```

**Decision:** ⏸️ **INTENTIONALLY NOT FIXED** - The Blade components themselves ARE the abstraction layer. Each component is self-contained and should remain independent. Using `config()` for Tailwind classes is not idiomatic in the Laravel/Tailwind ecosystem. The duplication here is acceptable because:
1. Each Blade component encapsulates its own styling
2. Changes to one component's appearance may intentionally differ from others
3. The config approach adds indirection without meaningful benefit

---

## 2. KISS (Keep It Simple, Stupid) Violations

### 2.1 Over-Complex Reconciliation State Management - High ✅ FIXED

**File:** `app/Livewire/DebtList.php` (Lines 27-44)

**Issue:** Nine separate arrays to manage reconciliation modal state is overly complex.

**Code Snippet:**
```php
/** @var array<int, bool> */
public array $reconciliationModals = [];

/** @var array<int, string> */
public array $reconciliationBalances = [];

/** @var array<int, string> */
public array $reconciliationDates = [];

/** @var array<int, string|null> */
public array $reconciliationNotes = [];
```

**Suggested Fix:** Use a single nested array

```php
/** @var array<int, array{show: bool, balance: string, date: string, notes: string|null}> */
public array $reconciliations = [];

public function openReconciliationModal(int $debtId): void
{
    $this->reconciliations[$debtId] = [
        'show' => true,
        'balance' => (string) Debt::find($debtId)?->balance ?? '',
        'date' => now()->format('d.m.Y'),
        'notes' => null,
    ];
}

public function closeReconciliationModal(int $debtId): void
{
    unset($this->reconciliations[$debtId]);
}
```

---

### 2.2 Complex Debt Discrepancy Logic - High ✅ FIXED

**File:** `app/Livewire/DebtList.php` (Lines 361-407)

**Issue:** The `findDiscrepancies()` method performs multiple nested loops and queries, making it hard to follow.

**Suggested Fix:** Extract to a dedicated service with clear responsibilities

```php
// app/Services/YnabDiscrepancyService.php
class YnabDiscrepancyService
{
    public function findDiscrepancies(Collection $ynabDebts, Collection $localDebts): array
    {
        return [
            'new' => $this->findNewDebts($ynabDebts, $localDebts),
            'closed' => $this->findClosedDebts($ynabDebts, $localDebts),
            'potential_matches' => $this->findPotentialMatches($ynabDebts, $localDebts),
            'balance_mismatch' => $this->findBalanceMismatches($ynabDebts, $localDebts),
        ];
    }

    private function findNewDebts(Collection $ynabDebts, Collection $localDebts): array
    {
        // Single responsibility: find only new debts
    }

    private function findClosedDebts(Collection $ynabDebts, Collection $localDebts): array
    {
        // Single responsibility: find only closed debts
    }

    // ... other focused methods
}
```

---

### 2.3 Over-Complex Conditional Form Rendering - Medium ✅ FIXED

**File:** `resources/views/livewire/create-debt.blade.php` (Lines 95-194)

**Issue:** Complex Alpine.js logic with long conditional chains embedded in view (100+ lines of x-data).

**Suggested Fix:** Extract to a dedicated partial or Alpine component as shown in section 1.5.

---

## 3. SOLID Principle Violations

### 3.1 Single Responsibility Principle (SRP) Violations

#### 3.1.1 DebtList Component Doing Too Much - Critical ⏸️ DEFERRED

**File:** `app/Livewire/DebtList.php` (562 lines after refactoring)

**Status:** Partially addressed. The component has been reduced from 711 to 562 lines through:
- Extracted `HasDeleteConfirmation` trait
- Uses `YnabDiscrepancyService` for discrepancy logic
- Uses `DateFormatter` utility for date parsing
- Simplified reconciliation state to single nested array

**Decision:** Further component splitting deferred. The current structure is maintainable at 562 lines with clear separation of concerns via traits and services.

**Current Responsibilities:**
1. Debt display and filtering
2. Debt reordering
3. YNAB API integration and sync
4. Reconciliation modal management
5. Reconciliation history viewing
6. Payment tracking
7. Multiple calculation properties

**Suggested Fix:** Break into multiple focused components

```php
// app/Livewire/DebtList/DebtListDisplay.php
class DebtListDisplay extends Component { /* Display only */ }

// app/Livewire/DebtList/DebtReorder.php
class DebtReorder extends Component { /* Reordering only */ }

// app/Livewire/DebtList/YnabSync.php
class YnabSync extends Component { /* YNAB integration only */ }

// app/Livewire/DebtList/ReconciliationManager.php
class ReconciliationManager extends Component { /* Reconciliation only */ }

// Parent component orchestrates:
class DebtListPage extends Component
{
    // Renders child components
}
```

---

#### 3.1.2 DebtProgress Mixed Concerns - High ✅ FIXED

**File:** `app/Livewire/DebtProgress.php`

**Previous Responsibilities (now separated):**
1. Cache management → Extracted to `ProgressCacheService`
2. Complex calculation logic → Extracted to `ProgressChartService`
3. Multiple business logic properties → Remain in component (appropriate)
4. View rendering → Remains in component (appropriate)

**Fix Applied:** Created two focused services:

```php
// app/Services/ProgressCacheService.php
class ProgressCacheService
{
    public function getCacheKey(): string { }
    public function remember(callable $callback): array { }
    public function has(): bool { }
    public function clear(): void { }
    public static function clearCache(): void { }  // Backward compatible
    public static function getProgressDataCacheKey(): string { }  // Backward compatible
}

// app/Services/ProgressChartService.php
class ProgressChartService
{
    public function calculateProgressData(): array { }
    protected function getColorPalette(): array { }
}
```

**Tests Added:**
- `tests/Unit/Services/ProgressCacheServiceTest.php` (13 tests)
- `tests/Unit/Services/ProgressChartServiceTest.php` (13 tests)

---

### 3.2 Open/Closed Principle (OCP) Violations

#### 3.2.1 Hard-Coded Debt Ordering Strategies - High ✅ FIXED

**File:** `app/Services/DebtCalculationService.php`

**Previous Issue:** Three similar ordering methods with hard-coded logic. Adding a new strategy required modifying the service.

**Fix Applied:** Implemented Strategy Pattern with the following files:

**Interface Created:**
```php
// app/Contracts/DebtOrderingStrategy.php
interface DebtOrderingStrategy
{
    public function order(Collection $debts): Collection;
    public function getKey(): string;
    public function getName(): string;
    public function getDescription(): string;
}
```

**Strategy Classes Created:**
- `app/Services/DebtOrdering/SnowballStrategy.php` - Orders by lowest balance first
- `app/Services/DebtOrdering/AvalancheStrategy.php` - Orders by highest interest rate first
- `app/Services/DebtOrdering/CustomStrategy.php` - Orders by user-defined priority

**Service Updated:**
```php
// app/Services/DebtCalculationService.php
class DebtCalculationService
{
    /** @var array<string, DebtOrderingStrategy> */
    protected array $strategies = [];

    public function registerStrategy(DebtOrderingStrategy $strategy): void { }
    public function getStrategy(string $key): ?DebtOrderingStrategy { }
    public function getStrategies(): array { }
    public function order(string $strategy, Collection $debts): Collection { }

    // Legacy methods marked @deprecated but still work:
    public function orderBySnowball(Collection $debts): Collection { }
    public function orderByAvalanche(Collection $debts): Collection { }
    public function orderByCustom(Collection $debts): Collection { }
}
```

**Translations Added:**
- `lang/en/strategies.php`
- `lang/no/strategies.php`

**Tests Added:**
- `tests/Unit/Services/DebtOrdering/SnowballStrategyTest.php` (14 tests)
- `tests/Unit/Services/DebtOrdering/AvalancheStrategyTest.php` (16 tests)
- `tests/Unit/Services/DebtOrdering/CustomStrategyTest.php` (20 tests)
- `tests/Unit/Contracts/DebtOrderingStrategyTest.php` (17 tests)

**Benefits:**
- New strategies can be added by implementing `DebtOrderingStrategy` interface
- No modification to existing code required (Open/Closed Principle)
- Full backward compatibility maintained
- Strategy names and descriptions are translatable

---

## 4. Other Violations

### 4.1 Magic Numbers Without Constants - Medium

**Files Affected:**
- `app/Models/Debt.php` (Line 49)
- `app/Services/DebtCalculationService.php` (Line 47)
- `resources/views/livewire/create-debt.blade.php` (Line 131)
- `resources/views/livewire/edit-debt.blade.php` (Line 90)

**Issue:** Hard-coded values appear throughout code without explanation.

**Code Snippets:**
```php
// Debt.php
return max($this->balance * 0.03, 300);  // Magic numbers: 3% and 300 kr

// JavaScript in views
this.calculatedMinimum = Math.ceil(Math.max(balance * 0.03, 300));
this.calculatedMinimum = Math.ceil(monthlyInterest * 1.1); // 10% buffer
```

**Suggested Fix:** Create configuration

```php
// config/debt.php
return [
    'minimum_payment' => [
        'kredittkort' => [
            'percentage' => 0.03,  // 3%
            'minimum_amount' => 300,  // 300 kr
        ],
        'forbrukslån' => [
            'buffer_percentage' => 1.1,  // 10% above interest
        ],
    ],
];
```

---

### 4.2 Inconsistent Error Handling - Medium

**Files Affected:**
- `app/Livewire/DebtList.php` (Lines 335-340)
- `app/Livewire/SelfLoans/Overview.php` (Lines 113-118)

**Issue:** Some methods silently fail without user feedback.

**Suggested Fix:** Implement consistent error handling pattern

```php
public function updateLoan(): void
{
    $this->validate([...]);

    try {
        $loan = SelfLoan::findOrFail($this->selectedLoanId);
        $loan->update([...]);
        session()->flash('message', __('app.self_loan_updated_successfully'));
    } catch (\Exception $e) {
        session()->flash('error', __('app.error_updating_loan'));
        \Log::error('Failed to update loan', ['error' => $e->getMessage()]);
    }

    $this->closeEditModal();
}
```

---

### 4.3 Inconsistent Naming Conventions - Low

**Issue:** Different patterns used across similar functionality:
- Property naming: `$debtToDelete` vs `$loanToDelete` vs `$recordToDelete`
- Method naming: `openEditModal()` vs `openRepaymentModal()` vs `openReconciliationModal()`

**Suggested Fix:** Establish naming conventions in CLAUDE.md or a style guide.

---

### 4.4 Test Duplication - Low

**Files Affected:**
- `tests/Feature/CreateDebtTest.php` (Lines 72-96)
- `tests/Feature/EditDebtTest.php` (Lines 46-71)

**Issue:** Very similar validation tests repeated across test files.

**Suggested Fix:** Create shared test utilities

```php
// tests/Support/DebtTestData.php
class DebtTestData
{
    public static function validDebtData(): array
    {
        return [
            'name' => 'Test Debt',
            'type' => 'kredittkort',
            'balance' => '50000',
            'interestRate' => '8.5',
            'minimumPayment' => '1500',
        ];
    }
}
```

---

## Summary Table

| Violation | Severity | Files | Fix Complexity | Status |
|-----------|----------|-------|----------------|--------|
| Validation rules duplication | Critical | CreateDebt, EditDebt | Medium | ✅ Fixed |
| DebtList overly complex (711→562 lines) | Critical | DebtList | High | ⏸️ Deferred |
| Modal management duplication | High | SelfLoans/* | Medium | Pending |
| Form card HTML duplication | High | Multiple views | Low | Pending |
| Minimum calculation duplication | High | Views | Low | ✅ Fixed |
| Delete confirmation duplication | High | Multiple components | Medium | ✅ Fixed |
| DebtProgress mixed concerns | High | DebtProgress | High | ✅ Fixed |
| Hard-coded debt strategies | High | DebtCalculationService | Medium | ✅ Fixed |
| Reconciliation state complexity | High | DebtList | Medium | ✅ Fixed |
| Complex discrepancy logic | High | DebtList | Medium | ✅ Fixed |
| Date parsing duplication | Medium | Multiple files | Low | ✅ Fixed |
| Input classes duplication | Medium | Form components | Low | ⏸️ Intentional |
| Missing constants/magic numbers | Medium | Multiple | Low | Pending |
| Inconsistent error handling | Medium | Multiple | Low | Pending |
| Form rendering complexity | Medium | Views | Medium | ✅ Fixed |
| Inconsistent naming | Low | Multiple | Low | Pending |
| Test duplication | Low | Tests | Low | Pending |

---

## Recommended Fix Priority

### Phase 1: Critical (Do First)
1. ⏸️ Break up DebtList component (711→562 lines) - **Deferred** (now manageable)
2. ✅ Extract validation rules to shared trait (`HasDebtValidation`)
3. Create modal management trait (`HasModalManagement`)

### Phase 2: High Priority (Do Soon)
4. ✅ Create date parsing utility (`DateFormatter`)
5. Create form card Blade component
6. ✅ Extract Alpine.js debt calculator
7. ✅ Implement strategy pattern for debt ordering (`DebtOrderingStrategy` interface)
8. ✅ Create delete confirmation trait
9. ✅ Extract YNAB discrepancy logic to service (`YnabDiscrepancyService`)
10. ✅ Simplify reconciliation state management (single nested array)
11. ✅ Extract DebtProgress cache logic to `ProgressCacheService`
12. ✅ Extract DebtProgress chart logic to `ProgressChartService`

### Phase 3: Medium Priority (Do Eventually)
13. Consolidate magic numbers to `config/debt.php`
14. Standardize error handling
15. ⏸️ Centralize input classes configuration - **Intentionally not fixed** (Blade components ARE the abstraction)

### Phase 4: Low Priority (Nice to Have)
16. Standardize naming conventions
17. Create test utilities for shared data
18. Minor code cleanup and polish

---

## Files to Create

```
app/
├── Contracts/
│   └── DebtOrderingStrategy.php            ✅ Created
├── Livewire/
│   ├── Concerns/
│   │   ├── HasDebtValidation.php           ✅ Created
│   │   ├── HasDeleteConfirmation.php       ✅ Created
│   │   └── HasModalManagement.php
│   └── DebtList/                           ⏸️ Deferred (DebtList now 562 lines)
│       ├── DebtListDisplay.php
│       ├── DebtReorder.php
│       ├── ReconciliationManager.php
│       └── YnabSync.php
├── Services/
│   ├── DebtOrdering/
│   │   ├── AvalancheStrategy.php           ✅ Created
│   │   ├── CustomStrategy.php              ✅ Created
│   │   └── SnowballStrategy.php            ✅ Created
│   ├── ProgressCacheService.php            ✅ Created
│   ├── ProgressChartService.php            ✅ Created
│   └── YnabDiscrepancyService.php          ✅ Created
└── Support/
    └── DateFormatter.php                   ✅ Created

config/
├── debt.php
└── ui.php

lang/
├── en/
│   └── strategies.php                      ✅ Created
└── no/
    └── strategies.php                      ✅ Created

resources/
├── js/
│   └── alpine/
│       └── debt-type-calculator.js         ✅ Created
└── views/
    └── components/
        └── form/
            └── card.blade.php

tests/
├── Support/
│   └── DebtTestData.php
└── Unit/
    ├── Contracts/
    │   └── DebtOrderingStrategyTest.php    ✅ Created (17 tests)
    └── Services/
        ├── DebtOrdering/
        │   ├── AvalancheStrategyTest.php   ✅ Created (16 tests)
        │   ├── CustomStrategyTest.php      ✅ Created (20 tests)
        │   └── SnowballStrategyTest.php    ✅ Created (14 tests)
        ├── ProgressCacheServiceTest.php    ✅ Created (13 tests)
        ├── ProgressChartServiceTest.php    ✅ Created (13 tests)
        └── YnabDiscrepancyServiceTest.php  ✅ Created
```
