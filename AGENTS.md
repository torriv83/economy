# AGENTS.md

This file provides guidance for AI agents operating in this repository.

## Project Overview

Personal debt management application built with Laravel 12, Livewire 3, Tailwind CSS v4, and Pest v4.

## Build / Lint / Test Commands

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/DebtListTest.php

# Run tests matching filter (recommended after changes)
php artisan test --filter=testName

# Run browser tests
php artisan test --filter=browser

# Format changed PHP files (ALWAYS run before finalizing)
vendor/bin/pint --dirty

# Run static analysis
vendor/bin/phpstan

# Build frontend assets
npm run build

# Run development server with queue and vite
composer run dev

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## PHP Code Style

### File Structure
- Always use `declare(strict_types=1);` at the top of PHP files
- Namespace should match directory structure
- One class per file

### Imports
```php
use App\Models\Debt;                           // Local models (no prefix)
use App\Services\DebtCalculationService;      // Local services
use App\Livewire\Concerns\HasDebtValidation; // Traits
use Illuminate\Database\Eloquent\Model;        // Framework classes
use Livewire\Component;                        // Package classes
use Carbon\Carbon;                             // Composer packages
```

### Naming Conventions
- **Classes**: `PascalCase` (e.g., `DebtList`, `AvalancheStrategy`)
- **Methods/Properties**: `camelCase` (e.g., `calculateMinimumPayment`)
- **Variables**: `camelCase` (e.g., `$totalDebt`, `$debtsCollection`)
- **Constants**: `SCREAMING_SNAKE_CASE` (e.g., `PAYOFF_MONTHS`)
- **Enums**: `TitleCase` keys (e.g., `FavoritePerson`, `Monthly`)
- **Database columns**: `snake_case` (e.g., `interest_rate`, `custom_priority_order`)
- **Boolean methods**: `is*`, `has*`, `can*`, `should*` prefix (e.g., `isMinimumPaymentCompliant()`)

### Type Declarations
- **Always use explicit return types** on methods and functions
- Use explicit parameter types
- Use PHP 8 constructor property promotion:
```php
public function __construct(
    public DebtCalculationService $calculationService,
    public YnabService $ynabService,
) {}
```

### Control Structures
- Always use curly braces, even for single-line blocks

### Comments
- Prefer PHPDoc blocks over inline comments
- Add array shape type definitions for arrays
- Never use comments for obvious code

### Enums
```php
enum DebtType: string
{
    case Kredittkort = 'kredittkort';
    case Forbrukslån = 'forbrukslån';
}
```

## Laravel Conventions

### Models
- Use `$fillable` for mass assignment
- Define casts in `casts()` method (not `$casts` property)
- Use relationship methods with return type hints
- Generate with factory: `php artisan make:model Debt -mfs`

### Livewire Components
- Use `App\Livewire` namespace (NOT `App\Http\Livewire`)
- Use `boot()` method for dependency injection
- Use `mount()` for initialization
- Use `updated*()` lifecycle hooks for reactive side effects
- Single root element in templates
- Use `wire:key` in loops
- Use `wire:model.live` for real-time updates
- Use `$this->dispatch()` to dispatch events

### Services
- Put business logic in `app/Services/`
- Use dependency injection via constructor
- One service per file

### Validation
- Inline validation in Livewire using `rules()` method
- Use array syntax for rules: `['required', 'string', 'max:255']`
- Define custom messages in `messages()` method
- Create Form Request classes for complex validation

### Database
- Prefer Eloquent over raw queries
- Use `Model::query()` over `DB::`
- Use eager loading to prevent N+1 queries
- Migrations must include all column attributes when modifying

## Tailwind CSS v4

- Use `@import "tailwindcss"` (not `@tailwind` directives)
- Use `@theme {}` for custom values (no `tailwind.config.js`)
- Use `gap-*` for spacing between items (not `mt/mb`)
- Use `dark:` prefix for dark mode
- Do NOT use deprecated utilities:
  - `bg-opacity-*` → `bg-black/*`
  - `text-opacity-*` → `text-black/*`
  - `flex-shrink-*` → `shrink-*`

## Testing (Pest v4)

### Test Structure
```php
<?php

use App\Models\Debt;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('debt list renders correctly', function () {
    Debt::factory()->create(['name' => 'Kredittkort']);
    
    Livewire::test(DebtList::class)
        ->assertSee('Kredittkort');
});
```

### Test Organization
- Feature tests: `tests/Feature/`
- Unit tests: `tests/Unit/`
- Browser tests: `tests/Browser/`
- Use `test()` or `it()` syntax

### Assertions
- Use specific methods: `assertForbidden()`, `assertNotFound()` over `assertStatus(403)`
- Use `expect()->toBeTrue()` syntax for expectations

### Mocking
```php
use function Pest\Laravel\mock;

mock(SomeClass::class, function ($mock) {
    $mock->shouldReceive('method')->once()->andReturn(value);
});
```

## Error Handling

- Use early returns to avoid nesting
- Use `?` for nullable types
- Catch exceptions with specific types when possible
- Flash messages for user-facing errors: `session()->flash('error', 'message')`
- Log errors appropriately

## UI / Norwegian Text

- UI text and messages: Norwegian (Norsk)
- Code (variables, methods, classes): English
- Translation keys: `__('app.key_name')`

## Architecture

```
app/
├── Contracts/           # Interfaces
├── Livewire/
│   ├── Concerns/         # Shared traits
│   └── *.php             # Components
├── Models/              # Eloquent models
├── Services/            # Business logic
│   └── DebtOrdering/    # Strategy pattern
├── Jobs/                # Queued jobs
└── Support/             # Helper classes
```

## Common Patterns

### Debt Validation Trait
Shared validation logic lives in `app/Livewire/Concerns/HasDebtValidation.php`

### Delete Confirmation Pattern
Use `HasDeleteConfirmation` trait for delete modals

### Payment Schedule Calculations
Use `DebtCalculationService` for payoff calculations

### Debt Ordering Strategies
Implement `DebtOrderingStrategy` contract for custom ordering
