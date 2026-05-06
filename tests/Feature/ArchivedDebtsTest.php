<?php

use App\Livewire\ArchivedDebts;
use App\Livewire\DebtList;
use App\Models\Debt;
use App\Models\Payment;
use App\Models\User;
use App\Services\DebtCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    DebtCacheService::clearCache();
    $this->actingAs(User::factory()->create());
});

afterEach(function () {
    Cache::flush();
});

test('debt model active scope returns only debts without paid_off_at', function () {
    $active = Debt::factory()->create(['balance' => 1000, 'paid_off_at' => null]);
    $archived = Debt::factory()->create(['balance' => 0, 'paid_off_at' => now()]);

    expect(Debt::active()->pluck('id')->all())->toBe([$active->id]);
    expect(Debt::archived()->pluck('id')->all())->toBe([$archived->id]);
});

test('isArchived returns true when paid_off_at is set', function () {
    $debt = Debt::factory()->make(['paid_off_at' => null]);
    expect($debt->isArchived())->toBeFalse();

    $debt->paid_off_at = now();
    expect($debt->isArchived())->toBeTrue();
});

test('observer sets paid_off_at when balance reaches zero', function () {
    $debt = Debt::factory()->create(['balance' => 1000, 'paid_off_at' => null]);

    $debt->update(['balance' => 0]);

    expect($debt->fresh()->paid_off_at)->not->toBeNull();
});

test('observer sets paid_off_at when balance reaches threshold', function () {
    $debt = Debt::factory()->create(['balance' => 1000, 'paid_off_at' => null]);

    $debt->update(['balance' => 0.005]);

    expect($debt->fresh()->paid_off_at)->not->toBeNull();
});

test('observer clears paid_off_at when balance increases above threshold', function () {
    $debt = Debt::factory()->create(['balance' => 0, 'paid_off_at' => now()]);

    $debt->update(['balance' => 500]);

    expect($debt->fresh()->paid_off_at)->toBeNull();
});

test('observer keeps existing paid_off_at if already set', function () {
    $original = now()->subDays(5);
    $debt = Debt::factory()->create(['balance' => 0, 'paid_off_at' => $original]);

    $debt->update(['balance' => 0, 'name' => 'Updated name']);

    expect($debt->fresh()->paid_off_at->toDateString())->toBe($original->toDateString());
});

test('debt list hides archived debts', function () {
    Debt::factory()->create(['name' => 'Active Debt', 'balance' => 50000, 'paid_off_at' => null]);
    Debt::factory()->create(['name' => 'Archived Debt', 'balance' => 0, 'paid_off_at' => now()]);

    Livewire::test(DebtList::class)
        ->call('loadData')
        ->assertSee('Active Debt')
        ->assertDontSee('Archived Debt');
});

test('debt list total only counts active debts', function () {
    Debt::factory()->create(['balance' => 50000, 'paid_off_at' => null]);
    Debt::factory()->create(['balance' => 0, 'paid_off_at' => now()]);

    Livewire::test(DebtList::class)
        ->call('loadData')
        ->assertSee('50 000');
});

test('archived debts page renders successfully', function () {
    $response = $this->get(route('archived'));

    $response->assertSuccessful();
});

test('archived debts component shows only archived debts', function () {
    Debt::factory()->create(['name' => 'Active Debt', 'balance' => 50000, 'paid_off_at' => null]);
    Debt::factory()->create(['name' => 'Archived Debt', 'balance' => 0, 'paid_off_at' => now()]);

    Livewire::test(ArchivedDebts::class)
        ->call('loadData')
        ->assertSee('Archived Debt')
        ->assertDontSee('Active Debt');
});

test('archived debts shows empty state when no archived debts exist', function () {
    Debt::factory()->create(['name' => 'Active Debt', 'balance' => 50000, 'paid_off_at' => null]);

    Livewire::test(ArchivedDebts::class)
        ->call('loadData')
        ->assertSee(__('app.no_archived_debts'));
});

test('archived debts can toggle include_in_charts', function () {
    $debt = Debt::factory()->create([
        'balance' => 0,
        'paid_off_at' => now(),
        'include_in_charts' => true,
    ]);

    Livewire::test(ArchivedDebts::class)
        ->call('loadData')
        ->call('toggleIncludeInCharts', $debt->id);

    expect($debt->fresh()->include_in_charts)->toBeFalse();
});

test('archived debts toggle does nothing for active debts', function () {
    $debt = Debt::factory()->create([
        'balance' => 1000,
        'paid_off_at' => null,
        'include_in_charts' => true,
    ]);

    Livewire::test(ArchivedDebts::class)
        ->call('loadData')
        ->call('toggleIncludeInCharts', $debt->id);

    expect($debt->fresh()->include_in_charts)->toBeTrue();
});

test('archived debts displays totals from payments', function () {
    $debt = Debt::factory()->create([
        'name' => 'Test Debt',
        'balance' => 0,
        'original_balance' => 10000,
        'paid_off_at' => now(),
    ]);

    Payment::query()->create([
        'debt_id' => $debt->id,
        'planned_amount' => 5000,
        'actual_amount' => 5000,
        'interest_paid' => 500,
        'principal_paid' => 4500,
        'payment_date' => now(),
        'month_number' => 1,
        'payment_month' => now()->format('Y-m'),
        'is_reconciliation_adjustment' => false,
    ]);
    Payment::query()->create([
        'debt_id' => $debt->id,
        'planned_amount' => 5500,
        'actual_amount' => 5500,
        'interest_paid' => 500,
        'principal_paid' => 5000,
        'payment_date' => now(),
        'month_number' => 2,
        'payment_month' => now()->format('Y-m'),
        'is_reconciliation_adjustment' => false,
    ]);

    Livewire::test(ArchivedDebts::class)
        ->call('loadData')
        ->assertSee('10 500')
        ->assertSee('1 000');
});

test('cache service getAllActive returns only active debts', function () {
    Debt::factory()->create(['name' => 'Active 1', 'balance' => 1000, 'paid_off_at' => null]);
    Debt::factory()->create(['name' => 'Active 2', 'balance' => 2000, 'paid_off_at' => null]);
    Debt::factory()->create(['name' => 'Archived', 'balance' => 0, 'paid_off_at' => now()]);

    $service = app(DebtCacheService::class);

    $activeNames = $service->getAllActive()->pluck('name')->all();

    expect($activeNames)->toHaveCount(2);
    expect($activeNames)->toContain('Active 1', 'Active 2');
    expect($activeNames)->not->toContain('Archived');
});

test('cache service getAllArchived returns only archived debts', function () {
    Debt::factory()->create(['name' => 'Active', 'balance' => 1000, 'paid_off_at' => null]);
    Debt::factory()->create(['name' => 'Archived', 'balance' => 0, 'paid_off_at' => now()]);

    $service = app(DebtCacheService::class);

    $archivedNames = $service->getAllArchived()->pluck('name')->all();

    expect($archivedNames)->toBe(['Archived']);
});
