<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Owns the reconciliation lifecycle: apply, revise, revoke + reads.
 *
 * Reconciliation = the user reports the actual debt balance on a given date,
 * and the app records the discrepancy as a Payment row with
 * `is_reconciliation_adjustment = true` and `month_number = null`. The Payment's
 * `principal_paid` is the signed adjustment that, when summed across all
 * payments for the debt, makes `original_balance - SUM(principal_paid)` equal
 * to the reported actual balance.
 */
class ReconciliationService
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Create a new reconciliation adjustment for a debt.
     */
    public function apply(
        Debt $debt,
        float $actualBalance,
        string $reconciliationDate,
        ?string $notes = null,
    ): Payment {
        $principalPaid = $this->calculatePrincipalAdjustment(
            calculatedBalanceExcludingThisAdjustment: $debt->balance,
            actualBalance: $actualBalance,
        );

        if (abs($principalPaid) < 0.01) {
            throw new InvalidArgumentException('No adjustment needed - balances match.');
        }

        return DB::transaction(function () use ($debt, $principalPaid, $reconciliationDate, $notes): Payment {
            $payment = Payment::create([
                'debt_id' => $debt->id,
                'planned_amount' => 0,
                'actual_amount' => $principalPaid,
                'interest_paid' => 0,
                'principal_paid' => $principalPaid,
                'payment_date' => $reconciliationDate,
                'month_number' => null,
                'payment_month' => now()->parse($reconciliationDate)->format('Y-m'),
                'notes' => $notes ?? $this->buildDefaultNote($principalPaid),
                'is_reconciliation_adjustment' => true,
            ]);

            $this->paymentService->updateDebtBalances();

            $debt->update(['last_verified_at' => $reconciliationDate]);

            return $payment;
        });
    }

    /**
     * Revise an existing reconciliation adjustment to reflect a new actual balance.
     */
    public function revise(
        Payment $reconciliation,
        float $newActualBalance,
        string $reconciliationDate,
        ?string $notes = null,
    ): Payment {
        $this->assertIsReconciliation($reconciliation);

        /** @var Debt $debt */
        $debt = $reconciliation->debt;

        // The edit modal pre-fills the note field with the stored note, which is
        // usually the auto-generated one. Submitting it unchanged must not pin
        // the note to the old amount, so it is treated as "no note given" and
        // regenerated. Genuinely custom notes are kept verbatim.
        if ($notes !== null && $notes === $this->buildDefaultNote((float) $reconciliation->principal_paid)) {
            $notes = null;
        }

        $principalPaid = $this->calculatePrincipalAdjustment(
            calculatedBalanceExcludingThisAdjustment: $debt->balance + $reconciliation->principal_paid,
            actualBalance: $newActualBalance,
        );

        return DB::transaction(function () use ($reconciliation, $principalPaid, $reconciliationDate, $notes): Payment {
            $reconciliation->update([
                'actual_amount' => $principalPaid,
                'principal_paid' => $principalPaid,
                'interest_paid' => 0,
                'payment_date' => $reconciliationDate,
                'payment_month' => now()->parse($reconciliationDate)->format('Y-m'),
                'notes' => $notes ?? $this->buildDefaultNote($principalPaid),
            ]);

            $this->paymentService->updateDebtBalances();

            return $reconciliation->fresh();
        });
    }

    /**
     * Revoke a reconciliation adjustment by deleting it. The debt's balance
     * is recomputed and reflects what it would be without this adjustment.
     */
    public function revoke(Payment $reconciliation): bool
    {
        $this->assertIsReconciliation($reconciliation);

        DB::transaction(function () use ($reconciliation): void {
            $reconciliation->delete();
            $this->paymentService->updateDebtBalances();
        });

        return true;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function forDebt(Debt $debt): Collection
    {
        return Payment::with('debt')
            ->where('debt_id', $debt->id)
            ->where('is_reconciliation_adjustment', true)
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * @return Collection<int, Payment>
     */
    public function all(): Collection
    {
        return Payment::with('debt')
            ->where('is_reconciliation_adjustment', true)
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Single source of truth for the principal_paid arithmetic.
     *
     * Both apply and revise compute principal_paid as the signed difference
     * between the calculated balance (excluding this adjustment's effect) and
     * the user's reported actual balance. Positive when actual is lower
     * (extra principal paid), negative when actual is higher (fees added).
     */
    private function calculatePrincipalAdjustment(
        float $calculatedBalanceExcludingThisAdjustment,
        float $actualBalance,
    ): float {
        return round($calculatedBalanceExcludingThisAdjustment - $actualBalance, 2);
    }

    private function assertIsReconciliation(Payment $payment): void
    {
        if (! $payment->is_reconciliation_adjustment) {
            throw new InvalidArgumentException('Payment is not a reconciliation adjustment.');
        }
    }

    private function buildDefaultNote(float $principalPaid): string
    {
        $direction = $principalPaid < 0 ? 'Økning' : 'Reduksjon';

        return 'Avstemming: '.$direction.' på '.number_format(abs($principalPaid), 2, ',', ' ').' kr';
    }
}
