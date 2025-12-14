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
        // Use startOfMonth to compare months properly, ensuring integer result
        $monthNumber = (int) $debt->created_at->startOfMonth()->diffInMonths($paymentDate->startOfMonth()) + 1;

        $payment = null;

        DB::transaction(function () use ($debt, $transaction, $paymentDate, $paymentMonth, $monthNumber, $paymentService, &$payment) {
            // Calculate interest/principal breakdown based on current balance
            $currentBalance = $debt->balance;
            $monthlyInterest = round($currentBalance * ($debt->interest_rate / 100) / 12, 2);
            $actualAmount = $transaction['amount'];

            // Payment goes to interest first, then principal
            $interestPaid = min($actualAmount, $monthlyInterest);
            $principalPaid = max(0, $actualAmount - $monthlyInterest);

            $payment = Payment::create([
                'debt_id' => $debt->id,
                'planned_amount' => $transaction['amount'],
                'actual_amount' => $actualAmount,
                'interest_paid' => $interestPaid,
                'principal_paid' => $principalPaid,
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
    public function updatePaymentFromTransaction(Payment $payment, string $ynabTransactionId, float $amount, ?string $date = null): void
    {
        $data = [
            'actual_amount' => $amount,
            'ynab_transaction_id' => $ynabTransactionId,
        ];

        if ($date !== null) {
            $data['payment_date'] = Carbon::parse($date);
        }

        $payment->update($data);
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
                $amountMatches = abs($localPayment->actual_amount - $ynabTx['amount']) < 0.01;
                $dateMatches = $localPayment->payment_date->format('Y-m-d') === $ynabTx['date'];
                $status = ($amountMatches && $dateMatches) ? 'matched' : 'mismatch';

                $comparedTransactions[] = [
                    'id' => $ynabTx['id'],
                    'date' => $ynabTx['date'],
                    'amount' => $ynabTx['amount'],
                    'payee_name' => $ynabTx['payee_name'],
                    'memo' => $ynabTx['memo'],
                    'status' => $status,
                    'local_payment_id' => $localPayment->id,
                    'local_amount' => $localPayment->actual_amount,
                    'local_date' => $localPayment->payment_date->format('Y-m-d'),
                ];
            } else {
                // Try fuzzy matching by month and similar amount
                $potentialMatch = $this->findFuzzyMatch($localPaymentsByMonth, $transactionMonth, $ynabTx['amount']);

                if ($potentialMatch) {
                    $amountMatches = abs($potentialMatch->actual_amount - $ynabTx['amount']) < 0.01;
                    $dateMatches = $potentialMatch->payment_date->format('Y-m-d') === $ynabTx['date'];
                    $status = ($amountMatches && $dateMatches) ? 'matched' : 'mismatch';

                    $comparedTransactions[] = [
                        'id' => $ynabTx['id'],
                        'date' => $ynabTx['date'],
                        'amount' => $ynabTx['amount'],
                        'payee_name' => $ynabTx['payee_name'],
                        'memo' => $ynabTx['memo'],
                        'status' => $status,
                        'local_payment_id' => $potentialMatch->id,
                        'local_amount' => $potentialMatch->actual_amount,
                        'local_date' => $potentialMatch->payment_date->format('Y-m-d'),
                    ];
                } else {
                    // Check if there's any payment in the same month (different amount)
                    $monthPayment = $this->findPaymentInMonth($localPaymentsByMonth, $transactionMonth);

                    if ($monthPayment) {
                        // Payment exists for this month but with different amount - offer to link
                        $comparedTransactions[] = [
                            'id' => $ynabTx['id'],
                            'date' => $ynabTx['date'],
                            'amount' => $ynabTx['amount'],
                            'payee_name' => $ynabTx['payee_name'],
                            'memo' => $ynabTx['memo'],
                            'status' => 'linkable',
                            'local_payment_id' => $monthPayment->id,
                            'local_amount' => $monthPayment->actual_amount,
                            'local_date' => $monthPayment->payment_date->format('Y-m-d'),
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
        }

        return $comparedTransactions;
    }

    /**
     * Find a fuzzy match for a YNAB transaction in local payments.
     *
     * @param  Collection<string, \Illuminate\Database\Eloquent\Collection<int, Payment>>  $localPaymentsByMonth
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

    /**
     * Find any payment in the given month (regardless of amount).
     *
     * @param  Collection<string, \Illuminate\Database\Eloquent\Collection<int, Payment>>  $localPaymentsByMonth
     */
    private function findPaymentInMonth(Collection $localPaymentsByMonth, string $transactionMonth): ?Payment
    {
        if (! isset($localPaymentsByMonth[$transactionMonth])) {
            return null;
        }

        return $localPaymentsByMonth[$transactionMonth]->first();
    }

    /**
     * Link a YNAB transaction to an existing local payment and update it.
     */
    public function linkTransactionToPayment(Payment $payment, string $ynabTransactionId, float $amount, string $date): void
    {
        $paymentDate = Carbon::parse($date);

        $payment->update([
            'actual_amount' => $amount,
            'payment_date' => $paymentDate,
            'ynab_transaction_id' => $ynabTransactionId,
        ]);
    }
}
