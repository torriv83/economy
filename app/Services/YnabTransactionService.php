<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Debt;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class YnabTransactionService
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Import a YNAB transaction as a payment.
     *
     * @param  array<string, mixed>  $transaction
     */
    public function importTransaction(Debt $debt, array $transaction): Payment
    {
        $this->validateTransactionData($transaction);

        $paymentDate = Carbon::parse($transaction['date']);
        $paymentMonth = $paymentDate->format('Y-m');

        // Calculate month number based on payment date relative to debt creation
        // Use startOfMonth to compare months properly, ensuring integer result
        // Ensure month number is at least 1 (handle edge cases with timezone/data errors)
        $monthNumber = max(1, (int) $debt->created_at->startOfMonth()->diffInMonths($paymentDate->startOfMonth()) + 1);

        $payment = null;

        DB::transaction(function () use ($debt, $transaction, $paymentDate, $paymentMonth, $monthNumber, &$payment) {
            // Calculate interest/principal breakdown based on historical balance
            $balanceAtPaymentTime = $this->getHistoricalBalance($debt, $paymentDate);
            $monthlyInterest = round($balanceAtPaymentTime * ($debt->interest_rate / 100) / 12, 2);
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

            $this->paymentService->updateDebtBalances();
        });

        return $payment;
    }

    /**
     * Update a local payment with YNAB transaction data.
     */
    public function updatePaymentFromTransaction(Payment $payment, string $ynabTransactionId, float $amount, ?string $date = null): void
    {
        $this->validateAmount($amount);

        if ($date !== null) {
            $this->validateDateFormat($date);
        }

        // Hent historisk balanse på betalingstidspunktet
        $debt = $payment->debt;
        $paymentDate = $date !== null ? Carbon::parse($date) : $payment->payment_date;

        // Beregn månedlig rente basert på historisk balanse (ekskluder denne betalingen)
        $monthlyInterestRate = ($debt->interest_rate / 100) / 12;
        $balanceAtPaymentTime = $this->getHistoricalBalance($debt, $paymentDate, $payment);
        $monthlyInterest = round($balanceAtPaymentTime * $monthlyInterestRate, 2);

        // Fordel betaling: Rente først, deretter hovedstol
        $interestPaid = min($amount, $monthlyInterest);
        $principalPaid = max(0, $amount - $monthlyInterest);

        $data = [
            'actual_amount' => $amount,
            'interest_paid' => $interestPaid,
            'principal_paid' => $principalPaid,
            'ynab_transaction_id' => $ynabTransactionId,
        ];

        if ($date !== null) {
            $data['payment_date'] = Carbon::parse($date);
        }

        $payment->update($data);

        // Oppdater gjeldssaldo
        $this->paymentService->updateDebtBalances();

        // Temporary cache clear (til Bug #509 er fikset)
        $this->clearTemporaryCaches();
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
        $this->validateAmount($amount);
        $this->validateDateFormat($date);

        $paymentDate = Carbon::parse($date);

        // Hent historisk balanse på betalingstidspunktet
        $debt = $payment->debt;

        // Beregn månedlig rente basert på historisk balanse (ekskluder denne betalingen)
        $monthlyInterestRate = ($debt->interest_rate / 100) / 12;
        $balanceAtPaymentTime = $this->getHistoricalBalance($debt, $paymentDate, $payment);
        $monthlyInterest = round($balanceAtPaymentTime * $monthlyInterestRate, 2);

        // Fordel betaling: Rente først, deretter hovedstol
        $interestPaid = min($amount, $monthlyInterest);
        $principalPaid = max(0, $amount - $monthlyInterest);

        $payment->update([
            'actual_amount' => $amount,
            'interest_paid' => $interestPaid,
            'principal_paid' => $principalPaid,
            'payment_date' => $paymentDate,
            'ynab_transaction_id' => $ynabTransactionId,
        ]);

        // Oppdater gjeldssaldo
        $this->paymentService->updateDebtBalances();

        // Temporary cache clear (til Bug #509 er fikset)
        $this->clearTemporaryCaches();
    }

    /**
     * Calculate the historical balance for a debt at a given payment date.
     *
     * This method calculates what the debt balance was at the time of the payment
     * by starting with the original balance and subtracting all principal payments
     * made before this payment date.
     *
     * @param  Payment|null  $excludePayment  Optional payment to exclude from calculation (used when updating existing payments)
     */
    private function getHistoricalBalance(Debt $debt, Carbon $paymentDate, ?Payment $excludePayment = null): float
    {
        // Beregn alle betalinger (hovedstol) som skjedde FØR denne datoen
        $query = Payment::where('debt_id', $debt->id)
            ->where('payment_date', '<', $paymentDate);

        // Ekskluder betalingen vi oppdaterer (hvis angitt)
        if ($excludePayment !== null) {
            $query->where('id', '!=', $excludePayment->id);
        }

        $previousPrincipal = $query->sum('principal_paid');

        // Hvis original_balance er satt og virker fornuftig, bruk den
        if ($debt->original_balance !== null) {
            return max(0, $debt->original_balance - $previousPrincipal);
        }

        // Ellers beregn original balance fra current balance + all principal paid
        $allPrincipalPaid = Payment::where('debt_id', $debt->id)
            ->when($excludePayment, fn ($q) => $q->where('id', '!=', $excludePayment->id))
            ->sum('principal_paid');

        $originalBalance = $debt->balance + $allPrincipalPaid;

        return max(0, $originalBalance - $previousPrincipal);
    }

    /**
     * Validate transaction data from YNAB.
     *
     * @param  array<string, mixed>  $transaction
     */
    private function validateTransactionData(array $transaction): void
    {
        if (! isset($transaction['date']) || ! is_string($transaction['date'])) {
            throw new InvalidArgumentException('Transaction date is required and must be a string');
        }

        $this->validateDateFormat($transaction['date']);

        if (! isset($transaction['amount']) || ! is_numeric($transaction['amount'])) {
            throw new InvalidArgumentException('Transaction amount is required and must be numeric');
        }

        $this->validateAmount((float) $transaction['amount']);
    }

    /**
     * Validate date format.
     */
    private function validateDateFormat(string $date): void
    {
        try {
            Carbon::parse($date);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid date format: {$date}");
        }
    }

    /**
     * Validate amount is not negative.
     */
    private function validateAmount(float $amount): void
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }

    /**
     * Clear temporary caches (until Bug #509 is fixed).
     */
    private function clearTemporaryCaches(): void
    {
        DebtCacheService::clearCache();
        ProgressCacheService::clearCache();
        DebtCalculationService::clearAllCalculationCaches();
    }
}
