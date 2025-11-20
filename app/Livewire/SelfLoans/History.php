<?php

declare(strict_types=1);

namespace App\Livewire\SelfLoans;

use App\Models\SelfLoan\SelfLoan;
use App\Models\SelfLoan\SelfLoanRepayment;
use Livewire\Component;

class History extends Component
{
    public ?int $selectedLoanId = null;

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

    public function getPaidOffLoansProperty(): array
    {
        $loans = SelfLoan::where('current_balance', '<=', 0)->latest()->get();

        return $loans->map(function ($loan) {
            return [
                'id' => $loan->id,
                'name' => $loan->name,
                'description' => $loan->description,
                'original_amount' => $loan->original_amount,
                'created_at' => $loan->created_at->locale('nb')->translatedFormat('d. F Y'),
                'repayments' => $loan->repayments()->latest('paid_at')->get()->map(function ($repayment) {
                    return [
                        'amount' => $repayment->amount,
                        'notes' => $repayment->notes,
                        'paid_at' => $repayment->paid_at->locale('nb')->translatedFormat('d. F Y H:i'),
                    ];
                })->toArray(),
            ];
        })->values()->toArray();
    }

    public function getAllRepaymentsProperty(): array
    {
        $query = SelfLoanRepayment::with('selfLoan')
            ->latest('paid_at');

        if ($this->selectedLoanId !== null) {
            $query->where('self_loan_id', $this->selectedLoanId);
        }

        $repayments = $query->get();

        return $repayments->map(function ($repayment) {
            return [
                'id' => $repayment->id,
                'loan_name' => $repayment->selfLoan->name,
                'amount' => $repayment->amount,
                'notes' => $repayment->notes,
                'paid_at' => $repayment->paid_at->locale('nb')->translatedFormat('d. F Y H:i'),
            ];
        })->toArray();
    }

    public function clearFilter(): void
    {
        $this->selectedLoanId = null;
    }

    public function render()
    {
        return view('livewire.self-loans.history');
    }
}
