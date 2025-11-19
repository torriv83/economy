<?php

namespace App\Models\SelfLoan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfLoanRepayment extends Model
{
    /** @use HasFactory<\Database\Factories\SelfLoan\SelfLoanRepaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'self_loan_id',
        'amount',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'paid_at' => 'datetime',
        ];
    }

    public function selfLoan(): BelongsTo
    {
        return $this->belongsTo(SelfLoan::class);
    }
}
