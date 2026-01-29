<?php

use App\Livewire\PayoffCalendar;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('payment modal', function () {
    it('opens modal with correct debt context', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->assertSet('showPaymentModal', true)
            ->assertSet('selectedDebtId', $debt->id)
            ->assertSet('selectedDebtName', $debt->name)
            ->assertSet('plannedAmount', 500.00)
            ->assertSet('paymentAmount', 500.00)
            ->assertSet('selectedMonthNumber', 1)
            ->assertSet('selectedPaymentMonth', now()->format('Y-m'));
    });

    it('pre-fills amount with planned amount', function () {
        $debt = Debt::factory()->create([
            'name' => 'Credit Card',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 15,
            'minimum_payment' => 300,
            'due_day' => 20,
        ]);

        $plannedAmount = 750.50;

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 1000, 'strategy' => 'snowball'])
            ->call('openPaymentModal', $debt->id, $debt->name, $plannedAmount, 2, '2025-02')
            ->assertSet('paymentAmount', $plannedAmount)
            ->assertSet('plannedAmount', $plannedAmount);
    });

    it('pre-fills date with today in Norwegian format', function () {
        $debt = Debt::factory()->create([
            'name' => 'Car Loan',
            'balance' => 20000,
            'original_balance' => 20000,
            'interest_rate' => 5,
            'minimum_payment' => 1000,
            'due_day' => 10,
        ]);

        $expectedDate = now()->format('d.m.Y');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 500, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 1000.00, 1, now()->format('Y-m'))
            ->assertSet('paymentDate', $expectedDate);
    });

    it('closes modal and resets all state', function () {
        $debt = Debt::factory()->create([
            'name' => 'Personal Loan',
            'balance' => 8000,
            'original_balance' => 8000,
            'interest_rate' => 8,
            'minimum_payment' => 400,
            'due_day' => 25,
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 1500, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 600.00, 3, '2025-03')
            ->assertSet('showPaymentModal', true)
            ->call('closePaymentModal')
            ->assertSet('showPaymentModal', false)
            ->assertSet('selectedDebtId', 0)
            ->assertSet('selectedDebtName', '')
            ->assertSet('paymentAmount', 0)
            ->assertSet('paymentDate', '')
            ->assertSet('paymentNotes', '')
            ->assertSet('selectedMonthNumber', 0)
            ->assertSet('selectedPaymentMonth', '')
            ->assertSet('plannedAmount', 0);
    });
});

