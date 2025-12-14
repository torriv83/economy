<?php

declare(strict_types=1);

namespace App\Livewire\Debts;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use App\Services\PayoffSettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class DebtDetail extends Component
{
    public Debt $debt;

    public bool $embedded = false;

    public bool $isLoading = true;

    public ?float $whatIfAmount = 500.0;

    public string $whatIfType = 'monthly'; // 'monthly' or 'one_time'

    /** @var array<string, mixed>|null */
    public ?array $whatIfResult = null;

    protected DebtCalculationService $calculationService;

    protected PayoffSettingsService $settingsService;

    public function boot(DebtCalculationService $calculationService, PayoffSettingsService $settingsService): void
    {
        $this->calculationService = $calculationService;
        $this->settingsService = $settingsService;
    }

    public function mount(Debt $debt, bool $embedded = false): void
    {
        $this->debt = $debt->load('payments');
        $this->embedded = $embedded;

        // Pre-calculate what-if with default amount
        $this->calculateWhatIf();
    }

    public function updatedWhatIfAmount(): void
    {
        $this->recalculateWhatIf();
    }

    public function updatedWhatIfType(): void
    {
        $this->recalculateWhatIf();
    }

    protected function recalculateWhatIf(): void
    {
        if ($this->whatIfAmount !== null && $this->whatIfAmount > 0) {
            $this->calculateWhatIf();
        } else {
            $this->whatIfResult = null;
        }
    }

    public function calculateWhatIf(): void
    {
        $debts = Debt::with('payments')->get();
        $extraPayment = $this->settingsService->getExtraPayment();
        $strategy = $this->settingsService->getStrategy();
        $amount = $this->whatIfAmount ?? 0;

        // Calculate current scenario
        $currentSchedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $extraPayment,
            $strategy
        );

        if ($this->whatIfType === 'monthly') {
            // Monthly: Add extra payment specifically to THIS debt (not global pool)
            $modifiedDebts = $debts->map(function (Debt $debt) use ($amount) {
                if ($debt->id === $this->debt->id) {
                    $clone = $debt->replicate();
                    $clone->id = $debt->id;
                    // Increase minimum payment so this debt gets more each month
                    $clone->minimum_payment = ($debt->minimum_payment ?? 0) + $amount;
                    $clone->setRelation('payments', $debt->payments);

                    return $clone;
                }

                return $debt;
            });

            $whatIfSchedule = $this->calculationService->generatePaymentSchedule(
                $modifiedDebts,
                $extraPayment,
                $strategy
            );
        } else {
            // One-time: Reduce this debt's balance first, then calculate
            // Start fresh from reduced current balance (no payment history replay)
            $modifiedDebts = $debts->map(function (Debt $debt) use ($amount) {
                if ($debt->id === $this->debt->id) {
                    $reducedBalance = max(0, $debt->balance - $amount);
                    $clone = $debt->replicate();
                    $clone->id = $debt->id;
                    $clone->balance = $reducedBalance;
                    // Set original_balance to match so calculation starts fresh
                    $clone->original_balance = $reducedBalance;
                    // Clear payments to prevent replay from old original_balance
                    $clone->setRelation('payments', collect());

                    return $clone;
                }

                return $debt;
            });

            $whatIfSchedule = $this->calculationService->generatePaymentSchedule(
                $modifiedDebts,
                $extraPayment,
                $strategy
            );
        }

        // Find when this specific debt is paid off in each scenario
        // For what-if, we need to know the modified starting balance to detect immediate payoff
        $whatIfStartingBalance = $this->whatIfType === 'one_time'
            ? max(0, $this->debt->balance - $amount)
            : $this->debt->balance;

        $currentDebtPayoff = $this->findDebtPayoffMonth($currentSchedule['schedule'], $this->debt->name, $this->debt->balance);
        $whatIfDebtPayoff = $this->findDebtPayoffMonth($whatIfSchedule['schedule'], $this->debt->name, $whatIfStartingBalance);

        $monthsSaved = $currentDebtPayoff - $whatIfDebtPayoff;

        // Calculate interest saved for THIS specific debt only
        $currentDebtInterest = $this->calculateDebtInterest($currentSchedule['schedule'], $this->debt->name);
        $whatIfDebtInterest = $this->calculateDebtInterest($whatIfSchedule['schedule'], $this->debt->name);
        $interestSaved = $currentDebtInterest - $whatIfDebtInterest;

        $this->whatIfResult = [
            'current_months' => $currentDebtPayoff,
            'new_months' => $whatIfDebtPayoff,
            'months_saved' => max(0, $monthsSaved),
            'interest_saved' => max(0, $interestSaved),
            'current_payoff_date' => now()->addMonths($currentDebtPayoff)->format('Y-m-d'),
            'new_payoff_date' => now()->addMonths($whatIfDebtPayoff)->format('Y-m-d'),
        ];
    }

    /**
     * Calculate total interest paid for a specific debt across all months.
     *
     * @param  array<int, array<string, mixed>>  $schedule
     */
    protected function calculateDebtInterest(array $schedule, string $debtName): float
    {
        $totalInterest = 0.0;

        foreach ($schedule as $month) {
            foreach ($month['payments'] as $payment) {
                if ($payment['name'] === $debtName) {
                    $totalInterest += $payment['interest'] ?? 0;
                }
            }
        }

        return $totalInterest;
    }

    /**
     * Find the month number when a specific debt is paid off.
     *
     * @param  array<int, array<string, mixed>>  $schedule
     * @param  float|null  $startingBalance  The debt's balance at start of schedule (for detecting immediate payoff)
     */
    protected function findDebtPayoffMonth(array $schedule, string $debtName, ?float $startingBalance = null): int
    {
        // If starting balance is 0 or near 0, debt is already paid off (month 0)
        if ($startingBalance !== null && $startingBalance <= 0.01) {
            return 0;
        }

        // Check if debt appears in schedule at all
        $debtAppearsInSchedule = false;

        foreach ($schedule as $month) {
            foreach ($month['payments'] as $payment) {
                if ($payment['name'] === $debtName) {
                    $debtAppearsInSchedule = true;
                    if ($payment['remaining'] <= 0.01) {
                        return $month['month'];
                    }
                }
            }
        }

        // If debt never appeared in schedule and we know it has a starting balance,
        // it might have been paid off before schedule started (e.g., one-time payment covered it)
        if (! $debtAppearsInSchedule && $startingBalance !== null) {
            return 0;
        }

        // If not found, return the last month
        return count($schedule);
    }

    /**
     * Get recent payments for this debt.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment>
     */
    public function getRecentPaymentsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->debt->payments()
            ->orderByDesc('payment_date')
            ->limit(5)
            ->get();
    }

    /**
     * Get total paid amount for this debt.
     */
    public function getTotalPaidProperty(): float
    {
        return $this->debt->payments()->sum('actual_amount');
    }

    /**
     * Get the debt type badge color.
     */
    public function getTypeBadgeColorProperty(): string
    {
        return match ($this->debt->type) {
            'kredittkort' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            'forbrukslån' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    public function loadData(): void
    {
        $this->isLoading = false;
    }

    public function render(): View
    {
        return view('livewire.debts.debt-detail');
    }
}
