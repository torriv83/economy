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
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'float',
            'current_balance' => 'float',
        ];
    }

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
}
