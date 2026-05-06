<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCacheService;
use Livewire\Component;

/**
 * @property array<int, array<string, mixed>> $debts
 */
class ArchivedDebts extends Component
{
    public bool $isLoading = true;

    public ?int $viewingDebtId = null;

    protected DebtCacheService $debtCacheService;

    public function boot(DebtCacheService $debtCacheService): void
    {
        $this->debtCacheService = $debtCacheService;
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
        $debts = $this->debtCacheService->getAllArchivedWithPayments();

        return $debts->map(function (Debt $debt) {
            $totalPaid = (float) $debt->payments
                ->where('is_reconciliation_adjustment', false)
                ->sum('actual_amount');

            $totalInterest = (float) $debt->payments
                ->where('is_reconciliation_adjustment', false)
                ->sum('interest_paid');

            $monthsCount = $debt->payments
                ->whereNotNull('month_number')
                ->pluck('payment_month')
                ->unique()
                ->count();

            return [
                'id' => $debt->id,
                'name' => $debt->name,
                'type' => $debt->type,
                'originalBalance' => (float) ($debt->original_balance ?? 0),
                'interestRate' => (float) $debt->interest_rate,
                'totalPaid' => round($totalPaid, 2),
                'totalInterest' => round($totalInterest, 2),
                'monthsCount' => $monthsCount,
                'paidOffAt' => $debt->paid_off_at?->locale('nb')->translatedFormat('d. F Y'),
                'includeInCharts' => $debt->include_in_charts,
            ];
        })->sortByDesc('paidOffAt')->values()->toArray();
    }

    public function getArchivedCountProperty(): int
    {
        return Debt::archived()->count();
    }

    public function getTotalPaidOffProperty(): float
    {
        return (float) $this->debtCacheService
            ->getAllArchived()
            ->sum('original_balance');
    }

    public function toggleIncludeInCharts(int $debtId): void
    {
        $debt = Debt::find($debtId);

        if (! $debt || ! $debt->isArchived()) {
            return;
        }

        $debt->update(['include_in_charts' => ! $debt->include_in_charts]);

        session()->flash('message', $debt->include_in_charts
            ? __('app.debt_now_visible_in_charts', ['name' => $debt->name])
            : __('app.debt_hidden_from_charts', ['name' => $debt->name])
        );
    }

    public function viewHistory(int $debtId): void
    {
        $this->viewingDebtId = $debtId;
    }

    public function closeHistory(): void
    {
        $this->viewingDebtId = null;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.archived-debts')->layout('components.layouts.app');
    }
}
