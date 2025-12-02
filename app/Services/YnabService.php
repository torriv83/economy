<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class YnabService
{
    public function __construct(
        private readonly string $token,
        private readonly string $budgetId
    ) {}

    /**
     * Get the cache TTL in seconds.
     * Uses the user's background sync interval setting so cached data
     * remains valid until the next sync. Falls back to 30 minutes.
     */
    private function getCacheTtl(): int
    {
        $settingsService = app(SettingsService::class);
        $intervalMinutes = $settingsService->getYnabBackgroundSyncInterval();

        return $intervalMinutes * 60;
    }

    /**
     * Clear all cached YNAB data for this budget.
     */
    public function clearCache(): void
    {
        Cache::forget("ynab:budget_summary:{$this->budgetId}");
        Cache::forget("ynab:categories:{$this->budgetId}");
        Cache::forget("ynab:debt_accounts:{$this->budgetId}");
    }

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
     * Results are cached based on user's sync interval setting.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function fetchDebtAccounts(): Collection
    {
        $cacheKey = "ynab:debt_accounts:{$this->budgetId}";

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
            $response = Http::withToken($this->token)
                ->get("https://api.ynab.com/v1/budgets/{$this->budgetId}/accounts")
                ->throw()
                ->json();

            /** @var array<int, array<string, mixed>> $accountsData */
            $accountsData = $response['data']['accounts'] ?? [];

            /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $accounts */
            $accounts = collect($accountsData);

            return $accounts->filter(function ($account) {
                return in_array($account['type'], ['personalLoan', 'otherDebt', 'creditCard'])
                    && ! $account['deleted'];
            })->map(function ($account) {
                return $this->mapYnabAccount($account);
            });
        });
    }

    /**
     * Map YNAB account data to our app's debt structure.
     *
     * @param  array<string, mixed>  $account
     * @return array<string, mixed>
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
     *
     * @param  array<string, int>  $rates
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
     *
     * @param  array<string, int>  $payments
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

    /**
     * Fetch payment transactions for a specific debt account from YNAB.
     * Only returns transactions that reduce the debt (positive amounts in YNAB).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function fetchPaymentTransactions(string $accountId, ?\DateTimeInterface $sinceDate = null): Collection
    {
        $url = "https://api.ynab.com/v1/budgets/{$this->budgetId}/accounts/{$accountId}/transactions";

        $query = [];
        if ($sinceDate !== null) {
            $query['since_date'] = $sinceDate->format('Y-m-d');
        }

        $response = Http::withToken($this->token)
            ->get($url, $query)
            ->throw()
            ->json();

        /** @var array<int, array<string, mixed>> $transactionsData */
        $transactionsData = $response['data']['transactions'] ?? [];

        /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $transactions */
        $transactions = collect($transactionsData);

        // Filter to only include payments (positive amounts reduce debt in YNAB)
        // and exclude deleted transactions
        /** @var \Illuminate\Support\Collection<int, array<string, mixed>> */
        return $transactions
            ->filter(function ($transaction) {
                return $transaction['amount'] > 0 && ! $transaction['deleted'];
            })
            ->map(function ($transaction) {
                return $this->mapYnabTransaction($transaction);
            })
            ->values();
    }

    /**
     * Map YNAB transaction data to our app's structure.
     *
     * @param  array<string, mixed>  $transaction
     * @return array{id: string, date: string, amount: float, payee_name: string|null, memo: string|null}
     */
    protected function mapYnabTransaction(array $transaction): array
    {
        // YNAB stores amounts in milliunits (divide by 1000 to get NOK)
        $amount = $transaction['amount'] / 1000;

        return [
            'id' => $transaction['id'],
            'date' => $transaction['date'],
            'amount' => $amount,
            'payee_name' => $transaction['payee_name'] ?? null,
            'memo' => $transaction['memo'] ?? null,
        ];
    }

    /**
     * Fetch budget summary including Ready to Assign amount.
     * Results are cached based on user's sync interval setting.
     *
     * @return array{ready_to_assign: float, currency_format: array<string, mixed>|null}
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function fetchBudgetSummary(): array
    {
        $cacheKey = "ynab:budget_summary:{$this->budgetId}";

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
            // Use the current month endpoint for accurate "Ready to Assign" value
            $currentMonth = date('Y-m').'-01';

            $monthResponse = Http::withToken($this->token)
                ->get("https://api.ynab.com/v1/budgets/{$this->budgetId}/months/{$currentMonth}")
                ->throw()
                ->json();

            $month = $monthResponse['data']['month'] ?? [];

            // Fetch budget for currency format
            $budgetResponse = Http::withToken($this->token)
                ->get("https://api.ynab.com/v1/budgets/{$this->budgetId}/settings")
                ->throw()
                ->json();

            $settings = $budgetResponse['data']['settings'] ?? [];

            // to_be_budgeted is in milliunits
            $readyToAssign = (($month['to_be_budgeted'] ?? 0)) / 1000;

            return [
                'ready_to_assign' => $readyToAssign,
                'currency_format' => $settings['currency_format'] ?? null,
            ];
        });
    }

    /**
     * Fetch all categories from YNAB with balances and goal info.
     * Results are cached based on user's sync interval setting.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function fetchCategories(): Collection
    {
        $cacheKey = "ynab:categories:{$this->budgetId}";

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
            $response = Http::withToken($this->token)
                ->get("https://api.ynab.com/v1/budgets/{$this->budgetId}/categories")
                ->throw()
                ->json();

            /** @var array<int, array<string, mixed>> $categoryGroups */
            $categoryGroups = $response['data']['category_groups'] ?? [];

            $categories = collect();

            foreach ($categoryGroups as $group) {
                // Skip internal YNAB groups
                if ($group['hidden'] || in_array($group['name'], ['Internal Master Category', 'Credit Card Payments'])) {
                    continue;
                }

                foreach ($group['categories'] ?? [] as $category) {
                    if ($category['hidden'] || $category['deleted']) {
                        continue;
                    }

                    $categories->push($this->mapYnabCategory($category, $group['name']));
                }
            }

            return $categories;
        });
    }

    /**
     * Map YNAB category data to our app's structure.
     *
     * @param  array<string, mixed>  $category
     * @return array<string, mixed>
     */
    protected function mapYnabCategory(array $category, string $groupName): array
    {
        // All amounts in milliunits - convert to NOK
        $balance = ($category['balance'] ?? 0) / 1000;
        $budgeted = ($category['budgeted'] ?? 0) / 1000;
        $activity = ($category['activity'] ?? 0) / 1000;
        $goalTarget = ($category['goal_target'] ?? 0) / 1000;
        $goalUnderFunded = ($category['goal_under_funded'] ?? 0) / 1000;

        return [
            'id' => $category['id'],
            'name' => $category['name'],
            'group_name' => $groupName,
            'balance' => $balance,
            'budgeted' => $budgeted,
            'activity' => $activity,
            'goal_type' => $category['goal_type'] ?? null,
            'goal_target' => $goalTarget,
            'goal_under_funded' => $goalUnderFunded,
            'goal_percentage_complete' => $category['goal_percentage_complete'] ?? null,
            'is_overfunded' => $balance > 0 && $goalTarget > 0 && $balance > $goalTarget,
            'has_goal' => ($category['goal_type'] ?? null) !== null,
        ];
    }
}
