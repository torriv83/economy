<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'debt_id',
        'planned_amount',
        'actual_amount',
        'payment_date',
        'month_number',
        'payment_month',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'planned_amount' => 'float',
            'actual_amount' => 'float',
            'payment_date' => 'date',
            'month_number' => 'integer',
        ];
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }
}