describe('edit payment modal', function () {
    it('opens edit modal with existing payment data', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 4500,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $payment = Payment::factory()->create([
            'debt_id' => $debt->id,
            'planned_amount' => 500,
            'actual_amount' => 550,
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
            'payment_date' => now()->subDays(5),
            'notes' => 'Original notes',
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openEditPaymentModal', $payment->id)
            ->assertSet('showPaymentModal', true)
            ->assertSet('isEditMode', true)
            ->assertSet('selectedPaymentId', $payment->id)
            ->assertSet('selectedDebtId', $debt->id)
            ->assertSet('selectedDebtName', $debt->name)
            ->assertSet('plannedAmount', 500)
            ->assertSet('paymentAmount', 550)
            ->assertSet('selectedMonthNumber', 1)
            ->assertSet('paymentNotes', 'Original notes');
    });

    it('updates existing payment when in edit mode', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 4500,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $payment = Payment::factory()->create([
            'debt_id' => $debt->id,
            'planned_amount' => 500,
            'actual_amount' => 500,
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
            'payment_date' => now()->subDays(5),
            'notes' => null,
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openEditPaymentModal', $payment->id)
            ->set('paymentAmount', 600)
            ->set('paymentDate', now()->subDays(3)->format('d.m.Y'))
            ->set('paymentNotes', 'Updated notes')
            ->call('recordPayment')
            ->assertSet('showPaymentModal', false);

        $payment->refresh();
        expect($payment->actual_amount)->toBe(600.0)
            ->and($payment->payment_date->format('Y-m-d'))->toBe(now()->subDays(3)->format('Y-m-d'))
            ->and($payment->notes)->toBe('Updated notes');
    });

    it('does not create duplicate when editing', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 4500,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $payment = Payment::factory()->create([
            'debt_id' => $debt->id,
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
            'payment_date' => now()->subDays(5),
        ]);

        $initialCount = Payment::count();

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openEditPaymentModal', $payment->id)
            ->set('paymentAmount', 600)
            ->call('recordPayment');

        expect(Payment::count())->toBe($initialCount);
    });

    it('closes edit modal and resets edit state', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 4500,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $payment = Payment::factory()->create([
            'debt_id' => $debt->id,
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
            'payment_date' => now()->subDays(5),
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openEditPaymentModal', $payment->id)
            ->assertSet('isEditMode', true)
            ->assertSet('selectedPaymentId', $payment->id)
            ->call('closePaymentModal')
            ->assertSet('isEditMode', false)
            ->assertSet('selectedPaymentId', null)
            ->assertSet('showPaymentModal', false);
    });
});

describe('payment validation', function () {
    it('validates amount is required', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        // When amount is empty string, Livewire coerces it - validation still fails
        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->set('paymentAmount', null)
            ->call('recordPayment')
            ->assertHasErrors(['paymentAmount']);
    });

    it('validates amount must be positive', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->set('paymentAmount', 0)
            ->call('recordPayment')
            ->assertHasErrors(['paymentAmount' => 'min']);
    });

    it('validates amount cannot be negative', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->set('paymentAmount', -100)
            ->call('recordPayment')
            ->assertHasErrors(['paymentAmount' => 'min']);
    });

    it('validates date is required', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->set('paymentDate', '')
            ->call('recordPayment')
            ->assertHasErrors(['paymentDate' => 'required']);
    });

    it('validates date format is dd.mm.YYYY', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->set('paymentDate', '2024-01-15') // Wrong format (ISO)
            ->call('recordPayment')
            ->assertHasErrors(['paymentDate' => 'date_format']);
    });

    it('validates date format rejects US format', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->set('paymentDate', '01/15/2024') // Wrong format (US)
            ->call('recordPayment')
            ->assertHasErrors(['paymentDate' => 'date_format']);
    });

    it('validates date cannot be in future', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $futureDate = now()->addDay()->format('d.m.Y');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->set('paymentDate', $futureDate)
            ->call('recordPayment')
            ->assertHasErrors(['paymentDate']);
    });

    it('validates notes max length', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $longNotes = str_repeat('a', 501);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->set('paymentNotes', $longNotes)
            ->call('recordPayment')
            ->assertHasErrors(['paymentNotes' => 'max']);
    });

    it('allows empty notes', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->set('paymentNotes', '')
            ->call('recordPayment')
            ->assertHasNoErrors(['paymentNotes']);
    });

    it('allows null notes', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->set('paymentNotes', null)
            ->call('recordPayment')
            ->assertHasNoErrors(['paymentNotes']);
    });

    it('allows notes at exactly 500 characters', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $maxNotes = str_repeat('a', 500);

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, now()->format('Y-m'))
            ->set('paymentNotes', $maxNotes)
            ->call('recordPayment')
            ->assertHasNoErrors(['paymentNotes']);
    });
});

