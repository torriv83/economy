<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use App\Services\YnabService;
use Illuminate\Support\Collection;
use Livewire\Component;

class DebtList extends Component
{
    public bool $reorderMode = false;

    public bool $showYnabSync = false;

    public array $ynabDiscrepancies = [];

    public bool $showLinkConfirmation = false;

    public ?int $linkingLocalDebtId = null;

    public array $linkingYnabDebt = [];

    public array $selectedFieldsToUpdate = [];

    public bool $showDeleteModal = false;

    public ?int $debtToDelete = null;

    public string $debtNameToDelete = '';

    protected DebtCalculationService $calculationService;

    protected YnabService $ynabService;

    public function boot(DebtCalculationService $calculationService, YnabService $ynabService): void
    {
        $this->calculationService = $calculationService;
        $this->ynabService = $ynabService;
    }

    public function getDebtsProperty(): array
    {
        $debts = Debt::all();

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

    public function getPayoffEstimateProperty(): ?array
    {
        $debts = Debt::all();

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
            $localDebts = Debt::all();

            $this->ynabDiscrepancies = $this->findDiscrepancies($ynabDebts, $localDebts);
            $this->showYnabSync = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Kunne ikke hente data fra YNAB: '.$e->getMessage());
        }
    }

    public function findDiscrepancies(Collection $ynabDebts, Collection $localDebts): array
    {
        $discrepancies = [
            'new' => [],
            'closed' => [],
            'potential_matches' => [],
        ];

        // Find new debts in YNAB that don't exist locally
        foreach ($ynabDebts as $ynabDebt) {
            // First check if already linked by YNAB account ID
            $linkedByAccountId = $localDebts->first(function ($localDebt) use ($ynabDebt) {
                return $localDebt->ynab_account_id === $ynabDebt['ynab_id'];
            });

            if ($linkedByAccountId) {
                // Already linked, skip this YNAB debt
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

    public function render()
    {
        return view('livewire.debt-list')->layout('components.layouts.app');
    }
}
