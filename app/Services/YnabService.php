<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class YnabService
{
    public function __construct(
        private readonly string $token,
        private readonly string $budgetId
    ) {}

    /**
     * Check if YNAB API is accessible.
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function isAccessible(): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(5)
                ->get('https://api.ynab.com/v1/user');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fetch all debt accounts from YNAB.
     * Returns accounts of type: personalLoan, otherDebt, creditCard.
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function fetchDebtAccounts(): Collection
    {
        $response = Http::withToken($this->token)
            ->get("https://api.ynab.com/v1/budgets/{$this->budgetId}/accounts")
            ->throw()
            ->json();

        $accounts = collect($response['data']['accounts'] ?? []);

        return $accounts->filter(function ($account) {
            return in_array($account['type'], ['personalLoan', 'otherDebt', 'creditCard'])
                && ! $account['deleted'];
        })->map(function ($account) {
            return $this->mapYnabAccount($account);
        });
    }

    /**
     * Map YNAB account data to our app's debt structure.
     */
    protected function mapYnabAccount(array $account): array
    {
        // YNAB stores amounts in milliunits (divide by 1000 to get NOK)
        // Negative balance = debt owed
        $balance = abs($account['balance'] / 1000);

        // Get the most recent interest rate
        $interestRate = $this->getLatestInterestRate($account['debt_interest_rates'] ?? []);

        // Get the most recent minimum payment
        $minimumPayment = $this->getLatestMinimumPayment($account['debt_minimum_payments'] ?? []);

        return [
            'ynab_id' => $account['id'],
            'name' => $account['name'],
            'balance' => $balance,
            'interest_rate' => $interestRate,
            'minimum_payment' => $minimumPayment,
            'type' => $account['type'],
            'closed' => $account['closed'],
        ];
    }

    /**
     * Extract the latest interest rate from YNAB's historical data.
     * YNAB format: {"2023-02-01": 11180, "2023-09-01": 15300}
     */
    protected function getLatestInterestRate(array $rates): float
    {
        if (empty($rates)) {
            return 0.0;
        }

        // Get the most recent date
        $latestDate = max(array_keys($rates));
        $rateInMilliunits = $rates[$latestDate];

        // Convert milliunits to percentage (15300 = 15.3%)
        return $rateInMilliunits / 1000;
    }

    /**
     * Extract the latest minimum payment from YNAB's historical data.
     * YNAB format: {"2023-02-01": 590000, "2023-03-01": 592650}
     */
    protected function getLatestMinimumPayment(array $payments): ?float
    {
        if (empty($payments)) {
            return null;
        }

        // Get the most recent date
        $latestDate = max(array_keys($payments));
        $paymentInMilliunits = $payments[$latestDate];

        // Convert milliunits to NOK (590000 = 590 kr)
        return $paymentInMilliunits / 1000;
    }
}
