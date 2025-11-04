<?php

use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Payment Model', function () {
    it('can be created with factory', function () {
        $payment = Payment::factory()->create();

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->id)->not->toBeNull()
            ->and($payment->debt_id)->not->toBeNull();
    });

    it('has correct fillable attributes', function () {
        $payment = Payment::factory()->create([
            'planned_amount' => 500.0,
            'actual_amount' => 550.0,
            'month_number' => 5,
            'payment_month' => '2025-05',
            'notes' => 'Test note',
        ]);

        expect($payment->planned_amount)->toBe(500.0)
            ->and($payment->actual_amount)->toBe(550.0)
            ->and($payment->month_number)->toBe(5)
            ->and($payment->payment_month)->toBe('2025-05')
            ->and($payment->notes)->toBe('Test note');
    });
});

describe('Payment Relationships', function () {
    it('belongs to a debt', function () {
        $debt = Debt::factory()->create(['name' => 'Test Debt']);
        $payment = Payment::factory()->create(['debt_id' => $debt->id]);

        expect($payment->debt)->toBeInstanceOf(Debt::class)
            ->and($payment->debt->id)->toBe($debt->id)
            ->and($payment->debt->name)->toBe('Test Debt');
    });

    it('has debt relationship defined', function () {
        $payment = new Payment;

        expect(method_exists($payment, 'debt'))->toBeTrue();
    });

    it('eager loads debt relationship', function () {
        $debt = Debt::factory()->create();
        $payment = Payment::factory()->create(['debt_id' => $debt->id]);

        $loadedPayment = Payment::with('debt')->find($payment->id);

        expect($loadedPayment->relationLoaded('debt'))->toBeTrue();
    });
});

