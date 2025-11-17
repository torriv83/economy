<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * @property string|null $debtFreeDate
 * @property array $paymentSchedule
 * @property Collection $actualPayments
 * @property int $daysRemaining
 */
class PayoffCalendar extends Component
{
    public float $extraPayment = 2000;

    public string $strategy = 'avalanche';

    public int $currentMonth;

    public int $currentYear;

    protected DebtCalculationService $calculationService;

    public function boot(DebtCalculationService $service): void
    {
        $this->calculationService = $service;
    }

    public function mount(): void
    {
        $this->currentMonth = (int) now()->month;
        $this->currentYear = (int) now()->year;
    }

    public function rules(): array
    {
        return [
            'extraPayment' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'strategy' => ['required', 'in:snowball,avalanche,custom'],
        ];
    }

    public function messages(): array
    {
        return [
            'extraPayment.required' => __('validation.required', ['attribute' => __('app.extra_monthly_payment')]),
            'extraPayment.numeric' => __('validation.numeric', ['attribute' => __('app.extra_monthly_payment')]),
            'extraPayment.min' => __('validation.min.numeric', ['attribute' => __('app.extra_monthly_payment'), 'min' => 0]),
            'extraPayment.max' => __('validation.max.numeric', ['attribute' => __('app.extra_monthly_payment'), 'max' => '1 000 000']),
        ];
    }

    public function updatedExtraPayment(): void
    {
        $this->validate(['extraPayment' => $this->rules()['extraPayment']]);
    }

    public function updatedStrategy(): void
    {
        $this->validate(['strategy' => $this->rules()['strategy']]);
    }

    public function previousMonth(): void
    {
        $this->currentMonth--;
        if ($this->currentMonth < 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        }
    }

    public function nextMonth(): void
    {
        $this->currentMonth++;
        if ($this->currentMonth > 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        }
    }

    public function goToToday(): void
    {
        $this->currentMonth = (int) now()->month;
        $this->currentYear = (int) now()->year;
    }

    public function getAvailableYearsProperty(): array
    {
        $startYear = (int) now()->year;
        $endYear = $this->debtFreeDate
            ? (int) Carbon::parse($this->debtFreeDate)->year
            : $startYear + 5;

        return range($startYear, $endYear);
    }

    protected function getDebts(): Collection
    {
        return once(fn () => Debt::all());
    }

    public function getPaymentScheduleProperty(): array
    {
        $debts = $this->getDebts();

        if ($debts->isEmpty()) {
            return [
                'months' => 0,
                'totalInterest' => 0.00,
                'payoffDate' => now()->format('Y-m-d'),
                'schedule' => [],
            ];
        }

        return $this->calculationService->generatePaymentSchedule($debts, $this->extraPayment, $this->strategy);
    }

