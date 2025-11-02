<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model
{
    /** @use HasFactory<\Database\Factories\DebtFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'balance',
        'original_balance',
        'interest_rate',
        'minimum_payment',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'float',
            'original_balance' => 'float',
            'interest_rate' => 'float',
            'minimum_payment' => 'float',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