describe('recording payment', function () {
    it('records payment with correct data', function () {
        $debt = Debt::factory()->create([
            'name' => 'Credit Card',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $paymentMonth = now()->format('Y-m');
        $paymentDate = now()->format('d.m.Y');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasNoErrors();

        $payment = Payment::where('debt_id', $debt->id)->first();
        expect($payment)->not->toBeNull()
            ->and($payment->planned_amount)->toBe(500.0)
            ->and($payment->actual_amount)->toBe(500.0)
            ->and($payment->month_number)->toBe(1)
            ->and($payment->payment_month)->toBe($paymentMonth);
    });

    it('records payment with user-specified amount', function () {
        $debt = Debt::factory()->create([
            'name' => 'Car Loan',
            'balance' => 15000,
            'original_balance' => 15000,
            'interest_rate' => 6,
            'minimum_payment' => 800,
            'due_day' => 20,
        ]);

        $paymentMonth = now()->format('Y-m');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 1000, 'strategy' => 'snowball'])
            ->call('openPaymentModal', $debt->id, $debt->name, 800.00, 1, $paymentMonth)
            ->set('paymentAmount', 1200.50) // User changed the amount
            ->call('recordPayment')
            ->assertHasNoErrors();

        $payment = Payment::where('debt_id', $debt->id)->first();
        expect($payment)->not->toBeNull()
            ->and($payment->planned_amount)->toBe(800.0)
            ->and($payment->actual_amount)->toBe(1200.5);
    });

    it('records payment with user-specified date', function () {
        $debt = Debt::factory()->create([
            'name' => 'Personal Loan',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 300,
            'due_day' => 5,
        ]);

        $paymentMonth = now()->format('Y-m');
        $customDate = now()->subDays(3)->format('d.m.Y');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 500, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 300.00, 1, $paymentMonth)
            ->set('paymentDate', $customDate)
            ->call('recordPayment')
            ->assertHasNoErrors();

        $payment = Payment::where('debt_id', $debt->id)->first();
        expect($payment)->not->toBeNull()
            ->and($payment->payment_date->format('d.m.Y'))->toBe($customDate);
    });

    it('records payment with notes', function () {
        $debt = Debt::factory()->create([
            'name' => 'Student Loan',
            'balance' => 25000,
            'original_balance' => 25000,
            'interest_rate' => 4,
            'minimum_payment' => 200,
            'due_day' => 1,
        ]);

        $paymentMonth = now()->format('Y-m');
        $notes = 'Extra payment from tax refund';

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 1000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 200.00, 1, $paymentMonth)
            ->set('paymentNotes', $notes)
            ->call('recordPayment')
            ->assertHasNoErrors();

        $payment = Payment::where('debt_id', $debt->id)->first();
        expect($payment)->not->toBeNull()
            ->and($payment->notes)->toBe($notes);
    });

    it('prevents duplicate payments for same debt and month', function () {
        $debt = Debt::factory()->create([
            'name' => 'Credit Card',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 15,
            'minimum_payment' => 300,
            'due_day' => 15,
        ]);

        $paymentMonth = now()->format('Y-m');

        // Create first payment
        Payment::factory()->create([
            'debt_id' => $debt->id,
            'month_number' => 1,
            'payment_month' => $paymentMonth,
        ]);

        // Try to record duplicate payment
        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 300.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasErrors(['paymentAmount']);

        expect(Payment::where('debt_id', $debt->id)->count())->toBe(1);
    });

    it('updates debt balance after recording', function () {
        $debt = Debt::factory()->create([
            'name' => 'Personal Loan',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 500,
            'due_day' => 10,
        ]);

        $paymentMonth = now()->format('Y-m');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 1000.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasNoErrors();

        // Interest: 10000 * 12% / 12 = 100.00
        // Principal: 1000 - 100 = 900.00
        // Balance: 10000 - 900 = 9100.00
        expect($debt->fresh()->balance)->toBe(9100.0);
    });

    it('creates payment record successfully', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Card',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 250,
            'due_day' => 25,
        ]);

        $paymentMonth = now()->format('Y-m');

        // Verify the payment is recorded and modal closes successfully
        Livewire::test(PayoffCalendar::class, ['extraPayment' => 1000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 250.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasNoErrors()
            ->assertSet('showPaymentModal', false);

        // Verify the payment was actually created
        expect(Payment::where('debt_id', $debt->id)->count())->toBe(1);
    });

    it('closes modal after successful recording', function () {
        $debt = Debt::factory()->create([
            'name' => 'Mortgage',
            'balance' => 200000,
            'original_balance' => 200000,
            'interest_rate' => 3,
            'minimum_payment' => 1500,
            'due_day' => 1,
        ]);

        $paymentMonth = now()->format('Y-m');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 500, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 1500.00, 1, $paymentMonth)
            ->assertSet('showPaymentModal', true)
            ->call('recordPayment')
            ->assertHasNoErrors()
            ->assertSet('showPaymentModal', false);
    });

    it('calculates interest and principal correctly', function () {
        $debt = Debt::factory()->create([
            'name' => 'High Interest Card',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 24, // 24% annual = 2% monthly
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $paymentMonth = now()->format('Y-m');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasNoErrors();

        $payment = Payment::where('debt_id', $debt->id)->first();
        // Interest: 10000 * 24% / 12 = 200.00
        // Principal: 500 - 200 = 300.00
        expect($payment->interest_paid)->toBe(200.0)
            ->and($payment->principal_paid)->toBe(300.0);
    });
});

