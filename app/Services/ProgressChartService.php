<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Debt;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for calculating progress chart data.
 *
 * This service handles the calculation of progress chart data,
 * including historical balance tracking and dataset generation.
 */
class ProgressChartService
{
    /**
     * Color palette for individual debt lines in the chart.
     *
     * @var array<string>
     */
    private const COLOR_PALETTE = [
        '#10B981', // green
        '#F59E0B', // amber
        '#EF4444', // red
        '#8B5CF6', // purple
        '#EC4899', // pink
        '#06B6D4', // cyan
        '#84CC16', // lime
    ];

    /**
     * Color for the total debt line.
     */
    private const TOTAL_LINE_COLOR = '#3B82F6';

    public function __construct(
        protected DebtCacheService $debtCacheService
    ) {}

    /**
     * Calculate progress chart data showing debt balance history.
     *
     * @return array{labels: array<string>, datasets: array<array<string, mixed>>}
     */
    public function calculateProgressData(): array
    {
        $debts = $this->debtCacheService->getAllWithPayments();

        if ($debts->isEmpty()) {
            return ['labels' => [], 'datasets' => []];
        }

        $dateRange = $this->calculateDateRange($debts);

        if ($dateRange === null) {
            return ['labels' => [], 'datasets' => []];
        }

        [$startDate, $now] = $dateRange;
        $paymentsByDebtAndMonth = $this->groupPaymentsByDebtAndMonth($debts);
        $debtData = $this->initializeDebtDataStructures($debts);
        $chartData = $this->calculateMonthlyDataPoints($debts, $startDate, $now, $paymentsByDebtAndMonth, $debtData);

        return $this->buildDatasetsArray($chartData['labels'], $chartData['totalData'], $chartData['debtData']);
    }

    /**
     * Calculate the date range for the chart based on earliest payment/debt.
     *
     * @param  Collection<int, Debt>  $debts
     * @return array{0: Carbon, 1: Carbon}|null
     */
    protected function calculateDateRange(Collection $debts): ?array
    {
        $earliestPayment = Payment::orderBy('payment_date')->first();
        $earliestDebt = Debt::orderBy('created_at')->first();

        if (! $earliestDebt) {
            return null;
        }

        $startDate = $earliestPayment
            ? min($earliestPayment->payment_date, $earliestDebt->created_at)
            : $earliestDebt->created_at;

        return [Carbon::parse($startDate)->startOfMonth(), Carbon::now()];
    }

    /**
     * Group payments by debt ID and month for efficient lookup.
     *
     * @param  Collection<int, Debt>  $debts
     * @return array<int, Collection<string, float>>
     */
    protected function groupPaymentsByDebtAndMonth(Collection $debts): array
    {
        $paymentsByDebtAndMonth = [];

        foreach ($debts as $debt) {
            $paymentsByDebtAndMonth[$debt->id] = $debt->payments
                ->groupBy(fn ($payment) => Carbon::parse($payment->payment_date)->format('Y-m'))
                ->map(fn ($monthPayments) => $monthPayments->sum('principal_paid'));
        }

        return $paymentsByDebtAndMonth;
    }

    /**
     * Initialize the debt data structures for chart building.
     *
     * @param  Collection<int, Debt>  $debts
     * @return array<string, array<string, mixed>>
     */
    protected function initializeDebtDataStructures(Collection $debts): array
    {
        $debtData = [];

        foreach ($debts as $index => $debt) {
            $debtData[$debt->name] = [
                'label' => $debt->name,
                'data' => [],
                'borderColor' => self::COLOR_PALETTE[$index % count(self::COLOR_PALETTE)],
            ];
        }

        return $debtData;
    }

    /**
     * Calculate monthly data points for all debts.
     *
     * @param  Collection<int, Debt>  $debts
     * @param  array<int, Collection<string, float>>  $paymentsByDebtAndMonth
     * @param  array<string, array<string, mixed>>  $debtData
     * @return array{labels: array<string>, totalData: array<float>, debtData: array<string, array<string, mixed>>}
     */
    protected function calculateMonthlyDataPoints(
        Collection $debts,
        Carbon $startDate,
        Carbon $now,
        array $paymentsByDebtAndMonth,
        array $debtData
    ): array {
        $labels = [];
        $totalData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($now)) {
            $labels[] = $this->formatMonthLabel($currentDate);
            $currentMonthKey = $currentDate->format('Y-m');

            $totalBalance = 0;

            foreach ($debts as $debt) {
                $remainingBalance = $this->calculateDebtBalanceForMonth(
                    $debt,
                    $currentMonthKey,
                    $paymentsByDebtAndMonth[$debt->id] ?? collect()
                );

                $debtData[$debt->name]['data'][] = round($remainingBalance, 2);
                $totalBalance += $remainingBalance;
            }

            $totalData[] = round($totalBalance, 2);
            $currentDate->addMonth();
        }

        return [
            'labels' => $labels,
            'totalData' => $totalData,
            'debtData' => $debtData,
        ];
    }

    /**
     * Format a month label for the chart.
     */
    protected function formatMonthLabel(Carbon $date): string
    {
        $clonedDate = clone $date;
        $clonedDate->locale(app()->getLocale());

        return $clonedDate->isoFormat('MMM YYYY');
    }

    /**
     * Calculate a debt's remaining balance for a specific month.
     *
     * @param  Collection<string, float>  $debtPayments
     */
    protected function calculateDebtBalanceForMonth(
        Debt $debt,
        string $currentMonthKey,
        Collection $debtPayments
    ): float {
        $cumulativePaid = 0;

        foreach ($debtPayments as $monthKey => $amount) {
            if ($monthKey <= $currentMonthKey) {
                $cumulativePaid += $amount;
            }
        }

        $originalBalance = $debt->original_balance ?? $debt->balance;

        return max(0, $originalBalance - $cumulativePaid);
    }

    /**
     * Build the final datasets array for the chart.
     *
     * @param  array<string>  $labels
     * @param  array<float>  $totalData
     * @param  array<string, array<string, mixed>>  $debtData
     * @return array{labels: array<string>, datasets: array<array<string, mixed>>}
     */
    protected function buildDatasetsArray(array $labels, array $totalData, array $debtData): array
    {
        $datasets = [
            [
                'label' => __('app.total_debt_balance'),
                'data' => $totalData,
                'borderColor' => self::TOTAL_LINE_COLOR,
                'isTotal' => true,
            ],
        ];

        foreach ($debtData as $data) {
            $datasets[] = $data;
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * Get the color palette for debt lines.
     *
     * @return array<string>
     */
    public function getColorPalette(): array
    {
        return self::COLOR_PALETTE;
    }
}
