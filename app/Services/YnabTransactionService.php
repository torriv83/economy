<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Debt;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class YnabTransactionService
{
    /**
     * Import a YNAB transaction as a payment.
     *
     * @param  array<string, mixed>  $transaction
     */
    public function importTransaction(Debt $debt, array $transaction, PaymentService $paymentService): Payment
    {
        $paymentDate = Carbon::parse($transaction['date']);
        $paymentMonth = $paymentDate->format('Y-m');

        // Calculate month number based on payment date relative to debt creation
        $monthNumber = $debt->created_at->diffInMonths($paymentDate) + 1;

        $payment = null;

        DB::transaction(function () use ($debt, $transaction, $paymentDate, $paymentMonth, $monthNumber, $paymentService, &$payment) {
            $payment = Payment::create([
                'debt_id' => $debt->id,
                'planned_amount' => $transaction['amount'],
                'actual_amount' => $transaction['amount'],
                'payment_date' => $paymentDate->format('Y-m-d'),
                'month_number' => $monthNumber,
                'payment_month' => $paymentMonth,
                'notes' => $transaction['memo'] ?? __('app.imported_from_ynab'),
                'ynab_transaction_id' => $transaction['id'],
                'is_reconciliation_adjustment' => false,
            ]);

            $paymentService->updateDebtBalances();
        });

        return $payment;
    }

    /**
     * Update a local payment with YNAB transaction data.
     */
    public function updatePaymentFromTransaction(Payment $payment, string $ynabTransactionId, float $amount): void
    {
        $payment->update([
            'actual_amount' => $amount,
            'ynab_transaction_id' => $ynabTransactionId,
        ]);
    }

    /**
     * Compare YNAB transactions with local payments for a single debt.
     *
     * @param  Collection<int, array<string, mixed>>  $ynabTransactions
     * @return array<int, array<string, mixed>>
     */
    public function compareTransactionsForDebt(Debt $debt, Collection $ynabTransactions): array
    {
        // Get local payments linked by ynab_transaction_id
        $localPayments = Payment::where('debt_id', $debt->id)
            ->where('is_reconciliation_adjustment', false)
            ->get()
            ->keyBy('ynab_transaction_id');

        // Group unlinked local payments by month for fuzzy matching
        $localPaymentsByMonth = Payment::where('debt_id', $debt->id)
            ->where('is_reconciliation_adjustment', false)
            ->whereNull('ynab_transaction_id')
            ->get()
            ->groupBy(fn ($p) => $p->payment_date->format('Y-m'));

        $comparedTransactions = [];

        foreach ($ynabTransactions as $ynabTx) {
            $transactionMonth = Carbon::parse($ynabTx['date'])->format('Y-m');

            // Check if already linked by ynab_transaction_id
            if ($localPayments->has($ynabTx['id'])) {
                $localPayment = $localPayments->get($ynabTx['id']);
                $status = abs($localPayment->actual_amount - $ynabTx['amount']) < 0.01
                    ? 'matched'
                    : 'mismatch';

                $comparedTransactions[] = [
                    'id' => $ynabTx['id'],
                    'date' => $ynabTx['date'],
                    'amount' => $ynabTx['amount'],
                    'payee_name' => $ynabTx['payee_name'],
                    'memo' => $ynabTx['memo'],
                    'status' => $status,
                    'local_payment_id' => $localPayment->id,
                    'local_amount' => $localPayment->actual_amount,
                ];
            } else {
                // Try fuzzy matching by month and similar amount
                $potentialMatch = $this->findFuzzyMatch($localPaymentsByMonth, $transactionMonth, $ynabTx['amount']);

                if ($potentialMatch) {
                    $status = abs($potentialMatch->actual_amount - $ynabTx['amount']) < 0.01
                        ? 'matched'
                        : 'mismatch';

                    $comparedTransactions[] = [
                        'id' => $ynabTx['id'],
                        'date' => $ynabTx['date'],
                        'amount' => $ynabTx['amount'],
                        'payee_name' => $ynabTx['payee_name'],
                        'memo' => $ynabTx['memo'],
                        'status' => $status,
                        'local_payment_id' => $potentialMatch->id,
                        'local_amount' => $potentialMatch->actual_amount,
                    ];
                } else {
                    // No match found - this is a new transaction
                    $comparedTransactions[] = [
                        'id' => $ynabTx['id'],
                        'date' => $ynabTx['date'],
                        'amount' => $ynabTx['amount'],
                        'payee_name' => $ynabTx['payee_name'],
                        'memo' => $ynabTx['memo'],
                        'status' => 'missing',
                        'local_payment_id' => null,
                        'local_amount' => null,
                    ];
                }
            }
        }

        return $comparedTransactions;
    }

    /**
     * Find a fuzzy match for a YNAB transaction in local payments.
     *
     * @param  Collection<string, Collection<int, Payment>>  $localPaymentsByMonth
     */
    private function findFuzzyMatch(Collection $localPaymentsByMonth, string $transactionMonth, float $amount): ?Payment
    {
        if (! isset($localPaymentsByMonth[$transactionMonth])) {
            return null;
        }

        foreach ($localPaymentsByMonth[$transactionMonth] as $localPayment) {
            // Match if amount is within 5%
            $diff = abs($localPayment->actual_amount - $amount);
            $threshold = $amount * 0.05;
            if ($diff <= $threshold) {
                return $localPayment;
            }
        }

        return null;
    }
}
