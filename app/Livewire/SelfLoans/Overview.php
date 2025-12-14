<?php

declare(strict_types=1);

namespace App\Livewire\SelfLoans;

use App\Livewire\Concerns\HasConsistentFlashMessages;
use App\Livewire\Concerns\HasDeleteConfirmation;
use App\Models\SelfLoan\SelfLoan;
use App\Models\SelfLoan\SelfLoanRepayment;
use App\Services\SettingsService;
use App\Services\YnabService;
use App\Support\DateFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Overview extends Component
{
    use HasConsistentFlashMessages;
    use HasDeleteConfirmation;

    private YnabService $ynabService;

    private SettingsService $settingsService;

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

    public string $editYnabConnectionType = 'none';

    public string $editYnabAccountId = '';

    public string $editYnabCategoryId = '';

    public bool $showEditModal = false;

    public bool $isLoading = true;

    public function loadData(): void
    {
        $this->isLoading = false;
    }

    public function boot(
        YnabService $ynabService,
        SettingsService $settingsService
    ): void {
        $this->ynabService = $ynabService;
        $this->settingsService = $settingsService;
    }

    /**
     * @return array<int, array{id: int, name: string, description: string|null, original_amount: float, current_balance: float, total_repaid: float, progress_percentage: float, created_at: string, ynab_account_id: string|null, ynab_category_id: string|null, ynab_account_name: string|null, ynab_category_name: string|null, ynab_available: float|null}>
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
                'ynab_account_id' => $loan->ynab_account_id,
                'ynab_category_id' => $loan->ynab_category_id,
                'ynab_account_name' => $this->getYnabAccountName($loan->ynab_account_id),
                'ynab_category_name' => $this->getYnabCategoryName($loan->ynab_category_id),
                'ynab_available' => $this->getYnabAvailable($loan->ynab_account_id, $loan->ynab_category_id),
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

    public function getIsYnabConfiguredProperty(): bool
    {
        return $this->settingsService->isYnabConfigured();
    }

    /**
     * @return array<int, array{id: string, name: string, balance: float}>
     */
    public function getYnabAccountsProperty(): array
    {
        if (! $this->getIsYnabConfiguredProperty()) {
            return [];
        }

        try {
            return $this->ynabService->fetchSavingsAccounts()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @return array<int, array{id: string, name: string, group_name: string}>
     */
    public function getYnabCategoriesProperty(): array
    {
        if (! $this->getIsYnabConfiguredProperty()) {
            return [];
        }

        try {
            return $this->ynabService->fetchCategories()->toArray();
        } catch (\Exception $e) {
            return [];
        }
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
        $this->repaymentDate = DateFormatter::todayNorwegian();
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
                'paid_at' => Carbon::createFromFormat(DateFormatter::NORWEGIAN_FORMAT, $this->repaymentDate),
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
        $this->withdrawalDate = DateFormatter::todayNorwegian();
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
                'paid_at' => Carbon::createFromFormat(DateFormatter::NORWEGIAN_FORMAT, $this->withdrawalDate),
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

            // Load YNAB connection
            if ($loan->ynab_account_id) {
                $this->editYnabConnectionType = 'account';
                $this->editYnabAccountId = $loan->ynab_account_id;
                $this->editYnabCategoryId = '';
            } elseif ($loan->ynab_category_id) {
                $this->editYnabConnectionType = 'category';
                $this->editYnabCategoryId = $loan->ynab_category_id;
                $this->editYnabAccountId = '';
            } else {
                $this->editYnabConnectionType = 'none';
                $this->editYnabAccountId = '';
                $this->editYnabCategoryId = '';
            }

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
        $this->editYnabConnectionType = 'none';
        $this->editYnabAccountId = '';
        $this->editYnabCategoryId = '';
        $this->resetValidation();
    }

    public function updateLoan(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editDescription' => 'nullable|string|max:500',
            'editOriginalAmount' => 'required|numeric|min:0.01',
            'editYnabConnectionType' => 'in:none,account,category',
            'editYnabAccountId' => 'nullable|string',
            'editYnabCategoryId' => 'nullable|string',
        ]);

        $loan = SelfLoan::find($this->selectedLoanId);

        if ($loan) {
            $ynabAccountId = null;
            $ynabCategoryId = null;

            if ($this->editYnabConnectionType === 'account' && $this->editYnabAccountId) {
                $ynabAccountId = $this->editYnabAccountId;
            } elseif ($this->editYnabConnectionType === 'category' && $this->editYnabCategoryId) {
                $ynabCategoryId = $this->editYnabCategoryId;
            }

            $loan->update([
                'name' => $this->editName,
                'description' => $this->editDescription,
                'original_amount' => $this->editOriginalAmount,
                'ynab_account_id' => $ynabAccountId,
                'ynab_category_id' => $ynabCategoryId,
            ]);

            session()->flash('message', __('app.self_loan_updated_successfully'));
        }

        $this->closeEditModal();
    }

    protected function performDelete(int $id): void
    {
        $loan = SelfLoan::find($id);
        if ($loan) {
            $loan->delete();
            session()->flash('message', __('app.self_loan_deleted'));
        }
    }

    private function getYnabAccountName(?string $accountId): ?string
    {
        if (! $accountId || ! $this->settingsService->isYnabConfigured()) {
            return null;
        }

        try {
            $accounts = $this->ynabService->fetchSavingsAccounts();
            $account = $accounts->firstWhere('id', $accountId);

            return $account['name'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getYnabCategoryName(?string $categoryId): ?string
    {
        if (! $categoryId || ! $this->settingsService->isYnabConfigured()) {
            return null;
        }

        try {
            $categories = $this->ynabService->fetchCategories();
            $category = $categories->firstWhere('id', $categoryId);

            return $category['name'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getYnabAvailable(?string $accountId, ?string $categoryId): ?float
    {
        if (! $this->settingsService->isYnabConfigured()) {
            return null;
        }

        try {
            if ($accountId) {
                $accounts = $this->ynabService->fetchSavingsAccounts();
                $account = $accounts->firstWhere('id', $accountId);

                return $account['balance'] ?? null;
            }

            if ($categoryId) {
                $categories = $this->ynabService->fetchCategories();
                $category = $categories->firstWhere('id', $categoryId);

                return $category['balance'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.self-loans.overview');
    }
}
