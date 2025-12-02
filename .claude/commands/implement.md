---
allowed-tools: all
description: Implement the discussed feature following all coding standards
---

# IMPLEMENTATION COMMAND

**Implement the feature/change we just discussed.**

---
**Do not run tests until the last step!**

## [!] MANDATORY PRE-FLIGHT CHECK

1. **Re-read the conversation** - What exactly did we discuss?
2. **Re-read CLAUDE.md** - Refresh on project standards
3. **Identify the scope** - What files need to be created/modified?

---

## [CRITICAL] TRANSLATION REQUIREMENTS

**ZERO hardcoded user-facing text allowed!**

### Rules:
- [X] **NEVER** hardcode Norwegian text in source code
- [X] **NEVER** hardcode English text in source code
- [OK] **ALWAYS** use translation keys: `{{ __('debts.create_new') }}`
- [OK] **ALWAYS** create translation files if they don't exist
- [OK] **ALWAYS** add new keys to `lang/en/*.php` (and `lang/nb/*.php` if it exists)

### Translation Key Conventions:
```php
// Use dot notation with descriptive keys
__('debts.balance')           // Not __('Balance')
__('debts.form.name_label')   // Grouped by context
__('common.save')             // Shared across features
__('common.cancel')
__('common.delete')
__('validation.required')     // Laravel's built-in
```

### Before Finishing:
- [ ] Search for any hardcoded strings in your changes
- [ ] Verify all user-facing text uses `__()` or `@lang()`
- [ ] Create/update translation files as needed

---

## [DRY] DRY PRINCIPLE (Don't Repeat Yourself)

### Before Writing New Code:
1. **Search for existing implementations** - Does similar code exist?
2. **Check for reusable components** - Blade components, Livewire traits?
3. **Look at sibling files** - How do similar features work?

### Code Reuse Checklist:
- [ ] Check `resources/views/components/` for existing Blade components
- [ ] Check for shared Livewire traits or base classes
- [ ] Look for helper functions or service classes
- [ ] Reuse validation rules from similar forms
- [ ] Use existing CSS/Tailwind patterns

### If You Find Duplication:
- Extract to a shared component/method
- Don't copy-paste - refactor!

---

## [WORKFLOW] IMPLEMENTATION WORKFLOW

### Step 1: Plan the Implementation
Use TodoWrite to create a task list:
```
1. Create/modify models and migrations
2. Create/modify Livewire components
3. Create/modify Blade views
4. Add translation keys
5. Write tests
6. Run quality checks
```

### Step 2: Use Subagents for Parallel Work
**SPAWN MULTIPLE AGENTS** when tasks are independent:
- Agent 1: Create backend (models, migrations)
- Agent 2: Create frontend (Livewire, Blade)
- Agent 3: Write tests
**Always use apporpriate subagents for the task**

### Step 3: Follow Existing Patterns
- Check sibling files for conventions
- Use `php artisan make:*` commands
- Follow the established architecture

### Step 4: Verify Quality
After implementation, run:
```bash
vendor/bin/pint --dirty    # Format code
vendor/bin/phpstan analyse # Static analysis
php artisan test --filter=  # Related tests
```

---

## [CHECKLIST] IMPLEMENTATION CHECKLIST

### Code Quality:
- [ ] Follows existing code conventions (check sibling files!)
- [ ] Uses proper type hints on all methods
- [ ] No commented-out code
- [ ] No debugging statements (dd, dump, console.log)
- [ ] Self-documenting code with clear variable names

### Laravel/Livewire:
- [ ] Used `php artisan make:*` commands where applicable
- [ ] Created Form Request for validation (not inline)
- [ ] Eager loading to prevent N+1 queries
- [ ] Livewire components have single root element
- [ ] Added `wire:key` in loops

### Frontend:
- [ ] Responsive design (mobile-first)
- [ ] Dark mode support (if existing pages have it)
- [ ] Loading states with `wire:loading`
- [ ] Consistent with existing UI patterns

### Translations:
- [ ] ALL user-facing text uses translation keys
- [ ] Translation keys added to language files
- [ ] No hardcoded Norwegian or English strings

### Testing:
- [ ] Feature tests for user workflows
- [ ] Unit tests for business logic
- [ ] Tests cover happy paths AND edge cases
- [ ] All related tests pass

---

## [CRITICAL] LAYOUT COMPONENT ARCHITECTURE

**This project uses Layout Components with embedded views - NOT separate routes per page!**

### How Navigation Works in This Project:

