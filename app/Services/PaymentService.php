<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Record a single payment for a debt
     */
    public function recordPayment(
        Debt $debt,
        float $plannedAmount,
        float $actualAmount,
        int $monthNumber,
        string $paymentMonth
    ): Payment {
        // Calculate interest on current balance before payment
        $currentBalance = $debt->balance;
        $monthlyInterest = round($currentBalance * ($debt->interest_rate / 100) / 12, 2);

        // Payment goes to interest first, then principal
        $interestPaid = min($actualAmount, $monthlyInterest);
        $principalPaid = max(0, $actualAmount - $monthlyInterest);

        return Payment::create([
            'debt_id' => $debt->id,
            'planned_amount' => $plannedAmount,
            'actual_amount' => $actualAmount,
            'interest_paid' => $interestPaid,
            'principal_paid' => $principalPaid,
            'payment_date' => now(),
            'month_number' => $monthNumber,
            'payment_month' => $paymentMonth,
        ]);
    }

    /**
     * Record multiple payments for a month
     *
     * @param  array<int, array<string, mixed>>  $payments  Array of ['debt_id' => int, 'planned_amount' => float, 'actual_amount' => float]
     * @return \Illuminate\Support\Collection<int, \App\Models\Payment>
     */
    public function recordMonthPayments(array $payments, string $paymentMonth, int $monthNumber): Collection
    {
        $recordedPayments = collect();

        DB::transaction(function () use ($payments, $paymentMonth, $monthNumber, &$recordedPayments) {
            foreach ($payments as $paymentData) {
                $debt = Debt::findOrFail($paymentData['debt_id']);

                $payment = $this->recordPayment(
                    $debt,
                    $paymentData['planned_amount'],
                    $paymentData['actual_amount'],
                    $monthNumber,
                    $paymentMonth
                );

                $recordedPayments->push($payment);
            }

            $this->updateDebtBalances();
        });

        return $recordedPayments;
    }

    /**
     * Update all debt balances based on recorded payments
     * Uses Eloquent to trigger observers for proper cache invalidation
     */
    public function updateDebtBalances(): void
    {
        $debts = Debt::all();

        foreach ($debts as $debt) {
            // Hent sum av principal_paid for denne gjelden
            $totalPrincipalPaid = Payment::where('debt_id', $debt->id)
                ->sum('principal_paid');

            // Beregn ny balanse
            $newBalance = max(0, $debt->original_balance - $totalPrincipalPaid);

            // Oppdater via Eloquent (triggerer observer)
            $debt->update(['balance' => $newBalance]);
        }
    }

    /**
     * Calculate overall progress percentage
     *
     * For debts where balance > original (e.g., credit cards with increased usage),
     * we cap the individual progress at 0% so they don't drag down the total.
     */
    public function calculateOverallProgress(): float
    {
        $debts = Debt::all();

        if ($debts->isEmpty()) {
            return 0.0;
        }

        $totalOriginal = 0.0;
        $totalPaidOff = 0.0;

        foreach ($debts as $debt) {
            $totalOriginal += $debt->original_balance;
            // Cap individual debt progress at 0 (don't count negative progress)
            $paidOff = max(0, $debt->original_balance - $debt->balance);
            $totalPaidOff += $paidOff;
        }

        if (abs($totalOriginal) < 0.01) {
            return 0.0;
        }

        return ($totalPaidOff / $totalOriginal) * 100;
    }

    /**
     * Get all payments for a specific month
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Payment>
     */
    public function getPaymentsForMonth(string $paymentMonth): Collection
    {
        return Payment::with('debt')
            ->where('payment_month', $paymentMonth)
            ->orderBy('month_number')
            ->orderBy('debt_id')
            ->get();
    }

    /**
     * Check if a payment already exists for a debt in a specific month
     */
    public function paymentExists(int $debtId, int $monthNumber): bool
    {
        return Payment::where('debt_id', $debtId)
            ->where('month_number', $monthNumber)
            ->exists();
    }

    /**
     * Get payment for a specific debt and month
     */
    public function getPayment(int $debtId, int $monthNumber): ?Payment
    {
        return Payment::where('debt_id', $debtId)
            ->where('month_number', $monthNumber)
            ->first();
    }

    /**
     * Delete a payment and update debt balances
     */
    public function deletePayment(int $debtId, int $monthNumber): bool
    {
        $payment = $this->getPayment($debtId, $monthNumber);

        if (! $payment) {
            return false;
        }

        DB::transaction(function () use ($payment) {
            $payment->delete();
            $this->updateDebtBalances();
        });

        return true;
    }

    /**
     * Delete all payments for a specific month
     */
    public function deleteMonthPayments(int $monthNumber): int
    {
        $deletedCount = 0;

        DB::transaction(function () use ($monthNumber, &$deletedCount) {
            $deletedCount = Payment::where('month_number', $monthNumber)->delete();
            $this->updateDebtBalances();
        });

        return $deletedCount;
    }

    /**
     * Check if all payments for a month are paid
     *
     * @param  array<int, int>  $expectedDebts
     */
    public function isMonthFullyPaid(int $monthNumber, array $expectedDebts): bool
    {
        if (empty($expectedDebts)) {
            return false;
        }

        foreach ($expectedDebts as $debtId) {
            if (! $this->paymentExists($debtId, $monthNumber)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update the actual amount of an existing payment
     */
    public function updatePaymentAmount(int $debtId, int $monthNumber, float $newAmount): bool
    {
        $payment = $this->getPayment($debtId, $monthNumber);

        if (! $payment) {
            return false;
        }

        DB::transaction(function () use ($payment, $newAmount) {
            /** @var \App\Models\Debt $debt */
            $debt = $payment->debt;

            // Recalculate interest and principal based on debt's current balance
            $monthlyInterest = round($debt->balance * ($debt->interest_rate / 100) / 12, 2);
            $interestPaid = min($newAmount, $monthlyInterest);
            $principalPaid = max(0, $newAmount - $monthlyInterest);

            $payment->update([
                'actual_amount' => $newAmount,
                'interest_paid' => $interestPaid,
                'principal_paid' => $principalPaid,
            ]);

            $this->updateDebtBalances();
        });

        return true;
    }

    /**
     * Update the note of an existing payment
     */
    public function updatePaymentNote(int $debtId, int $monthNumber, string $note): bool
    {
        $payment = $this->getPayment($debtId, $monthNumber);

        if (! $payment) {
            return false;
        }

        $payment->update(['notes' => $note === '' ? null : $note]);

        return true;
    }

    /**
     * Get all historical payments grouped by month
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHistoricalPayments(): array
    {
        $currentMonth = now()->format('Y-m');

        // Get ALL payments (including reconciliations) to calculate running balances
        $allPayments = Payment::with('debt')
            ->where('payment_month', '<', $currentMonth)
            ->orderBy('payment_month')
            ->orderBy('debt_id')
            ->orderBy('created_at')
            ->get();

        // Track cumulative principal paid per debt to calculate remaining balance
        $cumulativePrincipal = [];
        $endOfMonthBalances = []; // [payment_month][debt_id] => remaining balance

        foreach ($allPayments as $payment) {
            $debtId = $payment->debt_id;
            $paymentMonth = $payment->payment_month;

            if (! isset($cumulativePrincipal[$debtId])) {
                $cumulativePrincipal[$debtId] = 0;
            }

            $cumulativePrincipal[$debtId] += $payment->principal_paid;

            // Store the end-of-month balance for this debt (last payment wins)
            $remaining = max(0, $payment->debt->original_balance - $cumulativePrincipal[$debtId]);

            if (! isset($endOfMonthBalances[$paymentMonth])) {
                $endOfMonthBalances[$paymentMonth] = [];
            }
            $endOfMonthBalances[$paymentMonth][$debtId] = round($remaining, 2);
        }

        // Now get only non-reconciliation payments for display
        $displayPayments = $allPayments->filter(function ($payment) {
            return ! $payment->is_reconciliation_adjustment;
        });

        $groupedPayments = [];
        $monthMapping = [];

        foreach ($displayPayments as $payment) {
            /** @var \App\Models\Debt $debt */
            $debt = $payment->debt;
            $paymentMonth = $payment->payment_month;

            if (! isset($monthMapping[$paymentMonth])) {
                $monthNumber = collect($monthMapping)->count() + 1;
                $monthMapping[$paymentMonth] = $monthNumber;
            }

            $monthNumber = $monthMapping[$paymentMonth];

            if (! isset($groupedPayments[$paymentMonth])) {
                $groupedPayments[$paymentMonth] = [
                    'month' => $monthNumber,
                    'date' => $paymentMonth.'-01',
                    'isHistorical' => true,
                    'payments' => [],
                ];
            }

            // Use end-of-month balance instead of balance after this specific payment
            $remaining = $endOfMonthBalances[$paymentMonth][$debt->id] ?? 0;

            $groupedPayments[$paymentMonth]['payments'][] = [
                'name' => $debt->name,
                'amount' => $payment->actual_amount,
                'remaining' => $remaining,
                'isPriority' => false,
            ];
        }

        return array_values($groupedPayments);
    }
}