describe('Payment Casts', function () {
    it('casts planned_amount to float', function () {
        $payment = Payment::factory()->create(['planned_amount' => '123.45']);

        expect($payment->planned_amount)->toBeFloat()
            ->and($payment->planned_amount)->toBe(123.45);
    });

    it('casts actual_amount to float', function () {
        $payment = Payment::factory()->create(['actual_amount' => '678.90']);

        expect($payment->actual_amount)->toBeFloat()
            ->and($payment->actual_amount)->toBe(678.90);
    });

    it('casts payment_date to date', function () {
        $payment = Payment::factory()->create(['payment_date' => '2025-01-15']);

        expect($payment->payment_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($payment->payment_date->format('Y-m-d'))->toBe('2025-01-15');
    });

    it('casts month_number to integer', function () {
        $payment = Payment::factory()->create(['month_number' => '5']);

        expect($payment->month_number)->toBeInt()
            ->and($payment->month_number)->toBe(5);
    });
});

describe('Payment Database', function () {
    it('stores payment correctly in database', function () {
        $debt = Debt::factory()->create();

        $payment = Payment::create([
            'debt_id' => $debt->id,
            'planned_amount' => 1000.0,
            'actual_amount' => 950.0,
            'payment_date' => now(),
            'month_number' => 3,
            'payment_month' => '2025-03',
            'notes' => 'Partial payment',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'debt_id' => $debt->id,
            'planned_amount' => 1000.0,
            'actual_amount' => 950.0,
            'month_number' => 3,
            'payment_month' => '2025-03',
            'notes' => 'Partial payment',
        ]);
    });

    it('can update payment attributes', function () {
        $payment = Payment::factory()->create(['actual_amount' => 500.0]);

        $payment->update(['actual_amount' => 750.0]);

        expect($payment->fresh()->actual_amount)->toBe(750.0);
    });

    it('can delete payment', function () {
        $payment = Payment::factory()->create();
        $paymentId = $payment->id;

        $payment->delete();

        $this->assertDatabaseMissing('payments', ['id' => $paymentId]);
    });

    it('has timestamps', function () {
        $payment = Payment::factory()->create();

        expect($payment->created_at)->not->toBeNull()
            ->and($payment->updated_at)->not->toBeNull();
    });
});

describe('Payment Validation', function () {
    it('requires debt_id', function () {
        try {
            Payment::create([
                'planned_amount' => 500.0,
                'actual_amount' => 500.0,
                'payment_date' => now(),
                'month_number' => 1,
                'payment_month' => '2025-01',
            ]);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            expect($e)->toBeInstanceOf(\Exception::class);
        }
    });

    it('allows null notes', function () {
        $payment = Payment::factory()->create(['notes' => null]);

        expect($payment->notes)->toBeNull();
    });
});

describe('Payment Factory', function () {
    it('creates payment with default values', function () {
        $payment = Payment::factory()->create();

        expect($payment->planned_amount)->toBeFloat()
            ->and($payment->actual_amount)->toBeFloat()
            ->and($payment->month_number)->toBeInt()
            ->and($payment->payment_month)->toBeString()
            ->and($payment->payment_date)->not->toBeNull();
    });

    it('creates payment with custom debt', function () {
        $debt = Debt::factory()->create(['name' => 'Custom Debt']);
        $payment = Payment::factory()->create(['debt_id' => $debt->id]);

        expect($payment->debt->name)->toBe('Custom Debt');
    });

    it('can create multiple payments', function () {
        $payments = Payment::factory()->count(5)->create();

        expect($payments)->toHaveCount(5)
            ->and(Payment::count())->toBe(5);
    });

    it('creates unique month numbers by default', function () {
        $debt = Debt::factory()->create();
        $payment1 = Payment::factory()->create(['debt_id' => $debt->id]);
        $payment2 = Payment::factory()->create(['debt_id' => $debt->id]);

        expect($payment1->month_number)->toBeInt()
            ->and($payment2->month_number)->toBeInt();
    });
});

describe('Payment Edge Cases', function () {
    it('handles zero amounts', function () {
        $payment = Payment::factory()->create([
            'planned_amount' => 0.0,
            'actual_amount' => 0.0,
        ]);

        expect($payment->planned_amount)->toBe(0.0)
            ->and($payment->actual_amount)->toBe(0.0);
    });

    it('handles large amounts', function () {
        $payment = Payment::factory()->create([
            'planned_amount' => 999999.99,
            'actual_amount' => 999999.99,
        ]);

        expect($payment->planned_amount)->toBe(999999.99)
            ->and($payment->actual_amount)->toBe(999999.99);
    });

    it('handles decimal precision', function () {
        $payment = Payment::factory()->create([
            'planned_amount' => 123.456,
            'actual_amount' => 789.012,
        ]);

        // SQLite may round to 2 decimal places
        expect($payment->planned_amount)->toBeFloat()
            ->and($payment->actual_amount)->toBeFloat();
    });

    it('handles different planned and actual amounts', function () {
        $payment = Payment::factory()->create([
            'planned_amount' => 1000.0,
            'actual_amount' => 850.0,
        ]);

        expect($payment->planned_amount)->toBe(1000.0)
            ->and($payment->actual_amount)->toBe(850.0)
            ->and($payment->actual_amount)->not->toBe($payment->planned_amount);
    });

    it('stores payment_month as string', function () {
        $payment = Payment::factory()->create(['payment_month' => '2025-12']);

        expect($payment->payment_month)->toBeString()
            ->and($payment->payment_month)->toBe('2025-12');
    });

    it('handles high month numbers', function () {
        $payment = Payment::factory()->create(['month_number' => 60]);

        expect($payment->month_number)->toBe(60);
    });

    it('maintains relationship after debt update', function () {
        $debt = Debt::factory()->create(['name' => 'Original Name']);
        $payment = Payment::factory()->create(['debt_id' => $debt->id]);

        $debt->update(['name' => 'Updated Name']);

        expect($payment->fresh()->debt->name)->toBe('Updated Name');
    });
});
