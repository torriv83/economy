<?php

declare(strict_types=1);

namespace App\Livewire\SelfLoans;

use App\Models\SelfLoan\SelfLoan;
use App\Models\SelfLoan\SelfLoanRepayment;
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

    public function openRepaymentModal(int $loanId): void
    {
        $this->selectedLoanId = $loanId;
        $this->repaymentAmount = 0;
        $this->repaymentNotes = '';
        $this->repaymentDate = now()->format('Y-m-d');
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
            'repaymentDate' => 'required|date|before_or_equal:today',
        ]);

        $loan = SelfLoan::findOrFail($this->selectedLoanId);

        if ($this->repaymentAmount > $loan->current_balance) {
            $this->addError('repaymentAmount', 'Repayment amount cannot exceed current balance.');

            return;
        }

        SelfLoanRepayment::create([
            'self_loan_id' => $loan->id,
            'amount' => $this->repaymentAmount,
            'notes' => $this->repaymentNotes,
            'paid_at' => \Carbon\Carbon::parse($this->repaymentDate),
        ]);

        $loan->update([
            'current_balance' => $loan->current_balance - $this->repaymentAmount,
        ]);

        session()->flash('message', 'Repayment added successfully.');

        $this->closeRepaymentModal();
    }

    public function openWithdrawalModal(int $loanId): void
    {
        $this->selectedLoanId = $loanId;
        $this->withdrawalAmount = 0;
        $this->withdrawalNotes = '';
        $this->withdrawalDate = now()->format('Y-m-d');
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
            'withdrawalDate' => 'required|date|before_or_equal:today',
        ]);

        $loan = SelfLoan::findOrFail($this->selectedLoanId);

        // Increase the balance and original amount
        $loan->update([
            'current_balance' => $loan->current_balance + $this->withdrawalAmount,
            'original_amount' => $loan->original_amount + $this->withdrawalAmount,
        ]);

        session()->flash('message', 'Withdrawal added successfully.');

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

            session()->flash('message', 'Self-loan updated successfully.');
        }

        $this->closeEditModal();
    }

    public function deleteLoan(int $id): void
    {
        $loan = SelfLoan::find($id);

        if ($loan) {
            $loan->delete();
            session()->flash('message', 'Self-loan deleted.');
        }
    }

    public function render()
    {
        return view('livewire.self-loans.overview');
    }
}
