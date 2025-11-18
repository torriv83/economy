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
        'type',
        'balance',
        'original_balance',
        'interest_rate',
        'minimum_payment',
        'custom_priority_order',
        'due_day',
        'ynab_account_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'balance' => 'float',
            'original_balance' => 'float',
            'interest_rate' => 'float',
            'minimum_payment' => 'float',
            'custom_priority_order' => 'integer',
            'due_day' => 'integer',
        ];
    }

    /**
     * Calculate the regulatory minimum payment for the debt based on its type.
     */
    public function calculateMinimumPaymentForType(): float
    {
        if ($this->type === 'kredittkort') {
            // Credit card: 3% of current balance or 300 kr, whichever is higher
            return max($this->balance * 0.03, 300);
        }

        // Forbrukslån: Monthly interest + small buffer (10%)
        // Payment must be greater than monthly interest to prevent debt growth
        $monthlyInterest = ($this->balance * ($this->interest_rate / 100)) / 12;

        return round($monthlyInterest * 1.1, 2); // 10% buffer above interest
    }

    /**
     * Check if the current minimum payment meets Norwegian regulatory requirements.
     */
    public function isMinimumPaymentCompliant(): bool
    {
        $requiredMinimum = $this->calculateMinimumPaymentForType();

        return $this->minimum_payment >= $requiredMinimum;
    }

    /**
     * Get a warning message if the minimum payment is not compliant.
     */
    public function getMinimumPaymentWarning(): ?string
    {
        if ($this->isMinimumPaymentCompliant()) {
            return null;
        }

        $requiredMinimum = number_format($this->calculateMinimumPaymentForType(), 0, ',', ' ');

        return __('app.non_compliant_minimum', ['amount' => $requiredMinimum]);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if this debt has a custom priority order set.
     */
    public function hasCustomPriority(): bool
    {
        return $this->custom_priority_order !== null;
    }

    /**
     * Check if all debts have custom priority orders set.
     */
    public static function allHaveCustomPriority(): bool
    {
        return static::whereNull('custom_priority_order')->count() === 0;
    }
}
