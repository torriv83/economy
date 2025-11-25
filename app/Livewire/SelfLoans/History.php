<?php

declare(strict_types=1);

namespace App\Livewire\SelfLoans;

use App\Models\SelfLoan\SelfLoan;
use App\Models\SelfLoan\SelfLoanRepayment;
use Livewire\Component;

class History extends Component
{
    public ?int $selectedLoanId = null;

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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.self-loans.history');
    }
}
