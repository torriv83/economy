<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\HasDeleteConfirmation;
use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCacheService;
use App\Services\DebtCalculationService;
use App\Services\PaymentService;
use App\Services\PayoffSettingsService;
use App\Services\YnabDiscrepancyService;
use App\Services\YnabService;
use App\Services\YnabSyncService;
use App\Support\DateFormatter;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class DebtList extends Component
{
    use HasDeleteConfirmation;

    public bool $isLoading = true;

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

    /** @var array<int, array{show: bool, balance: string, date: string, notes: string|null}> */
    public array $reconciliations = [];

    public bool $showReconciliationHistory = false;

    public ?int $viewingReconciliationHistoryForDebtId = null;

    public bool $ynabEnabled = false;

    protected DebtCalculationService $calculationService;

    protected YnabService $ynabService;

    protected YnabDiscrepancyService $discrepancyService;

    protected PaymentService $paymentService;

    protected PayoffSettingsService $settingsService;

    protected DebtCacheService $debtCacheService;

    protected YnabSyncService $ynabSyncService;

    protected \App\Services\SettingsService $globalSettingsService;

    public function boot(
        DebtCalculationService $calculationService,
        YnabService $ynabService,
        YnabDiscrepancyService $discrepancyService,
        PaymentService $paymentService,
        PayoffSettingsService $settingsService,
        DebtCacheService $debtCacheService,
        YnabSyncService $ynabSyncService,
        \App\Services\SettingsService $globalSettingsService
    ): void {
        $this->calculationService = $calculationService;
        $this->ynabService = $ynabService;
        $this->discrepancyService = $discrepancyService;
        $this->paymentService = $paymentService;
        $this->settingsService = $settingsService;
        $this->debtCacheService = $debtCacheService;
        $this->ynabSyncService = $ynabSyncService;
        $this->globalSettingsService = $globalSettingsService;
    }

    public function mount(): void
    {
        $this->ynabEnabled = $this->globalSettingsService->isYnabEnabled();
        // Reconciliations array is initialized on-demand when modals are opened
    }

    public function loadData(): void
    {
        $this->isLoading = false;
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
                'lastVerifiedAt' => $debt->last_verified_at?->locale('nb')->translatedFormat('d. F Y'),
                'customPriority' => $debt->custom_priority_order,
                'progressPercentage' => $progressPercentage,
                'amountPaid' => $paidOff,
            ];
        })->values()->toArray();
    }

    public function getTotalDebtProperty(): float
    {
        return (float) Debt::sum('balance');
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

        $historicalPayments = $this->paymentService->getHistoricalPayments();
        $historicalMonthOffset = count($historicalPayments);

        $schedule = $this->calculationService->generatePaymentSchedule($debts, $extraPayment, $strategy, $historicalMonthOffset);

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

    /**
     * Get the formatted payoff date using the user's chosen strategy.
     */
    public function getStrategyPayoffDateProperty(): ?string
    {
        $debts = $this->debtCacheService->getAll();

        if ($debts->isEmpty()) {
            return null;
        }

        $extraPayment = $this->settingsService->getExtraPayment();
        $strategy = $this->settingsService->getStrategy();

        $historicalPayments = $this->paymentService->getHistoricalPayments();
        $historicalMonthOffset = count($historicalPayments);

        $schedule = $this->calculationService->generatePaymentSchedule($debts, $extraPayment, $strategy, $historicalMonthOffset);

        if (empty($schedule['payoffDate'])) {
            return null;
        }

        $payoffDate = Carbon::parse($schedule['payoffDate']);
        $payoffDate->locale('nb');

        return $payoffDate->isoFormat('MMMM YYYY');
    }

    protected function performDelete(int $id): void
    {
        $debt = Debt::find($id);
        if ($debt) {
            $debt->delete();
            session()->flash('message', __('app.debt_deleted'));
        }
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

            $this->ynabDiscrepancies = $this->discrepancyService->findDiscrepancies($ynabDebts, $localDebts);
            $this->showYnabSync = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Kunne ikke hente data fra YNAB: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $ynabDebt
     */
    public function importYnabDebt(array $ynabDebt): void
    {
        $this->ynabSyncService->importDebt($ynabDebt);

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

        $count = $this->ynabSyncService->importAllDebts($this->ynabDiscrepancies['new']);

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

        $this->ynabSyncService->linkDebtToYnab($debt, $this->linkingYnabDebt, $this->selectedFieldsToUpdate);

        // Remove from potential matches if they exist
        if (isset($this->ynabDiscrepancies['potential_matches'])) {
            $this->ynabDiscrepancies['potential_matches'] = array_filter(
                $this->ynabDiscrepancies['potential_matches'],
                fn ($item) => $item['ynab']['name'] !== $this->linkingYnabDebt['name']
            );
        }

        $debt->refresh();
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
        $debt = Debt::find($debtId);
        if ($debt) {
            $this->reconciliations[$debtId] = [
                'show' => true,
                'balance' => (string) $debt->balance,
                'date' => DateFormatter::todayNorwegian(),
                'notes' => null,
            ];
        }
    }

    public function openReconciliationFromYnab(int $debtId, float $ynabBalance): void
    {
        $this->reconciliations[$debtId] = [
            'show' => true,
            'balance' => (string) $ynabBalance,
            'date' => DateFormatter::todayNorwegian(),
            'notes' => 'Avstemt mot YNAB',
        ];
        $this->showYnabSync = false;
    }

    public function closeReconciliationModal(int $debtId): void
    {
        unset($this->reconciliations[$debtId]);
    }

    public function getReconciliationDifference(int $debtId): float
    {
        $debt = Debt::find($debtId);
        if (! $debt) {
            return 0;
        }

        $actualBalance = isset($this->reconciliations[$debtId]['balance'])
            ? (float) $this->reconciliations[$debtId]['balance']
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
            "reconciliations.{$debtId}.balance" => ['required', 'numeric', 'min:0'],
            "reconciliations.{$debtId}.date" => ['required', 'date_format:d.m.Y'],
            "reconciliations.{$debtId}.notes" => ['nullable', 'string', 'max:500'],
        ], [
            "reconciliations.{$debtId}.balance.required" => 'Faktisk saldo er påkrevd.',
            "reconciliations.{$debtId}.balance.numeric" => 'Faktisk saldo må være et tall.',
            "reconciliations.{$debtId}.balance.min" => 'Faktisk saldo kan ikke være negativ.',
            "reconciliations.{$debtId}.date.required" => 'Avstemmingsdato er påkrevd.',
            "reconciliations.{$debtId}.date.date_format" => 'Avstemmingsdato må være i formatet DD.MM.ÅÅÅÅ.',
            "reconciliations.{$debtId}.notes.max" => 'Notater kan ikke være lengre enn 500 tegn.',
        ]);

        $difference = $this->getReconciliationDifference($debtId);
        $databaseDate = DateFormatter::norwegianToDatabase($this->reconciliations[$debtId]['date']);

        if (abs($difference) < 0.01) {
            // No adjustment needed, but still update the verification timestamp
            $debt->update(['last_verified_at' => $databaseDate]);
            session()->flash('message', __('app.debt_verified_balance_correct'));
            $this->closeReconciliationModal($debtId);

            return;
        }

        $this->paymentService->reconcileDebt(
            $debt,
            (float) $this->reconciliations[$debtId]['balance'],
            $databaseDate,
            $this->reconciliations[$debtId]['notes'] ?? null
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
