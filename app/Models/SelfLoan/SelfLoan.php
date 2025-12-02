<?php

namespace App\Models\SelfLoan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SelfLoan extends Model
{
    /** @use HasFactory<\Database\Factories\SelfLoan\SelfLoanFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'original_amount',
        'current_balance',
        'ynab_account_id',
        'ynab_category_id',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'float',
            'current_balance' => 'float',
        ];
    }

    /**
     * @return HasMany<\App\Models\SelfLoan\SelfLoanRepayment, $this>
     */
    public function repayments(): HasMany
    {
        return $this->hasMany(SelfLoanRepayment::class);
    }

    public function isPaidOff(): bool
    {
        return $this->current_balance <= 0;
    }

    public function getTotalRepaidAmount(): float
    {
        return $this->original_amount - $this->current_balance;
    }

    public function getProgressPercentage(): float
    {
        if ($this->original_amount <= 0) {
            return 0;
        }

        return round(($this->getTotalRepaidAmount() / $this->original_amount) * 100, 1);
    }

    /**
     * Check if this loan is linked to a YNAB account.
     */
    public function isLinkedToYnabAccount(): bool
    {
        return $this->ynab_account_id !== null;
    }

    /**
     * Check if this loan is linked to a YNAB category.
     */
    public function isLinkedToYnabCategory(): bool
    {
        return $this->ynab_category_id !== null;
    }

    /**
     * Check if this loan has any YNAB connection.
     */
    public function hasYnabConnection(): bool
    {
        return $this->isLinkedToYnabAccount() || $this->isLinkedToYnabCategory();
    }
}
