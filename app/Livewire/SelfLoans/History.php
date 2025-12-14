<?php

declare(strict_types=1);

namespace App\Livewire\SelfLoans;

use App\Models\SelfLoan\SelfLoan;
use App\Models\SelfLoan\SelfLoanRepayment;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class History extends Component
{
    public bool $isLoading = true;

    public ?int $selectedLoanId = null;

    // Edit modal properties
    public bool $showEditModal = false;

    public ?int $editRepaymentId = null;

    public string $editAmount = '';

    public string $editNotes = '';

    public string $editPaidAt = '';

    // Delete modal properties
    public bool $showDeleteModal = false;

    public ?int $repaymentToDelete = null;

    public string $repaymentLoanName = '';

    public function loadData(): void
    {
        $this->isLoading = false;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function getAvailableLoansProperty(): array
    {
        $loans = SelfLoan::orderBy('name')->get();

        return $loans->map(function ($loan) {
            return [
                'id' => $loan->id,
                'name' => $loan->name,
            ];
        })->toArray();
    }

    /**
     * @return array<int, array{id: int, name: string, description: string|null, original_amount: float, created_at: string, repayments: array<int, array{amount: float, notes: string|null, paid_at: string}>}>
     */
    public function getPaidOffLoansProperty(): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, SelfLoan> $loans */
        $loans = SelfLoan::where('current_balance', '<=', 0)->latest()->get();

        return $loans->map(function (SelfLoan $loan) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, SelfLoanRepayment> $repayments */
            $repayments = $loan->repayments()->latest('paid_at')->get();

            return [
                'id' => $loan->id,
                'name' => $loan->name,
                'description' => $loan->description,
                'original_amount' => $loan->original_amount,
                'created_at' => $loan->created_at->locale('nb')->translatedFormat('d. F Y'),
                'repayments' => $repayments->map(function (SelfLoanRepayment $repayment) {
                    /** @var \Carbon\Carbon $paidAt */
                    $paidAt = $repayment->paid_at;

                    return [
                        'amount' => $repayment->amount,
                        'notes' => $repayment->notes,
                        'paid_at' => $paidAt->locale('nb')->translatedFormat('d. F Y H:i'),
                    ];
                })->toArray(),
            ];
        })->values()->toArray();
    }

    /**
     * @return array<int, array{id: int, loan_name: string, amount: float, notes: string|null, paid_at: string}>
     */
    public function getAllRepaymentsProperty(): array
    {
        $query = SelfLoanRepayment::with('selfLoan')
            ->latest('paid_at');

        if ($this->selectedLoanId !== null) {
            $query->where('self_loan_id', $this->selectedLoanId);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, SelfLoanRepayment> $repayments */
        $repayments = $query->get();

        return $repayments->map(function (SelfLoanRepayment $repayment) {
            /** @var SelfLoan $loan */
            $loan = $repayment->selfLoan;
            /** @var \Carbon\Carbon $paidAt */
            $paidAt = $repayment->paid_at;

            return [
                'id' => $repayment->id,
                'loan_name' => $loan->name,
                'amount' => $repayment->amount,
                'notes' => $repayment->notes,
                'paid_at' => $paidAt->locale('nb')->translatedFormat('d. F Y H:i'),
            ];
        })->toArray();
    }

    public function clearFilter(): void
    {
        $this->selectedLoanId = null;
    }

    public function openEditModal(int $id): void
    {
        $repayment = SelfLoanRepayment::findOrFail($id);

        $this->editRepaymentId = $repayment->id;
        $this->editAmount = (string) abs($repayment->amount);
        $this->editNotes = $repayment->notes ?? '';
        /** @var \Carbon\Carbon $paidAt */
        $paidAt = $repayment->paid_at;
        $this->editPaidAt = $paidAt->format('Y-m-d\TH:i');
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editRepaymentId = null;
        $this->editAmount = '';
        $this->editNotes = '';
        $this->editPaidAt = '';
        $this->resetValidation();
    }

    public function updateRepayment(): void
    {
        $this->validate([
            'editAmount' => ['required', 'numeric', 'min:0.01'],
            'editPaidAt' => ['required', 'date'],
            'editNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $repayment = SelfLoanRepayment::findOrFail($this->editRepaymentId);
        $loan = $repayment->selfLoan;
        $wasNegative = $repayment->amount < 0;

        DB::transaction(function () use ($repayment, $loan, $wasNegative): void {
            $newAmount = $wasNegative ? -abs((float) $this->editAmount) : abs((float) $this->editAmount);

            $repayment->update([
                'amount' => $newAmount,
                'notes' => $this->editNotes ?: null,
                'paid_at' => $this->editPaidAt,
            ]);

            $this->recalculateLoanBalance($loan);
        });

        $this->closeEditModal();
        session()->flash('message', __('app.repayment_updated'));
    }

    public function confirmDelete(int $id, string $loanName): void
    {
        $this->repaymentToDelete = $id;
        $this->repaymentLoanName = $loanName;
        $this->showDeleteModal = true;
    }

    public function deleteRepayment(): void
    {
        $repayment = SelfLoanRepayment::findOrFail($this->repaymentToDelete);
        $loan = $repayment->selfLoan;

        DB::transaction(function () use ($repayment, $loan): void {
            $repayment->delete();
            $this->recalculateLoanBalance($loan);
        });

        $this->showDeleteModal = false;
        $this->repaymentToDelete = null;
        $this->repaymentLoanName = '';

        session()->flash('message', __('app.repayment_deleted'));
    }

    private function recalculateLoanBalance(SelfLoan $loan): void
    {
        $totalRepayments = (float) $loan->repayments()->sum('amount');
        $loan->current_balance = $loan->original_amount - $totalRepayments;
        $loan->save();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.self-loans.history');
    }
}
