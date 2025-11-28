<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Debt;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InterestInsightsService
{
    public function __construct(
        protected DebtCalculationService $calculationService,
        protected PayoffSettingsService $settingsService
    ) {}

    /**
     * Get interest breakdown for a period.
     *
     * @param  string  $period  'month' or 'all'
     * @return array{total_paid: float, interest_paid: float, principal_paid: float, interest_percentage: float}
     */
    public function getInterestBreakdown(string $period = 'month'): array
    {
        $query = Payment::query()
            ->where('is_reconciliation_adjustment', false);

        if ($period === 'month') {
            $currentMonth = Carbon::now()->format('Y-m');
            $query->where('payment_month', $currentMonth);
        }

        $totals = $query->selectRaw('
            COALESCE(SUM(actual_amount), 0) as total_paid,
            COALESCE(SUM(interest_paid), 0) as interest_paid,
            COALESCE(SUM(principal_paid), 0) as principal_paid
        ')->first();

        $totalPaid = (float) ($totals->total_paid ?? 0);
        $interestPaid = (float) ($totals->interest_paid ?? 0);
        $principalPaid = (float) ($totals->principal_paid ?? 0);

        // Calculate interest percentage (handle division by zero)
        $interestPercentage = $totalPaid > 0
            ? round(($interestPaid / $totalPaid) * 100, 1)
            : 0.0;

        return [
            'total_paid' => $totalPaid,
            'interest_paid' => $interestPaid,
            'principal_paid' => $principalPaid,
            'interest_percentage' => $interestPercentage,
        ];
    }

    /**
     * Get per-debt interest breakdown, ordered by interest_paid descending.
     *
     * @param  string  $period  'month' or 'all'
     * @return Collection<int, array{debt_id: int, debt_name: string, interest_paid: float, principal_paid: float, total_paid: float}>
     */
    public function getPerDebtInterestBreakdown(string $period = 'month'): Collection
    {
        $debts = Debt::all();

        if ($debts->isEmpty()) {
            return collect();
        }

        // Build payment query with period filter (exclude reconciliation adjustments)
        $paymentQuery = Payment::query()
            ->where('is_reconciliation_adjustment', false);

        if ($period === 'month') {
            $currentMonth = Carbon::now()->format('Y-m');
            $paymentQuery->where('payment_month', $currentMonth);
        }

        // Get payment aggregates by debt_id
        $paymentsByDebt = $paymentQuery
            ->selectRaw('
                debt_id,
                COALESCE(SUM(actual_amount), 0) as total_paid,
                COALESCE(SUM(interest_paid), 0) as interest_paid,
                COALESCE(SUM(principal_paid), 0) as principal_paid
            ')
            ->groupBy('debt_id')
            ->get()
            ->keyBy('debt_id');

        // Map debts to their payment breakdowns
        $result = $debts->map(function (Debt $debt) use ($paymentsByDebt) {
            $payments = $paymentsByDebt->get($debt->id);

            return [
                'debt_id' => $debt->id,
                'debt_name' => $debt->name,
                'interest_paid' => (float) ($payments->interest_paid ?? 0),
                'principal_paid' => (float) ($payments->principal_paid ?? 0),
                'total_paid' => (float) ($payments->total_paid ?? 0),
            ];
        });

        // Sort by interest_paid descending (most expensive first)
        return $result->sortByDesc('interest_paid')->values();
    }

    /**
     * Calculate what-if scenarios for extra payment increases.
     *
     * @param  array<int>  $increments  Extra amounts to simulate (e.g., [500, 1000, 2000])
     * @return array<int, array{increment: int, total_interest: float, months: int, savings: float, months_saved: int}>
     */
    public function getExtraPaymentScenarios(array $increments = [500, 1000, 2000]): array
    {
        $debts = Debt::all();

        if ($debts->isEmpty()) {
            return array_map(fn (int $increment) => [
                'increment' => $increment,
                'total_interest' => 0.0,
                'months' => 0,
                'savings' => 0.0,
                'months_saved' => 0,
            ], $increments);
        }

        $currentExtra = $this->settingsService->getExtraPayment();
        $strategy = $this->settingsService->getStrategy();

        // Calculate current baseline
        $currentSchedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $currentExtra,
            $strategy
        );
        $currentInterest = $currentSchedule['totalInterest'];
        $currentMonths = $currentSchedule['months'];

        $scenarios = [];

        foreach ($increments as $increment) {
            // Calculate schedule with increased extra payment
            $newExtra = $currentExtra + $increment;
            $newSchedule = $this->calculationService->generatePaymentSchedule(
                $debts,
                $newExtra,
                $strategy
            );

            $newInterest = $newSchedule['totalInterest'];
            $newMonths = $newSchedule['months'];

            $scenarios[] = [
                'increment' => $increment,
                'total_interest' => round($newInterest, 2),
                'months' => $newMonths,
                'savings' => round($currentInterest - $newInterest, 2),
                'months_saved' => $currentMonths - $newMonths,
            ];
        }

        return $scenarios;
    }
}