describe('payment events with context', function () {
    it('includes debt_id in unpaid payment events', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $component = Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche']);

        $paymentEvents = $component->get('paymentEvents');

        // Find an unpaid event
        $unpaidEvent = collect($paymentEvents)->first(function ($event) {
            return collect($event['debts'])->contains(fn ($d) => ! ($d['isPaid'] ?? true));
        });

        if ($unpaidEvent) {
            $unpaidDebt = collect($unpaidEvent['debts'])->first(fn ($d) => ! ($d['isPaid'] ?? true));
            expect($unpaidDebt)->toHaveKey('debt_id')
                ->and($unpaidDebt['debt_id'])->toBe($debt->id);
        }
    });

    it('includes month_number in unpaid payment events', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $component = Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche']);

        $paymentEvents = $component->get('paymentEvents');

        // Find an unpaid event
        $unpaidEvent = collect($paymentEvents)->first(function ($event) {
            return collect($event['debts'])->contains(fn ($d) => ! ($d['isPaid'] ?? true));
        });

        if ($unpaidEvent) {
            $unpaidDebt = collect($unpaidEvent['debts'])->first(fn ($d) => ! ($d['isPaid'] ?? true));
            expect($unpaidDebt)->toHaveKey('month_number')
                ->and($unpaidDebt['month_number'])->toBeGreaterThanOrEqual(1);
        }
    });

    it('includes payment_month in unpaid payment events', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $component = Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche']);

        $paymentEvents = $component->get('paymentEvents');

        // Find an unpaid event
        $unpaidEvent = collect($paymentEvents)->first(function ($event) {
            return collect($event['debts'])->contains(fn ($d) => ! ($d['isPaid'] ?? true));
        });

        if ($unpaidEvent) {
            $unpaidDebt = collect($unpaidEvent['debts'])->first(fn ($d) => ! ($d['isPaid'] ?? true));
            expect($unpaidDebt)->toHaveKey('payment_month')
                ->and($unpaidDebt['payment_month'])->toMatch('/^\d{4}-\d{2}$/');
        }
    });

    it('marks paid events with context data for editing', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 500,
            'due_day' => now()->day, // Due today so it shows in current month
        ]);

        // Record a payment for this month
        Payment::factory()->create([
            'debt_id' => $debt->id,
            'month_number' => 1,
            'payment_month' => now()->format('Y-m'),
            'payment_date' => now(),
        ]);

        $component = Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche']);

        $paymentEvents = $component->get('paymentEvents');

        // Find a paid event
        $paidEvent = collect($paymentEvents)->first(function ($event) {
            return collect($event['debts'])->contains(fn ($d) => $d['isPaid'] ?? false);
        });

        if ($paidEvent) {
            $paidDebt = collect($paidEvent['debts'])->first(fn ($d) => $d['isPaid'] ?? false);
            expect($paidDebt['isPaid'])->toBeTrue()
                ->and($paidDebt)->toHaveKey('debt_id') // Paid events have debt_id for editing
                ->and($paidDebt)->toHaveKey('payment_id'); // Paid events have payment_id for editing
        }
    });
});

