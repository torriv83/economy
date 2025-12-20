<?php

namespace App\Models\SelfLoan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $self_loan_id
 * @property float $amount
 * @property string|null $notes
 * @property Carbon $paid_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
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

    /**
     * @return BelongsTo<\App\Models\SelfLoan\SelfLoan, $this>
     */
    public function selfLoan(): BelongsTo
    {
        return $this->belongsTo(SelfLoan::class);
    }
}
