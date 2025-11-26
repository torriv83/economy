<?php

declare(strict_types=1);

namespace App\Livewire\SelfLoans;

use App\Models\SelfLoan\SelfLoan;
use App\Models\SelfLoan\SelfLoanRepayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Overview extends Component
{
    public int $selectedLoanId = 0;

    public float $repaymentAmount = 0;

    public string $repaymentNotes = '';

    public string $repaymentDate = '';

    public bool $showRepaymentModal = false;

    public float $withdrawalAmount = 0;

    public string $withdrawalNotes = '';

    public string $withdrawalDate = '';

    public bool $showWithdrawalModal = false;

    public string $editName = '';

    public string $editDescription = '';

    public string $editOriginalAmount = '';

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public ?int $loanToDelete = null;

    public string $loanNameToDelete = '';

    /**
     * @return array<int, array{id: int, name: string, description: string|null, original_amount: float, current_balance: float, total_repaid: float, progress_percentage: float, created_at: string}>
     */
    public function getSelfLoansProperty(): array
    {
        $loans = SelfLoan::where('current_balance', '>', 0)->get();

        return $loans->map(function ($loan) {
            return [
                'id' => $loan->id,
                'name' => $loan->name,
                'description' => $loan->description,
                'original_amount' => $loan->original_amount,
                'current_balance' => $loan->current_balance,
                'total_repaid' => $loan->getTotalRepaidAmount(),
                'progress_percentage' => $loan->getProgressPercentage(),
                'created_at' => $loan->created_at->locale('nb')->translatedFormat('d. F Y'),
            ];
        })->values()->toArray();
    }

    public function getTotalBorrowedProperty(): float
    {
        return SelfLoan::where('current_balance', '>', 0)->sum('current_balance');
    }

    public function getLoansCountProperty(): int
    {
        return SelfLoan::where('current_balance', '>', 0)->count();
    }

    public function getSelectedLoanBalanceProperty(): float
    {
        if ($this->selectedLoanId === 0) {
            return 0;
        }

        $loan = SelfLoan::find($this->selectedLoanId);

        return $loan !== null ? $loan->current_balance : 0;
    }

    public function openRepaymentModal(int $loanId): void
    {
        $this->selectedLoanId = $loanId;
        $this->repaymentAmount = 0;
        $this->repaymentNotes = '';
        $this->repaymentDate = now()->format('d.m.Y');
        $this->showRepaymentModal = true;
    }

    public function closeRepaymentModal(): void
    {
        $this->showRepaymentModal = false;
        $this->selectedLoanId = 0;
        $this->repaymentAmount = 0;
        $this->repaymentNotes = '';
        $this->repaymentDate = '';
        $this->resetValidation();
    }

    public function addRepayment(): void
    {
        $this->validate([
            'repaymentAmount' => 'required|numeric|min:0.01',
            'repaymentNotes' => 'nullable|string|max:500',
            'repaymentDate' => 'required|date_format:d.m.Y|before_or_equal:today',
        ]);

        $loan = SelfLoan::findOrFail($this->selectedLoanId);

        if ($this->repaymentAmount > $loan->current_balance) {
            $this->addError('repaymentAmount', __('app.validation_repayment_exceeds_balance'));

            return;
        }

        DB::transaction(function () use ($loan) {
            SelfLoanRepayment::create([
                'self_loan_id' => $loan->id,
                'amount' => $this->repaymentAmount,
                'notes' => $this->repaymentNotes,
                'paid_at' => Carbon::createFromFormat('d.m.Y', $this->repaymentDate),
            ]);

            $loan->update([
                'current_balance' => $loan->current_balance - $this->repaymentAmount,
            ]);
        });

        session()->flash('message', __('app.repayment_added_successfully'));

        $this->closeRepaymentModal();
    }

    public function openWithdrawalModal(int $loanId): void
    {
        $this->selectedLoanId = $loanId;
        $this->withdrawalAmount = 0;
        $this->withdrawalNotes = '';
        $this->withdrawalDate = now()->format('d.m.Y');
        $this->showWithdrawalModal = true;
    }

    public function closeWithdrawalModal(): void
    {
        $this->showWithdrawalModal = false;
        $this->selectedLoanId = 0;
        $this->withdrawalAmount = 0;
        $this->withdrawalNotes = '';
        $this->withdrawalDate = '';
        $this->resetValidation();
    }

    public function addWithdrawal(): void
    {
        $this->validate([
            'withdrawalAmount' => 'required|numeric|min:0.01',
            'withdrawalNotes' => 'nullable|string|max:500',
            'withdrawalDate' => 'required|date_format:d.m.Y|before_or_equal:today',
        ]);

        $loan = SelfLoan::findOrFail($this->selectedLoanId);

        DB::transaction(function () use ($loan) {
            // Create a negative repayment to track withdrawals
            SelfLoanRepayment::create([
                'self_loan_id' => $loan->id,
                'amount' => -$this->withdrawalAmount,
                'notes' => $this->withdrawalNotes,
                'paid_at' => Carbon::createFromFormat('d.m.Y', $this->withdrawalDate),
            ]);

            // Increase the balance and original amount
            $loan->update([
                'current_balance' => $loan->current_balance + $this->withdrawalAmount,
                'original_amount' => $loan->original_amount + $this->withdrawalAmount,
            ]);
        });

        session()->flash('message', __('app.withdrawal_added_successfully'));

        $this->closeWithdrawalModal();
    }

    public function openEditModal(int $loanId): void
    {
        $loan = SelfLoan::find($loanId);

        if ($loan) {
            $this->selectedLoanId = $loanId;
            $this->editName = $loan->name;
            $this->editDescription = $loan->description ?? '';
            $this->editOriginalAmount = (string) $loan->original_amount;
            $this->showEditModal = true;
        }
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->selectedLoanId = 0;
        $this->editName = '';
        $this->editDescription = '';
        $this->editOriginalAmount = '';
        $this->resetValidation();
    }

    public function updateLoan(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editDescription' => 'nullable|string|max:500',
            'editOriginalAmount' => 'required|numeric|min:0.01',
        ]);

        $loan = SelfLoan::find($this->selectedLoanId);

        if ($loan) {
            $loan->update([
                'name' => $this->editName,
                'description' => $this->editDescription,
                'original_amount' => $this->editOriginalAmount,
            ]);

            session()->flash('message', __('app.self_loan_updated_successfully'));
        }

        $this->closeEditModal();
    }

    public function confirmDelete(int $id, string $name): void
    {
        $this->loanToDelete = $id;
        $this->loanNameToDelete = $name;
        $this->showDeleteModal = true;
    }

    public function deleteLoan(): void
    {
        if ($this->loanToDelete) {
            $loan = SelfLoan::find($this->loanToDelete);

            if ($loan) {
                $loan->delete();
                session()->flash('message', __('app.self_loan_deleted'));
            }
        }

        $this->showDeleteModal = false;
        $this->loanToDelete = null;
        $this->loanNameToDelete = '';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.self-loans.overview');
    }
}
