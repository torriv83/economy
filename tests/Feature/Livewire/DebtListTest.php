<?php

use App\Livewire\DebtList;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('getDebtsProperty lastVerifiedAt', function () {
    it('includes formatted lastVerifiedAt when debt has been verified', function () {
        Debt::factory()->create([
            'name' => 'Test Debt',
            'last_verified_at' => '2024-03-15 10:00:00',
        ]);

        // Assert the formatted date appears in the rendered output
        // Norwegian format: "15. mars 2024"
        Livewire::test(DebtList::class)
            ->call('loadData')
            ->assertSee('15. mars 2024');
    });

    it('shows never verified text when debt has never been verified', function () {
        Debt::factory()->create([
            'name' => 'Unverified Debt',
            'last_verified_at' => null,
        ]);

        // Verify the debt is displayed with "never verified" indicator
        Livewire::test(DebtList::class)
            ->call('loadData')
            ->assertSee('Unverified Debt')
            ->assertSee(__('app.never_verified'));
    });
});

describe('reconcileDebt last_verified_at', function () {
    it('sets last_verified_at when reconciling with no adjustment', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'last_verified_at' => null,
        ]);

        Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set("reconciliations.{$debt->id}.balance", '10000') // Same as current balance
            ->set("reconciliations.{$debt->id}.date", '15.03.2024')
            ->call('reconcileDebt', $debt->id);

        $debt->refresh();
        expect($debt->last_verified_at)->not->toBeNull()
            ->and($debt->last_verified_at->format('Y-m-d'))->toBe('2024-03-15');
    });

    it('closes reconciliation modal after verification with no adjustment', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        $component = Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set("reconciliations.{$debt->id}.balance", '10000')
            ->set("reconciliations.{$debt->id}.date", '15.03.2024')
            ->call('reconcileDebt', $debt->id);

        expect($component->get('reconciliations'))->not->toHaveKey($debt->id);
    });

    it('handles very small balance differences as no adjustment needed', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000.005,
            'original_balance' => 10000,
            'last_verified_at' => null,
        ]);

        Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set("reconciliations.{$debt->id}.balance", '10000') // Difference less than 0.01
            ->set("reconciliations.{$debt->id}.date", '20.06.2024')
            ->call('reconcileDebt', $debt->id);

        $debt->refresh();
        expect($debt->last_verified_at)->not->toBeNull()
            ->and($debt->last_verified_at->format('Y-m-d'))->toBe('2024-06-20');
    });

    it('updates last_verified_at when reconciling with adjustment', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'last_verified_at' => null,
        ]);

        Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set("reconciliations.{$debt->id}.balance", '9500') // Adjustment needed
            ->set("reconciliations.{$debt->id}.date", '25.12.2024')
            ->call('reconcileDebt', $debt->id);

        $debt->refresh();
        expect($debt->last_verified_at)->not->toBeNull()
            ->and($debt->last_verified_at->format('Y-m-d'))->toBe('2024-12-25');
    });

    it('parses Norwegian date format correctly for last_verified_at', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
            'last_verified_at' => null,
        ]);

        Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set("reconciliations.{$debt->id}.balance", '10000')
            ->set("reconciliations.{$debt->id}.date", '01.01.2025')
            ->call('reconcileDebt', $debt->id);

        $debt->refresh();
        expect($debt->last_verified_at->format('Y-m-d'))->toBe('2025-01-01');
    });

    it('does not create payment record when no adjustment needed', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
            'original_balance' => 10000,
        ]);

        Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set("reconciliations.{$debt->id}.balance", '10000')
            ->set("reconciliations.{$debt->id}.date", '15.03.2024')
            ->call('reconcileDebt', $debt->id);

        expect($debt->payments()->count())->toBe(0);
    });
});

describe('reconciliation modal', function () {
    it('opens reconciliation modal with correct initial values', function () {
        $debt = Debt::factory()->create([
            'balance' => 15000,
        ]);

        $component = Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id);

        $reconciliations = $component->get('reconciliations');

        expect($reconciliations)->toHaveKey($debt->id)
            ->and($reconciliations[$debt->id]['show'])->toBeTrue()
            ->and($reconciliations[$debt->id]['balance'])->toBe('15000');
    });

    it('closes reconciliation modal', function () {
        $debt = Debt::factory()->create();

        $component = Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->call('closeReconciliationModal', $debt->id);

        expect($component->get('reconciliations'))->not->toHaveKey($debt->id);
    });

    it('stores balance in reconciliations array', function () {
        $debt = Debt::factory()->create([
            'balance' => 10000,
        ]);

        $component = Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set("reconciliations.{$debt->id}.balance", '9500');

        $reconciliations = $component->get('reconciliations');
        expect($reconciliations[$debt->id]['balance'])->toBe('9500');
    });
});

describe('validation', function () {
    it('validates balance is required', function () {
        $debt = Debt::factory()->create();

        Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set("reconciliations.{$debt->id}.balance", '')
            ->set("reconciliations.{$debt->id}.date", '15.03.2024')
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(["reconciliations.{$debt->id}.balance" => 'required']);
    });

    it('validates balance is numeric', function () {
        $debt = Debt::factory()->create();

        Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set("reconciliations.{$debt->id}.balance", 'not-a-number')
            ->set("reconciliations.{$debt->id}.date", '15.03.2024')
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(["reconciliations.{$debt->id}.balance" => 'numeric']);
    });

    it('validates date is required', function () {
        $debt = Debt::factory()->create();

        Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set("reconciliations.{$debt->id}.balance", '10000')
            ->set("reconciliations.{$debt->id}.date", '')
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(["reconciliations.{$debt->id}.date" => 'required']);
    });

    it('validates date format is Norwegian DD.MM.YYYY', function () {
        $debt = Debt::factory()->create();

        Livewire::test(DebtList::class)
            ->call('openReconciliationModal', $debt->id)
            ->set("reconciliations.{$debt->id}.balance", '10000')
            ->set("reconciliations.{$debt->id}.date", '2024-03-15') // Wrong format
            ->call('reconcileDebt', $debt->id)
            ->assertHasErrors(["reconciliations.{$debt->id}.date" => 'date_format']);
    });
});
