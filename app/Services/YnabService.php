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
     * Check if YNAB is properly configured with token and budget ID.
     */
    public function isConfigured(): bool
    {
        return $this->token !== '' && $this->budgetId !== '';
    }

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
     * Check if YNAB data has changed since last sync using server_knowledge.
     * Returns true if data has changed, false if unchanged.
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function hasDataChanged(): bool
    {
        $settingsService = app(SettingsService::class);
        $lastKnowledge = $settingsService->getYnabServerKnowledge();

        // Fetch budget with delta parameter if we have previous knowledge
        $url = "https://api.ynab.com/v1/budgets/{$this->budgetId}";
        if ($lastKnowledge !== null) {
            $url .= "?last_knowledge_of_server={$lastKnowledge}";
        }

        $response = Http::withToken($this->token)
            ->get($url)
            ->throw()
            ->json();

        $newKnowledge = $response['data']['server_knowledge'] ?? null;

        if ($newKnowledge === null) {
            return true; // Can't determine, assume changed
        }

        // If this is first sync or knowledge has changed
        if ($lastKnowledge === null || $newKnowledge !== $lastKnowledge) {
            $settingsService->setYnabServerKnowledge($newKnowledge);

            return true;
        }

        return false;
    }

    /**
     * Clear all cached YNAB data for this budget.
     */
    public function clearCache(): void
    {
        Cache::forget("ynab:budget_summary:{$this->budgetId}");
        Cache::forget("ynab:categories:{$this->budgetId}");
        Cache::forget("ynab:debt_accounts:{$this->budgetId}");
        Cache::forget("ynab:savings_accounts:{$this->budgetId}");
        Cache::forget("ynab:assigned_next_month:{$this->budgetId}");

        // Clear month-specific category caches
        $currentMonth = date('Y-m-01');
        $nextMonth = date('Y-m-01', strtotime('first day of next month'));
        Cache::forget("ynab:categories_month:{$this->budgetId}:{$currentMonth}");
        Cache::forget("ynab:categories_month:{$this->budgetId}:{$nextMonth}");
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
        // and exclude deleted transactions and YNAB system adjustments
        /** @var \Illuminate\Support\Collection<int, array<string, mixed>> */
        return $transactions
            ->filter(function ($transaction) {
                if ($transaction['amount'] <= 0 || $transaction['deleted']) {
                    return false;
                }

                // Exclude YNAB balance adjustments and starting balances
                $payeeName = $transaction['payee_name'] ?? '';
                $excludePatterns = ['Balance Adjustment', 'Starting Balance', 'Reconciliation'];

                foreach ($excludePatterns as $pattern) {
                    if (stripos($payeeName, $pattern) !== false) {
                        return false;
                    }
                }

                return true;
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

        // goal_cadence: 1 = monthly, 13 = yearly
        $goalCadence = $category['goal_cadence'] ?? null;

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
            'goal_day' => $category['goal_day'] ?? null,
            'goal_cadence' => $goalCadence,
            'is_overfunded' => $balance > 0 && $goalTarget > 0 && $balance > $goalTarget,
            'has_goal' => ($category['goal_type'] ?? null) !== null,
        ];
    }

    /**
     * Fetch all savings accounts from YNAB.
     * Results are cached based on user's sync interval setting.
     *
     * @return Collection<int, array{id: string, name: string, balance: float}>
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function fetchSavingsAccounts(): Collection
    {
        $cacheKey = "ynab:savings_accounts:{$this->budgetId}";

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
            $response = Http::withToken($this->token)
                ->get("https://api.ynab.com/v1/budgets/{$this->budgetId}/accounts")
                ->throw()
                ->json();

            /** @var array<int, array<string, mixed>> $accountsData */
            $accountsData = $response['data']['accounts'] ?? [];

            /** @var Collection<int, array<string, mixed>> $accounts */
            $accounts = collect($accountsData);

            return $accounts
                ->filter(function ($account) {
                    return $account['type'] === 'savings' && ! $account['deleted'];
                })
                ->map(function ($account) {
                    return [
                        'id' => $account['id'],
                        'name' => $account['name'],
                        'balance' => $account['balance'] / 1000,
                    ];
                })
                ->values();
        });
    }

    /**
     * Fetch total amount assigned to next month's budget.
     * Results are cached based on user's sync interval setting.
     *
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function fetchAssignedNextMonth(): float
    {
        $cacheKey = "ynab:assigned_next_month:{$this->budgetId}";

        return (float) Cache::remember($cacheKey, $this->getCacheTtl(), function (): float {
            $nextMonth = date('Y-m-01', strtotime('first day of next month'));

            $response = Http::withToken($this->token)
                ->get("https://api.ynab.com/v1/budgets/{$this->budgetId}/months/{$nextMonth}")
                ->throw()
                ->json();

            $month = $response['data']['month'] ?? [];

            // budgeted is in milliunits - convert to NOK
            return (float) (($month['budgeted'] ?? 0) / 1000);
        });
    }

    /**
     * Fetch categories with goal_type === 'NEED'.
     *
     * @return Collection<int, array{id: string, name: string, budgeted: float, goal_day: int|null}>
     */
    public function fetchNeedCategories(): Collection
    {
        $categories = $this->fetchCategories();

        return $categories
            ->filter(function ($category) {
                return ($category['goal_type'] ?? null) === 'NEED';
            })
            ->map(function ($category): array {
                $goalDay = $category['goal_day'] ?? null;

                return [
                    'id' => (string) $category['id'],
                    'name' => (string) $category['name'],
                    'budgeted' => (float) $category['budgeted'],
                    'goal_day' => $goalDay !== null ? (int) $goalDay : null,
                ];
            })
            ->values();
    }

    /**
     * Fetch expenses for the pay period (20th of month to 19th of next month).
     * Uses goal_day to determine if categories fall within the pay period.
     *
     * @param  int  $payDay  Day of the month when user gets paid (default 20)
     * @return float Sum of budgeted amounts for categories in the pay period
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function fetchPayPeriodExpenses(int $payDay = 20): float
    {
        $needCategories = $this->fetchNeedCategories();

        $payPeriodTotal = 0.0;

        foreach ($needCategories as $category) {
            $goalDay = $category['goal_day'] ?? null;

            if ($goalDay !== null) {
                // Check if goal_day falls in pay period (payDay-31 or 1 to payDay-1 of next month)
                if ($goalDay >= $payDay || $goalDay <= $payDay - 1) {
                    $payPeriodTotal += $category['budgeted'];
                }
            } else {
                // If no goal_day, include the budgeted amount as fallback
                $payPeriodTotal += $category['budgeted'];
            }
        }

        return $payPeriodTotal;
    }

    /**
     * Fetch categories for a specific month from YNAB.
     *
     * @param  string  $month  Month in format 'YYYY-MM-01'
     * @return Collection<int, array<string, mixed>>
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function fetchCategoriesForMonth(string $month): Collection
    {
        $cacheKey = "ynab:categories_month:{$this->budgetId}:{$month}";

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($month) {
            $response = Http::withToken($this->token)
                ->get("https://api.ynab.com/v1/budgets/{$this->budgetId}/months/{$month}")
                ->throw()
                ->json();

            $monthData = $response['data']['month'] ?? [];
            $categories = collect();

            foreach ($monthData['categories'] ?? [] as $category) {
                if ($category['hidden'] || $category['deleted']) {
                    continue;
                }

                $categories->push($this->mapYnabCategory($category, $category['category_group_name'] ?? ''));
            }

            return $categories;
        });
    }

    /**
     * Calculate the pay period shortfall (how much is missing to be "one month ahead").
     *
     * Pay period is from payDay of current month to payDay-1 of next month.
     * Example: payDay=20 means Dec 20 - Jan 19.
     *
     * Returns categories with goal_type=NEED that fall in this period and are underfunded.
     *
     * @param  int  $payDay  Day of the month when user gets paid (default 20)
     * @return array{shortfall: float, monthly_essential: float, funded: float}
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function fetchPayPeriodShortfall(int $payDay = 20): array
    {
        $currentMonth = date('Y-m-01');
        $nextMonth = date('Y-m-01', strtotime('first day of next month'));

        // Fetch categories for both months
        $currentMonthCategories = $this->fetchCategoriesForMonth($currentMonth);
        $nextMonthCategories = $this->fetchCategoriesForMonth($nextMonth);

        $totalNeeded = 0.0;
        $totalUnderfunded = 0.0;

        // Current month: NEED categories with goal_day >= payDay (e.g. 20-31)
        foreach ($currentMonthCategories as $category) {
            if (($category['goal_type'] ?? null) !== 'NEED') {
                continue;
            }

            $goalDay = $category['goal_day'] ?? null;

            // Include if goal_day >= payDay, or if no goal_day (monthly expense)
            if ($goalDay === null || $goalDay >= $payDay) {
                $totalNeeded += $this->getMonthlyAmountForCategory($category);
                $totalUnderfunded += $category['goal_under_funded'] ?? 0;
            }
        }

        // Next month: NEED categories with goal_day < payDay (e.g. 1-19)
        foreach ($nextMonthCategories as $category) {
            if (($category['goal_type'] ?? null) !== 'NEED') {
                continue;
            }

            $goalDay = $category['goal_day'] ?? null;

            // Include if goal_day < payDay (but not null - those were counted in current month)
            if ($goalDay !== null && $goalDay < $payDay) {
                $totalNeeded += $this->getMonthlyAmountForCategory($category);
                $totalUnderfunded += $category['goal_under_funded'] ?? 0;
            }
        }

        return [
            'shortfall' => $totalUnderfunded,
            'monthly_essential' => $totalNeeded,
            'funded' => $totalNeeded - $totalUnderfunded,
        ];
    }

    /**
     * Get the monthly amount for a category based on its goal cadence.
     *
     * For yearly goals (goal_cadence = 13), use 'budgeted' (monthly contribution).
     * For monthly goals (goal_cadence = 1), use 'goal_target' (full monthly amount).
     *
     * @param  array<string, mixed>  $category
     */
    private function getMonthlyAmountForCategory(array $category): float
    {
        $goalCadence = $category['goal_cadence'] ?? null;

        // Yearly goals (cadence = 13): use budgeted amount (monthly contribution)
        if ($goalCadence === 13) {
            return (float) ($category['budgeted'] ?? 0);
        }

        // Monthly goals (cadence = 1) or no cadence: use goal_target or budgeted as fallback
        return $category['goal_target'] > 0
            ? (float) $category['goal_target']
            : (float) ($category['budgeted'] ?? 0);
    }

    /**
     * Fetch specific categories by their names.
     *
     * @param  array<int, string>  $names  Category names to fetch
     * @return Collection<int, array{name: string, balance: float, budgeted: float, goal_target: float, goal_under_funded: float}>
     */
    public function fetchCategoriesByNames(array $names): Collection
    {
        $allCategories = $this->fetchCategories();

        return $allCategories
            ->filter(function ($category) use ($names) {
                return in_array($category['name'], $names, true);
            })
            ->map(function ($category): array {
                return [
                    'name' => (string) $category['name'],
                    'balance' => (float) $category['balance'],
                    'budgeted' => (float) $category['budgeted'],
                    'goal_target' => (float) $category['goal_target'],
                    'goal_under_funded' => (float) $category['goal_under_funded'],
                ];
            })
            ->values();
    }
}
