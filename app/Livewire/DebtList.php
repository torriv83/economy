<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCacheService;
use App\Services\DebtCalculationService;
use App\Services\PaymentService;
use App\Services\PayoffSettingsService;
use App\Services\YnabService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class DebtList extends Component
{
    public bool $reorderMode = false;

    public bool $showYnabSync = false;

    /** @var array<string, array<int, array<string, mixed>>> */
    public array $ynabDiscrepancies = [];

    public bool $showLinkConfirmation = false;

    public ?int $linkingLocalDebtId = null;

    /** @var array<string, mixed> */
    public array $linkingYnabDebt = [];

    /** @var array<int, string> */
    public array $selectedFieldsToUpdate = [];

    public bool $showDeleteModal = false;

    public ?int $debtToDelete = null;

    public string $debtNameToDelete = '';

    /** @var array<int, bool> */
    public array $reconciliationModals = [];

    /** @var array<int, string> */
    public array $reconciliationBalances = [];

    /** @var array<int, string> */
    public array $reconciliationDates = [];

    /** @var array<int, string|null> */
    public array $reconciliationNotes = [];

    public bool $showReconciliationHistory = false;

    public ?int $viewingReconciliationHistoryForDebtId = null;

    protected DebtCalculationService $calculationService;

    protected YnabService $ynabService;

    protected PaymentService $paymentService;

    protected PayoffSettingsService $settingsService;

    protected DebtCacheService $debtCacheService;

    public function boot(
        DebtCalculationService $calculationService,
        YnabService $ynabService,
        PaymentService $paymentService,
        PayoffSettingsService $settingsService,
        DebtCacheService $debtCacheService
    ): void {
        $this->calculationService = $calculationService;
        $this->ynabService = $ynabService;
        $this->paymentService = $paymentService;
        $this->settingsService = $settingsService;
        $this->debtCacheService = $debtCacheService;
    }

    public function mount(): void
    {
        // Initialize reconciliation arrays with all debt IDs
        foreach (Debt::pluck('id') as $debtId) {
            $this->reconciliationModals[$debtId] = false;
            $this->reconciliationBalances[$debtId] = '';
            $this->reconciliationDates[$debtId] = now()->format('d.m.Y');
            $this->reconciliationNotes[$debtId] = '';
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDebtsProperty(): array
    {
        $debts = $this->debtCacheService->getAll();

        // If custom priorities are set, sort by them
        if ($debts->whereNotNull('custom_priority_order')->count() > 0) {
            $debts = $debts->sortBy('custom_priority_order');
        }

        return $debts->map(function ($debt) {
            // Calculate progress percentage (how much has been paid off)
            $originalBalance = $debt->original_balance ?? $debt->balance;
            $paidOff = $originalBalance - $debt->balance;
            $progressPercentage = $originalBalance > 0 ? round(($paidOff / $originalBalance) * 100, 1) : 0;

            return [
                'id' => $debt->id,
                'name' => $debt->name,
                'type' => $debt->type,
                'balance' => $debt->balance,
                'originalBalance' => $debt->original_balance,
                'interestRate' => $debt->interest_rate,
                'minimumPayment' => $debt->minimum_payment,
                'dueDay' => $debt->due_day,
                'isCompliant' => $debt->isMinimumPaymentCompliant(),
                'warning' => $debt->getMinimumPaymentWarning(),
                'createdAt' => $debt->created_at->locale('nb')->translatedFormat('d. F Y'),
                'customPriority' => $debt->custom_priority_order,
                'progressPercentage' => $progressPercentage,
                'amountPaid' => $paidOff,
            ];
        })->values()->toArray();
    }

    public function getTotalDebtProperty(): float
    {
        return Debt::sum('balance');
    }

    public function getDebtsCountProperty(): int
    {
        return Debt::count();
    }

    public function getLastUpdatedProperty(): ?string
    {
        $latestDebt = Debt::latest('updated_at')->first();

        if (! $latestDebt) {
            return null;
        }

        return $latestDebt->updated_at->locale('nb')->translatedFormat('d. F Y \k\l. H:i');
    }

    /**
     * @return array<string, float|int>|null
     */
    public function getPayoffEstimateProperty(): ?array
    {
        $debts = $this->debtCacheService->getAll();

        if ($debts->isEmpty()) {
            return null;
        }

        // Check if all debts have minimum payments
        $hasAllMinimums = $debts->every(fn ($debt) => $debt->minimum_payment > 0);

        if (! $hasAllMinimums) {
            return null;
        }

        // Calculate true minimum payments only (no reallocation of freed-up payments)
        $months = $this->calculationService->calculateMinimumPaymentsOnly($debts);

        $years = floor($months / 12);
        $remainingMonths = $months % 12;

        return [
            'years' => $years,
            'months' => $remainingMonths,
            'totalMonths' => $months,
        ];
    }

    /**
     * Get payoff estimate using the user's chosen strategy and extra payment.
     *
     * @return array<string, float|int>|null
     */
    public function getStrategyEstimateProperty(): ?array
    {
        $debts = $this->debtCacheService->getAll();

        if ($debts->isEmpty()) {
            return null;
        }

        $extraPayment = $this->settingsService->getExtraPayment();
        $strategy = $this->settingsService->getStrategy();

        $schedule = $this->calculationService->generatePaymentSchedule($debts, $extraPayment, $strategy);

        if (empty($schedule['payoffDate'])) {
            return null;
        }

        // Calculate months directly from the payoff date for accuracy
        $payoffDate = Carbon::parse($schedule['payoffDate']);
        $now = now();
        $diff = $now->diff($payoffDate);

        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'totalMonths' => ($diff->y * 12) + $diff->m,
        ];
    }

    public function confirmDelete(int $id, string $name): void
    {
        $this->debtToDelete = $id;
        $this->debtNameToDelete = $name;
        $this->showDeleteModal = true;
    }

    public function deleteDebt(): void
    {
        if ($this->debtToDelete) {
            $debt = Debt::find($this->debtToDelete);

            if ($debt) {
                $debt->delete();
                session()->flash('message', 'Gjeld slettet.');
            }
        }

        $this->showDeleteModal = false;
        $this->debtToDelete = null;
        $this->debtNameToDelete = '';
    }

    public function enableReorderMode(): void
    {
        $this->reorderMode = true;
    }

    public function cancelReorder(): void
    {
        $this->reorderMode = false;
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function updateOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Debt::where('id', $id)->update([
                'custom_priority_order' => $index + 1,
            ]);
        }

        $this->reorderMode = false;
        session()->flash('message', 'Prioritetsrekkefølge lagret.');
    }

    public function checkYnab(): void
    {
        // First check if YNAB API is accessible
        if (! $this->ynabService->isAccessible()) {
            session()->flash('error', 'YNAB er for tiden nede. Prøv igjen senere.');

            return;
        }

        try {
            $ynabDebts = $this->ynabService->fetchDebtAccounts();
            $localDebts = $this->debtCacheService->getAll();

            $this->ynabDiscrepancies = $this->findDiscrepancies($ynabDebts, $localDebts);
            $this->showYnabSync = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Kunne ikke hente data fra YNAB: '.$e->getMessage());
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $ynabDebts
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $localDebts
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function findDiscrepancies(Collection $ynabDebts, Collection $localDebts): array
    {
        $discrepancies = [
            'new' => [],
            'closed' => [],
            'potential_matches' => [],
            'balance_mismatch' => [],
        ];

        // Find new debts in YNAB that don't exist locally
        foreach ($ynabDebts as $ynabDebt) {
            // First check if already linked by YNAB account ID
            $linkedByAccountId = $localDebts->first(function ($localDebt) use ($ynabDebt) {
                return $localDebt->ynab_account_id === $ynabDebt['ynab_id'];
            });

            if ($linkedByAccountId) {
                // Check if balances are different (use 0.001 tolerance for floating point)
                if (abs($ynabDebt['balance'] - $linkedByAccountId->balance) > 0.001) {
                    $discrepancies['balance_mismatch'][] = [
                        'local_debt' => $linkedByAccountId,
                        'ynab_debt' => $ynabDebt,
                        'local_balance' => $linkedByAccountId->balance,
                        'ynab_balance' => $ynabDebt['balance'],
                        'difference' => round($ynabDebt['balance'] - $linkedByAccountId->balance, 2),
                    ];
                }

                // Already linked, skip further processing for this YNAB debt
                continue;
            }

            if (! $ynabDebt['closed']) {
                // Look for potential matches (similar names or exact matches)
                $potentialMatch = $this->findPotentialMatch($ynabDebt['name'], $localDebts);

                if ($potentialMatch) {
                    $discrepancies['potential_matches'][] = [
                        'ynab' => $ynabDebt,
                        'local' => [
                            'id' => $potentialMatch->id,
                            'name' => $potentialMatch->name,
                            'balance' => $potentialMatch->balance,
                            'interest_rate' => $potentialMatch->interest_rate,
                        ],
                    ];
                } else {
                    $discrepancies['new'][] = $ynabDebt;
                }
            }
        }

        // Find debts that are closed in YNAB but still exist locally
        foreach ($localDebts as $localDebt) {
            // Check both by account ID and by name
            $ynabDebt = $ynabDebts->first(function ($ynabDebt) use ($localDebt) {
                return ($localDebt->ynab_account_id && $localDebt->ynab_account_id === $ynabDebt['ynab_id'])
                    || strtolower($ynabDebt['name']) === strtolower($localDebt->name);
            });

            if ($ynabDebt && $ynabDebt['closed']) {
                $discrepancies['closed'][] = [
                    'id' => $localDebt->id,
                    'name' => $localDebt->name,
                    'balance' => $localDebt->balance,
                ];
            }
        }

        return $discrepancies;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Debt>  $localDebts
     */
    protected function findPotentialMatch(string $ynabName, Collection $localDebts): ?Debt
    {
        // Normalize names for comparison
        $normalizedYnabName = strtolower(trim($ynabName));

        return $localDebts->first(function ($localDebt) use ($normalizedYnabName) {
            // Skip debts that are already linked to a YNAB account
            if ($localDebt->ynab_account_id) {
                return false;
            }

            $normalizedLocalName = strtolower(trim($localDebt->name));

            // Check if one name contains the other
            return str_contains($normalizedYnabName, $normalizedLocalName)
                || str_contains($normalizedLocalName, $normalizedYnabName)
                || similar_text($normalizedYnabName, $normalizedLocalName) > 5;
        });
    }

    /**
     * @param  array<string, mixed>  $ynabDebt
     */
    public function importYnabDebt(array $ynabDebt): void
    {
        Debt::create([
            'name' => $ynabDebt['name'],
            'balance' => $ynabDebt['balance'],
            'original_balance' => $ynabDebt['balance'],
            'interest_rate' => $ynabDebt['interest_rate'],
            'minimum_payment' => $ynabDebt['minimum_payment'] ?? 0,
            'ynab_account_id' => $ynabDebt['ynab_id'],
        ]);

        // Remove from discrepancies
        if (isset($this->ynabDiscrepancies['new'])) {
            $this->ynabDiscrepancies['new'] = array_filter(
                $this->ynabDiscrepancies['new'],
                fn ($debt) => $debt['name'] !== $ynabDebt['name']
            );
        }

        session()->flash('message', "'{$ynabDebt['name']}' importert fra YNAB.");
    }

    public function importAllYnabDebts(): void
    {
        if (! isset($this->ynabDiscrepancies['new']) || count($this->ynabDiscrepancies['new']) === 0) {
            return;
        }

        $count = count($this->ynabDiscrepancies['new']);

        foreach ($this->ynabDiscrepancies['new'] as $debt) {
            Debt::create([
                'name' => $debt['name'],
                'balance' => $debt['balance'],
                'original_balance' => $debt['balance'],
                'interest_rate' => $debt['interest_rate'],
                'minimum_payment' => $debt['minimum_payment'] ?? 0,
                'ynab_account_id' => $debt['ynab_id'],
            ]);
        }

        // Clear the new debts array
        $this->ynabDiscrepancies['new'] = [];

        session()->flash('message', "{$count} gjeld importert fra YNAB.");
    }

    public function deleteClosedDebt(int $id, string $name): void
    {
        $debt = Debt::find($id);

        if ($debt) {
            $debt->delete();

            // Remove from discrepancies
            $this->ynabDiscrepancies['closed'] = array_filter(
                $this->ynabDiscrepancies['closed'],
                fn ($debt) => $debt['id'] !== $id
            );

            session()->flash('message', "'{$name}' slettet.");
        }
    }

    public function ignorePotentialMatch(string $ynabName): void
    {
        // Remove from potential matches and add to new debts
        /** @var array<string, mixed>|null $match */
        $match = collect($this->ynabDiscrepancies['potential_matches'])->first(function ($item) use ($ynabName) {
            return $item['ynab']['name'] === $ynabName;
        });

        if ($match) {
            $this->ynabDiscrepancies['potential_matches'] = array_filter(
                $this->ynabDiscrepancies['potential_matches'],
                fn ($item) => $item['ynab']['name'] !== $ynabName
            );

            $this->ynabDiscrepancies['new'][] = $match['ynab'];
        }
    }

    /**
     * @param  array<string, mixed>  $ynabDebt
     */
    public function openLinkConfirmation(int $localDebtId, array $ynabDebt): void
    {
        $this->linkingLocalDebtId = $localDebtId;
        $this->linkingYnabDebt = $ynabDebt;
        $this->selectedFieldsToUpdate = [];
        $this->showLinkConfirmation = true;
    }

    public function closeLinkConfirmation(): void
    {
        $this->showLinkConfirmation = false;
        $this->linkingLocalDebtId = null;
        $this->linkingYnabDebt = [];
        $this->selectedFieldsToUpdate = [];
    }

    public function confirmLinkToExistingDebt(): void
    {
        if (! $this->linkingLocalDebtId || empty($this->linkingYnabDebt)) {
            session()->flash('error', 'Ugyldig kobling.');

            return;
        }

        $debt = Debt::find($this->linkingLocalDebtId);

        if (! $debt) {
            session()->flash('error', 'Kunne ikke finne gjelden.');

            return;
        }

        // Update the YNAB account ID to link them
        $updateData = ['ynab_account_id' => $this->linkingYnabDebt['ynab_id']];

        // Update selected fields
        if (in_array('name', $this->selectedFieldsToUpdate)) {
            $updateData['name'] = $this->linkingYnabDebt['name'];
        }
        if (in_array('balance', $this->selectedFieldsToUpdate)) {
            $updateData['balance'] = $this->linkingYnabDebt['balance'];
        }
        if (in_array('interest_rate', $this->selectedFieldsToUpdate)) {
            $updateData['interest_rate'] = $this->linkingYnabDebt['interest_rate'];
        }
        if (in_array('minimum_payment', $this->selectedFieldsToUpdate)) {
            $updateData['minimum_payment'] = $this->linkingYnabDebt['minimum_payment'];
        }

        $debt->update($updateData);

        // Remove from potential matches if they exist
        if (isset($this->ynabDiscrepancies['potential_matches'])) {
            $this->ynabDiscrepancies['potential_matches'] = array_filter(
                $this->ynabDiscrepancies['potential_matches'],
                fn ($item) => $item['ynab']['name'] !== $this->linkingYnabDebt['name']
            );
        }

        session()->flash('message', "'{$debt->name}' koblet til YNAB-konto.");

        $this->closeLinkConfirmation();
    }

    public function closeSyncModal(): void
    {
        $this->showYnabSync = false;
        $this->ynabDiscrepancies = [];
    }

    public function openReconciliationModal(int $debtId): void
    {
        $this->reconciliationModals[$debtId] = true;

        $debt = Debt::find($debtId);
        if ($debt) {
            $this->reconciliationBalances[$debtId] = (string) $debt->balance;
            $this->reconciliationDates[$debtId] = now()->format('d.m.Y');
            $this->reconciliationNotes[$debtId] = null;
        }
    }

    public function openReconciliationFromYnab(int $debtId, float $ynabBalance): void
    {
        $this->reconciliationModals[$debtId] = true;
        $this->reconciliationBalances[$debtId] = (string) $ynabBalance;
        $this->reconciliationDates[$debtId] = now()->format('d.m.Y');
        $this->reconciliationNotes[$debtId] = 'Avstemt mot YNAB';
        $this->showYnabSync = false;
    }

    public function closeReconciliationModal(int $debtId): void
    {
        unset($this->reconciliationModals[$debtId]);
        unset($this->reconciliationBalances[$debtId]);
        unset($this->reconciliationNotes[$debtId]);

        $debt = Debt::find($debtId);
        if ($debt) {
            $this->reconciliationBalances[$debtId] = (string) $debt->balance;
            $this->reconciliationDates[$debtId] = now()->format('d.m.Y');
        }
    }

    public function getReconciliationDifference(int $debtId): float
    {
        $debt = Debt::find($debtId);
        if (! $debt) {
            return 0;
        }

        $actualBalance = isset($this->reconciliationBalances[$debtId])
            ? (float) $this->reconciliationBalances[$debtId]
            : $debt->balance;

        return round($actualBalance - $debt->balance, 2);
    }

    public function reconcileDebt(int $debtId): void
    {
        $debt = Debt::find($debtId);
        if (! $debt) {
            return;
        }

        $this->validate([
            "reconciliationBalances.{$debtId}" => ['required', 'numeric', 'min:0'],
            "reconciliationDates.{$debtId}" => ['required', 'date_format:d.m.Y'],
            "reconciliationNotes.{$debtId}" => ['nullable', 'string', 'max:500'],
        ], [
            "reconciliationBalances.{$debtId}.required" => 'Faktisk saldo er påkrevd.',
            "reconciliationBalances.{$debtId}.numeric" => 'Faktisk saldo må være et tall.',
            "reconciliationBalances.{$debtId}.min" => 'Faktisk saldo kan ikke være negativ.',
            "reconciliationDates.{$debtId}.required" => 'Avstemmingsdato er påkrevd.',
            "reconciliationDates.{$debtId}.date_format" => 'Avstemmingsdato må være i formatet DD.MM.ÅÅÅÅ.',
            "reconciliationNotes.{$debtId}.max" => 'Notater kan ikke være lengre enn 500 tegn.',
        ]);

        $difference = $this->getReconciliationDifference($debtId);

        if (abs($difference) < 0.01) {
            session()->flash('message', 'Ingen justering nødvendig - saldo er allerede korrekt.');
            $this->closeReconciliationModal($debtId);

            return;
        }

        // Convert Norwegian date format (DD.MM.YYYY) to database format (YYYY-MM-DD)
        $dateObject = \DateTime::createFromFormat('d.m.Y', $this->reconciliationDates[$debtId]);
        $databaseDate = $dateObject ? $dateObject->format('Y-m-d') : now()->format('Y-m-d');

        $this->paymentService->reconcileDebt(
            $debt,
            (float) $this->reconciliationBalances[$debtId],
            $databaseDate,
            $this->reconciliationNotes[$debtId] ?? null
        );

        session()->flash('message', 'Gjeld avstemt.');

        $this->closeReconciliationModal($debtId);

        $this->dispatch('$refresh');
    }

    public function openReconciliationHistory(int $debtId): void
    {
        $this->viewingReconciliationHistoryForDebtId = $debtId;
        $this->showReconciliationHistory = true;
    }

    public function closeReconciliationHistory(): void
    {
        $this->showReconciliationHistory = false;
        $this->viewingReconciliationHistoryForDebtId = null;
    }

    /**
     * Get the name of the debt being viewed in the reconciliation history modal
     */
    public function getHistoryDebtNameProperty(): ?string
    {
        if (! $this->viewingReconciliationHistoryForDebtId) {
            return null;
        }

        return Debt::where('id', $this->viewingReconciliationHistoryForDebtId)
            ->value('name');
    }

    /**
     * Get reconciliation counts for all debts (cached to prevent N+1 queries)
     *
     * @return array<int, int>
     */
    public function getReconciliationCountsProperty(): array
    {
        return Payment::where('is_reconciliation_adjustment', true)
            ->selectRaw('debt_id, COUNT(*) as count')
            ->groupBy('debt_id')
            ->pluck('count', 'debt_id')
            ->toArray();
    }

    public function getReconciliationCountForDebt(int $debtId): int
    {
        return $this->reconciliationCounts[$debtId] ?? 0;
    }

    #[On('reconciliation-deleted')]
    #[On('reconciliation-updated')]
    public function handleReconciliationChanged(): void
    {
        // Refresh the component to update balances after reconciliation changes
        $this->dispatch('$refresh');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.debt-list')->layout('components.layouts.app');
    }
}