Each major section (Debts, Payoff, Settings, etc.) has a **Layout Component** that:
1. Provides a consistent sidebar menu
2. Manages view switching via a `$currentView` property
3. Keeps the URL stable (e.g., always `/debts`, never `/debts/4` or `/debts/create`)

### Pattern Example - DebtLayout:

```php
// DebtLayout.php manages these views:
public string $currentView = 'overview'; // overview, create, detail, edit, progress, insights, reconciliations

public function showDetail(int $debtId): void {
    $this->viewingDebtId = $debtId;
    $this->currentView = 'detail';
}

public function showCreate(): void {
    $this->currentView = 'create';
}
```

```blade
{{-- debt-layout.blade.php switches content based on $currentView --}}
@if ($currentView === 'overview')
    <livewire:debt-list />
@elseif ($currentView === 'detail' && $viewingDebt)
    <livewire:debts.debt-detail :debt="$viewingDebt" :embedded="true" />
@elseif ($currentView === 'create')
    <livewire:create-debt />
@endif
```

### Adding New Views - THE RIGHT WAY:

1. **Add a method to the Layout component**: `showNewView()`
2. **Add the view case to the Blade template**: `@elseif ($currentView === 'newview')`
3. **Add sidebar button if needed**: Using `wire:click="showNewView"`
4. **Navigate from child components using**: `$parent.showNewView()`

### Adding New Views - THE WRONG WAY (DO NOT DO THIS):

- [X] Creating a new route like `/debts/something`
- [X] Using `<a href="{{ route('debts.something') }}">`
- [X] Creating standalone Livewire components with their own layouts
- [X] Duplicating sidebar navigation in child components

### Navigation from Child Components:

```blade
{{-- CORRECT: Use $parent to call layout methods --}}
<button wire:click="$parent.showDetail({{ $debt->id }})">View</button>
<button wire:click="$parent.editDebt({{ $debt->id }})">Edit</button>
<button wire:click="$parent.showCreate">Add New</button>

{{-- WRONG: Using routes that change the URL --}}
<a href="{{ route('debts.show', $debt) }}">View</a>
```

### Query Parameter Support (for direct URL access):

The layout components support query parameters for bookmarkable URLs:
- `/debts?view=detail&debtId=4` - View debt #4
- `/debts?view=edit&debtId=4` - Edit debt #4
- `/debts?view=create` - Create new debt

---

## [COMMON MISTAKES] THINGS I TEND TO FORGET

**Double-check these before finishing:**

- [ ] **Layout Component Pattern** - Is this a sub-view that should use `$parent.showX()` instead of a new route?
- [ ] **Sidebar Consistency** - Does the new view maintain the sidebar? (Use embedded mode, not standalone)
- [ ] **URL Stability** - Does navigation keep the URL stable (e.g., `/debts`) or wrongly change it?
- [ ] **Routes** - Did I register the route for new pages/components? (Only for TOP-LEVEL pages!)
- [ ] **Navigation** - Did I add links to the new page from menus/UI?
- [ ] **Run migrations** - Did I actually run `php artisan migrate`?
- [ ] **Update factories** - If I added columns, did I update the factory?
- [ ] **Reset form state** - After save, does the form clear properly?
- [ ] **Refresh data** - After mutations, does the component show fresh data?
- [ ] **wire:model bindings** - Do all inputs have proper bindings?
- [ ] **Declare properties** - Is every `$this->foo` declared as `public $foo`?
- [ ] **Number formatting** - Am I displaying raw numbers or formatted values?
- [ ] **Null handling** - What happens when data is empty/null?

---

## [FORBIDDEN] FORBIDDEN BEHAVIORS

- [X] Hardcoding any user-facing text
- [X] Copy-pasting code without checking for existing solutions
- [X] Skipping tests
- [X] Adding features beyond what was discussed
- [X] Ignoring existing conventions
- [X] Creating files manually when artisan commands exist
- [X] Leaving TODO comments without implementing

---

## [DONE] DEFINITION OF DONE

The implementation is complete when:

- [OK] Feature works as discussed
- [OK] All text uses translation keys
- [OK] Code follows DRY principle
- [OK] Tests written and passing
- [OK] `vendor/bin/pint --dirty` shows no changes needed
- [OK] `vendor/bin/phpstan analyse` passes
- [OK] No hardcoded strings in the diff

---

**NOW: Implement what we discussed, following ALL guidelines above!**

Start by using TodoWrite to plan the implementation tasks, then spawn subagents for parallel work where possible.

---

## [FINAL STEP] RUN /check

**After implementation is complete, run `/check` to verify:**
- Code formatting (Pint)
- Static analysis (PHPStan)
- All tests pass

**Do NOT skip this step!**
