<?php

declare(strict_types=1);

namespace App\Livewire\SelfLoans;

use App\Models\SelfLoan\SelfLoan;
use App\Services\SettingsService;
use App\Services\YnabService;
use Livewire\Component;

/**
 * @property-read bool $isYnabConfigured
 * @property-read array<int, array{id: string, name: string, balance: float}> $ynabAccounts
 * @property-read array<int, array{id: string, name: string, group_name: string}> $ynabCategories
 */
class CreateSelfLoan extends Component
{
    public string $name = '';

    public string $description = '';

    public string $amount = '';

    public string $ynabConnectionType = 'none';

    public string $ynabAccountId = '';

    public string $ynabCategoryId = '';

    public function getIsYnabConfiguredProperty(): bool
    {
        return app(SettingsService::class)->isYnabConfigured();
    }

    /**
     * @return array<int, array{id: string, name: string, balance: float}>
     */
    public function getYnabAccountsProperty(): array
    {
        if (! $this->isYnabConfigured) {
            return [];
        }

        try {
            $ynabService = app(YnabService::class);

            return $ynabService->fetchSavingsAccounts()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @return array<int, array{id: string, name: string, group_name: string}>
     */
    public function getYnabCategoriesProperty(): array
    {
        if (! $this->isYnabConfigured) {
            return [];
        }

        try {
            $ynabService = app(YnabService::class);

            return $ynabService->fetchCategories()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'ynabConnectionType' => 'in:none,account,category',
            'ynabAccountId' => 'nullable|string',
            'ynabCategoryId' => 'nullable|string',
        ];
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function messages(): array
    {
        return [];
    }

    public function createLoan(): void
    {
        $this->validate();

        $ynabAccountId = null;
        $ynabCategoryId = null;

        if ($this->ynabConnectionType === 'account' && $this->ynabAccountId) {
            $ynabAccountId = $this->ynabAccountId;
        } elseif ($this->ynabConnectionType === 'category' && $this->ynabCategoryId) {
            $ynabCategoryId = $this->ynabCategoryId;
        }

        SelfLoan::create([
            'name' => $this->name,
            'description' => $this->description,
            'original_amount' => $this->amount,
            'current_balance' => $this->amount,
            'ynab_account_id' => $ynabAccountId,
            'ynab_category_id' => $ynabCategoryId,
        ]);

        session()->flash('message', __('app.self_loan_created'));

        $this->dispatch('loanCreated');

        $this->reset();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.self-loans.create-self-loan');
    }
}
