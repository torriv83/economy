<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $type
 * @property float $balance
 * @property float|null $original_balance
 * @property float $interest_rate
 * @property float|null $minimum_payment
 * @property int|null $custom_priority_order
 * @property int|null $due_day
 * @property string|null $ynab_account_id
 * @property \Carbon\Carbon|null $last_verified_at
 * @property \Carbon\Carbon|null $paid_off_at
 * @property bool $include_in_charts
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
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
        'last_verified_at',
        'paid_off_at',
        'include_in_charts',
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
            'last_verified_at' => 'datetime',
            'paid_off_at' => 'datetime',
            'include_in_charts' => 'boolean',
        ];
    }

    /**
     * Scope: only debts that are still active (not paid off).
     *
     * @param  Builder<Debt>  $query
     * @return Builder<Debt>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('paid_off_at');
    }

    /**
     * Scope: only debts that have been paid off and archived.
     *
     * @param  Builder<Debt>  $query
     * @return Builder<Debt>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('paid_off_at');
    }

    /**
     * Whether this debt has been paid off and archived.
     */
    public function isArchived(): bool
    {
        return $this->paid_off_at !== null;
    }

    /**
     * Calculate the regulatory minimum payment for the debt based on its type.
     *
     * For forbrukslån, the payment must be high enough to pay off the debt
     * within 5 years (60 months) according to Utlånsforskriften.
     */
    public function calculateMinimumPaymentForType(): float
    {
        if ($this->type === 'kredittkort') {
            // Credit card: Percentage of current balance or minimum amount, whichever is higher
            $percentage = config('debt.minimum_payment.kredittkort.percentage');
            $minimumAmount = config('debt.minimum_payment.kredittkort.minimum_amount');

            return max($this->balance * $percentage, $minimumAmount);
        }

        // Forbrukslån: Calculate payment that pays off debt in 60 months with interest
        // Using amortization formula: P = (r * PV) / (1 - (1 + r)^-n)
        $monthlyRate = ($this->interest_rate / 100) / 12;
        $numberOfMonths = config('debt.minimum_payment.forbrukslån.payoff_months', 60);

        if ($monthlyRate == 0) {
            // If no interest, simply divide balance by number of months
            return round($this->balance / $numberOfMonths, 2);
        }

        $payment = ($monthlyRate * $this->balance) / (1 - pow(1 + $monthlyRate, -$numberOfMonths));

        return round($payment, 2);
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

    /**
     * @return HasMany<\App\Models\Payment, $this>
     */
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
