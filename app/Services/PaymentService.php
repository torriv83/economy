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
        return Payment::create([
            'debt_id' => $debt->id,
            'planned_amount' => $plannedAmount,
            'actual_amount' => $actualAmount,
            'payment_date' => now(),
            'month_number' => $monthNumber,
            'payment_month' => $paymentMonth,
        ]);
    }

    /**
     * Record multiple payments for a month
     *
     * @param  array  $payments  Array of ['debt_id' => int, 'planned_amount' => float, 'actual_amount' => float]
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
     */
    public function updateDebtBalances(): void
    {
        $debts = Debt::with('payments')->get();

        foreach ($debts as $debt) {
            $totalPaid = $debt->payments->sum('actual_amount');
            $newBalance = max(0, $debt->original_balance - $totalPaid);

            $debt->update(['balance' => $newBalance]);
        }
    }

    /**
     * Calculate overall progress percentage
     */
    public function calculateOverallProgress(): float
    {
        $totalOriginal = Debt::sum('original_balance');

        if ($totalOriginal == 0) {
            return 0.0;
        }

        $totalCurrent = Debt::sum('balance');
        $totalPaid = $totalOriginal - $totalCurrent;

        return ($totalPaid / $totalOriginal) * 100;
    }

    /**
     * Get all payments for a specific month
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
            $payment->update(['actual_amount' => $newAmount]);
            $this->updateDebtBalances();
        });

        return true;
    }
}