    public function getCalendarProperty(): array
    {
        $firstOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $daysInMonth = $firstOfMonth->daysInMonth;
        $startDayOfWeek = (int) $firstOfMonth->dayOfWeek;

        $monthStart = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $monthEnd = $firstOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $currentDate = $monthStart->copy();

        while ($currentDate->lte($monthEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'date' => $currentDate->copy(),
                    'isCurrentMonth' => $currentDate->month === $this->currentMonth,
                    'isToday' => $currentDate->isToday(),
                ];
                $currentDate->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    public function getActualPaymentsProperty(): Collection
    {
        $monthStart = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        return Payment::whereBetween('payment_date', [$monthStart, $monthEnd])
            ->with('debt')
            ->where('is_reconciliation_adjustment', false)
            ->get();
    }

    public function getPaymentEventsProperty(): array
    {
        $events = [];
        $schedule = $this->paymentSchedule;
        $actualPayments = $this->actualPayments;

        if (empty($schedule['schedule'])) {
            return [];
        }

        // Group actual payments by debt_id and payment_month for quick lookup
        $actualPaymentsByDebtAndMonth = $actualPayments->groupBy(function ($payment) {
            return $payment->debt_id.'_'.$payment->payment_month;
        })->map(fn ($group) => $group->first());

        // Only process the current month's schedule
        $currentMonthKey = Carbon::create($this->currentYear, $this->currentMonth, 1)->format('Y-m');

        foreach ($schedule['schedule'] as $monthData) {
            $baseDate = Carbon::parse($monthData['date']);
            $scheduleMonthKey = $baseDate->format('Y-m');

            // Only show events for the currently selected month
            if ($scheduleMonthKey !== $currentMonthKey) {
                continue;
            }

            // Create payment events for each individual debt based on its due_day
            if (isset($monthData['payments'])) {
                foreach ($monthData['payments'] as $payment) {
                    if ($payment['amount'] <= 0) {
                        continue;
                    }

                    // Calculate the planned payment date using this debt's due_day
                    $dueDay = $payment['due_day'] ?? 1;
                    $plannedDate = $baseDate->copy()
                        ->year($baseDate->year)
                        ->month($baseDate->month)
                        ->day(min($dueDay, $baseDate->daysInMonth));

                    $plannedDateKey = $plannedDate->format('Y-m-d');

                    // Find the debt to get its ID
                    $debt = $this->getDebts()->firstWhere('name', $payment['name']);
                    if (! $debt) {
                        continue;
                    }

                    // Check if there's an actual payment for this debt in this month
                    $lookupKey = $debt->id.'_'.$currentMonthKey;
                    $actualPayment = $actualPaymentsByDebtAndMonth->get($lookupKey);

                    if ($actualPayment) {
                        // There's an actual payment - use the actual payment date
                        $actualDateKey = $actualPayment->payment_date->format('Y-m-d');

                        if (! isset($events[$actualDateKey])) {
                            $events[$actualDateKey] = [
                                'type' => 'payment',
                                'amount' => 0,
                                'debts' => [],
                            ];
                        }

                        $events[$actualDateKey]['amount'] += $actualPayment->actual_amount;
                        $events[$actualDateKey]['debts'][] = [
                            'name' => $payment['name'],
                            'amount' => $actualPayment->actual_amount,
                            'isPriority' => $payment['isPriority'],
                            'isPaid' => true,
                        ];
                    } else {
                        // No actual payment yet - show planned payment on due date
                        if (! isset($events[$plannedDateKey])) {
                            $events[$plannedDateKey] = [
                                'type' => 'payment',
                                'amount' => 0,
                                'debts' => [],
                            ];
                        }

                        // Check if the payment is overdue (planned date is in the past)
                        $isOverdue = $plannedDate->isPast() && ! $plannedDate->isToday();

                        $events[$plannedDateKey]['amount'] += $payment['amount'];
                        $events[$plannedDateKey]['debts'][] = [
                            'name' => $payment['name'],
                            'amount' => $payment['amount'],
                            'isPriority' => $payment['isPriority'],
                            'isPaid' => false,
                            'isOverdue' => $isOverdue,
                        ];
                    }
                }
            }
        }

        return $events;
    }

    public function getMilestonesProperty(): array
    {
        $milestones = [];
        $schedule = $this->paymentSchedule;

        if (empty($schedule['schedule'])) {
            return [];
        }

        $paidOffDebts = [];

        foreach ($schedule['schedule'] as $monthData) {
            if (! isset($monthData['payments']) || ! is_array($monthData['payments'])) {
                continue;
            }

            $baseDate = Carbon::parse($monthData['date']);

            foreach ($monthData['payments'] as $payment) {
                $debtName = $payment['name'];

                if ($payment['remaining'] <= 0.01 && ! in_array($debtName, $paidOffDebts)) {
                    // Use the debt's specific due_day for the payoff milestone
                    $dueDay = $payment['due_day'] ?? 1;
                    $payoffDate = $baseDate->copy()
                        ->year($baseDate->year)
                        ->month($baseDate->month)
                        ->day(min($dueDay, $baseDate->daysInMonth));

                    $dateKey = $payoffDate->format('Y-m-d');
                    if (! isset($milestones[$dateKey])) {
                        $milestones[$dateKey] = [];
                    }
                    $milestones[$dateKey][] = [
                        'type' => 'debt_payoff',
                        'debtName' => $debtName,
                    ];
                    $paidOffDebts[] = $debtName;
                }
            }
        }

        return $milestones;
    }

    public function getDebtFreeDateProperty(): ?string
    {
        $schedule = $this->paymentSchedule;

        if (empty($schedule['payoffDate'])) {
            return null;
        }

        return $schedule['payoffDate'];
    }

    public function getCountdownProperty(): array
    {
        $debtFreeDate = $this->debtFreeDate;

        if (! $debtFreeDate) {
            return [
                'years' => 0,
                'months' => 0,
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0,
                'totalDays' => 0,
                'targetTimestamp' => null,
            ];
        }

        $now = now();
        $target = Carbon::parse($debtFreeDate);

        // Get the proper diff breakdown
        $diff = $now->diff($target);

        // Calculate total days for the summary
        $totalDays = (int) $now->diffInDays($target);

        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'seconds' => $diff->s,
            'totalDays' => $totalDays,
            'targetTimestamp' => $target->timestamp * 1000, // Convert to milliseconds for JavaScript
        ];
    }

    public function getCurrentMonthNameProperty(): string
    {
        return Carbon::create($this->currentYear, $this->currentMonth, 1)
            ->locale(app()->getLocale())
            ->translatedFormat('F Y');
    }

    public function render()
    {
        return view('livewire.payoff-calendar')->layout('components.layouts.app');
    }
}
