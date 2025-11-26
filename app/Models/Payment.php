<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $debt_id
 * @property float|null $planned_amount
 * @property float $actual_amount
 * @property float|null $interest_paid
 * @property float|null $principal_paid
 * @property \Carbon\Carbon $payment_date
 * @property int|null $month_number
 * @property string|null $payment_month
 * @property string|null $notes
 * @property bool $is_reconciliation_adjustment
 * @property string|null $ynab_transaction_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'debt_id',
        'planned_amount',
        'actual_amount',
        'interest_paid',
        'principal_paid',
        'payment_date',
        'month_number',
        'payment_month',
        'notes',
        'is_reconciliation_adjustment',
        'ynab_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'planned_amount' => 'float',
            'actual_amount' => 'float',
            'interest_paid' => 'float',
            'principal_paid' => 'float',
            'payment_date' => 'date',
            'month_number' => 'integer',
            'is_reconciliation_adjustment' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<\App\Models\Debt, $this>
     */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /**
     * Scope to get only reconciliation adjustments
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeReconciliations(Builder $query): Builder
    {
        return $query->where('is_reconciliation_adjustment', true);
    }

    /**
     * Scope to get payments for a specific debt
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeForDebt(Builder $query, int $debtId): Builder
    {
        return $query->where('debt_id', $debtId);
    }
}