describe('edge cases', function () {
    it('handles zero interest rate correctly', function () {
        $debt = Debt::factory()->create([
            'name' => 'Interest-Free Loan',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 0,
            'minimum_payment' => 500,
            'due_day' => 15,
        ]);

        $paymentMonth = now()->format('Y-m');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 500.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasNoErrors();

        $payment = Payment::where('debt_id', $debt->id)->first();
        expect($payment->interest_paid)->toBe(0.0)
            ->and($payment->principal_paid)->toBe(500.0)
            ->and($debt->fresh()->balance)->toBe(9500.0);
    });

    it('handles overpayment that would exceed balance', function () {
        $debt = Debt::factory()->create([
            'name' => 'Small Debt',
            'balance' => 500,
            'original_balance' => 500,
            'interest_rate' => 10,
            'minimum_payment' => 100,
            'due_day' => 10,
        ]);

        $paymentMonth = now()->format('Y-m');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 1000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 100.00, 1, $paymentMonth)
            ->set('paymentAmount', 600.00) // More than balance
            ->call('recordPayment')
            ->assertHasNoErrors();

        // Balance should not go negative
        expect($debt->fresh()->balance)->toBeGreaterThanOrEqual(0);
    });

    it('handles very small payment amounts', function () {
        $debt = Debt::factory()->create([
            'name' => 'Test Debt',
            'balance' => 10000,
            'original_balance' => 10000,
            'interest_rate' => 12,
            'minimum_payment' => 100,
            'due_day' => 15,
        ]);

        $paymentMonth = now()->format('Y-m');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 100.00, 1, $paymentMonth)
            ->set('paymentAmount', 0.01) // Minimum allowed
            ->call('recordPayment')
            ->assertHasNoErrors();

        $payment = Payment::where('debt_id', $debt->id)->first();
        expect($payment->actual_amount)->toBe(0.01);
    });

    it('handles payment on last day of previous month', function () {
        $debt = Debt::factory()->create([
            'name' => 'Monthly Payment',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 8,
            'minimum_payment' => 250,
            'due_day' => 28,
        ]);

        // Use last month's last day to ensure it's always in the past
        $lastMonth = now()->subMonth();
        $paymentMonth = $lastMonth->format('Y-m');
        $lastDayOfLastMonth = $lastMonth->endOfMonth()->format('d.m.Y');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 500, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 250.00, 1, $paymentMonth)
            ->set('paymentDate', $lastDayOfLastMonth)
            ->call('recordPayment')
            ->assertHasNoErrors();

        expect(Payment::where('debt_id', $debt->id)->count())->toBe(1);
    });

    it('handles multiple debts with different due days', function () {
        $debt1 = Debt::factory()->create([
            'name' => 'Debt Early Month',
            'balance' => 5000,
            'original_balance' => 5000,
            'interest_rate' => 10,
            'minimum_payment' => 250,
            'due_day' => 5,
        ]);

        $debt2 = Debt::factory()->create([
            'name' => 'Debt Mid Month',
            'balance' => 8000,
            'original_balance' => 8000,
            'interest_rate' => 15,
            'minimum_payment' => 400,
            'due_day' => 15,
        ]);

        $debt3 = Debt::factory()->create([
            'name' => 'Debt End Month',
            'balance' => 3000,
            'original_balance' => 3000,
            'interest_rate' => 8,
            'minimum_payment' => 150,
            'due_day' => 28,
        ]);

        $paymentMonth = now()->format('Y-m');

        // Record payments for all three debts
        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt1->id, $debt1->name, 250.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasNoErrors();

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt2->id, $debt2->name, 400.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasNoErrors();

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 2000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt3->id, $debt3->name, 150.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasNoErrors();

        expect(Payment::count())->toBe(3);
    });

    it('handles debt without due_day set', function () {
        $debt = Debt::factory()->create([
            'name' => 'No Due Day Debt',
            'balance' => 7000,
            'original_balance' => 7000,
            'interest_rate' => 12,
            'minimum_payment' => 350,
            'due_day' => null,
        ]);

        $paymentMonth = now()->format('Y-m');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 1000, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 350.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasNoErrors();

        expect(Payment::where('debt_id', $debt->id)->count())->toBe(1);
    });
});

describe('different strategies', function () {
    it('records payment correctly with snowball strategy', function () {
        $debt = Debt::factory()->create([
            'name' => 'Snowball Test',
            'balance' => 3000,
            'original_balance' => 3000,
            'interest_rate' => 15,
            'minimum_payment' => 150,
            'due_day' => 10,
        ]);

        $paymentMonth = now()->format('Y-m');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 1000, 'strategy' => 'snowball'])
            ->call('openPaymentModal', $debt->id, $debt->name, 150.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasNoErrors();

        expect(Payment::where('debt_id', $debt->id)->count())->toBe(1);
    });

    it('records payment correctly with avalanche strategy', function () {
        $debt = Debt::factory()->create([
            'name' => 'Avalanche Test',
            'balance' => 8000,
            'original_balance' => 8000,
            'interest_rate' => 22,
            'minimum_payment' => 400,
            'due_day' => 20,
        ]);

        $paymentMonth = now()->format('Y-m');

        Livewire::test(PayoffCalendar::class, ['extraPayment' => 1500, 'strategy' => 'avalanche'])
            ->call('openPaymentModal', $debt->id, $debt->name, 400.00, 1, $paymentMonth)
            ->call('recordPayment')
            ->assertHasNoErrors();

        expect(Payment::where('debt_id', $debt->id)->count())->toBe(1);
    });
});
