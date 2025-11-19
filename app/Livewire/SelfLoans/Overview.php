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

    public bool $showRepaymentModal = false;

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
        $this->showRepaymentModal = true;
    }

    public function closeRepaymentModal(): void
    {
        $this->showRepaymentModal = false;
        $this->selectedLoanId = 0;
        $this->repaymentAmount = 0;
        $this->repaymentNotes = '';
        $this->resetValidation();
    }

    public function addRepayment(): void
    {
        $this->validate([
            'repaymentAmount' => 'required|numeric|min:0.01',
            'repaymentNotes' => 'nullable|string|max:500',
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
            'paid_at' => now(),
        ]);

        $loan->update([
            'current_balance' => $loan->current_balance - $this->repaymentAmount,
        ]);

        session()->flash('message', 'Repayment added successfully.');

        $this->closeRepaymentModal();
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
