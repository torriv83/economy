<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCalculationService;
use App\Services\PaymentService;
use App\Services\YnabService;
use App\Services\YnabTransactionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * @property string|null $debtFreeDate
 * @property array<string, mixed> $paymentSchedule
 * @property \Illuminate\Support\Collection<int, \App\Models\Payment> $actualPayments
 * @property int $daysRemaining
 */
class PayoffCalendar extends Component
{
    public bool $isLoading = true;

    public float $extraPayment;

    public string $strategy;

    public int $currentMonth;

    public int $currentYear;

    protected DebtCalculationService $calculationService;

    protected PaymentService $paymentService;

    protected YnabTransactionService $ynabTransactionService;

    protected YnabService $ynabService;

    // Modal state
    public bool $showPaymentModal = false;

    public bool $isEditMode = false;

    public ?int $selectedPaymentId = null;

    public int $selectedDebtId = 0;

    public string $selectedDebtName = '';

    public float $plannedAmount = 0;

    public float $paymentAmount = 0;

    public string $paymentDate = '';

    public string $paymentNotes = '';

    public int $selectedMonthNumber = 0;

    public string $selectedPaymentMonth = '';

    // YNAB Transaction Checker Modal state
    public bool $showYnabModal = false;

    public bool $isCheckingYnab = false;

    /** @var array<int, array{debt_id: int, debt_name: string, ynab_transactions: array<int, array{id: string, date: string, amount: float, payee_name: string|null, memo: string|null, status: string, local_payment_id: int|null, local_amount: float|null}>}> */
    public array $ynabComparisonResults = [];

    /** @var array<int, array{debt_id: int, debt_name: string, status: string, transaction_count: int}> */
    public array $ynabDebtSummary = [];

    public string $ynabError = '';

    public bool $ynabEnabled = false;

    public function boot(DebtCalculationService $service, PaymentService $paymentService, YnabTransactionService $ynabTransactionService, YnabService $ynabService): void
    {
        $this->calculationService = $service;
        $this->paymentService = $paymentService;
        $this->ynabTransactionService = $ynabTransactionService;
        $this->ynabService = $ynabService;
    }

    public function mount(float $extraPayment = 2000, string $strategy = 'avalanche'): void
    {
        $this->extraPayment = $extraPayment;
        $this->strategy = $strategy;
        $this->currentMonth = (int) now()->month;
        $this->currentYear = (int) now()->year;
        $this->ynabEnabled = app(\App\Services\SettingsService::class)->isYnabEnabled();
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

    public function loadData(): void
    {
        $this->isLoading = false;
    }

    public function openPaymentModal(
        int $debtId,
        string $debtName,
        float $plannedAmount,
        int $monthNumber,
        string $paymentMonth
    ): void {
        $this->selectedDebtId = $debtId;
        $this->selectedDebtName = $debtName;
        $this->plannedAmount = $plannedAmount;
        $this->paymentAmount = $plannedAmount;
        $this->selectedMonthNumber = $monthNumber;
        $this->selectedPaymentMonth = $paymentMonth;
        $this->paymentDate = now()->format('d.m.Y');
        $this->paymentNotes = '';
        $this->showPaymentModal = true;
    }

    public function openEditPaymentModal(int $paymentId): void
    {
        $payment = Payment::with('debt')->findOrFail($paymentId);

        $this->isEditMode = true;
        $this->selectedPaymentId = $paymentId;
        $this->selectedDebtId = $payment->debt_id;
        $this->selectedDebtName = $payment->debt->name;
        $this->plannedAmount = $payment->planned_amount;
        $this->paymentAmount = $payment->actual_amount;
        $this->selectedMonthNumber = $payment->month_number;
        $this->selectedPaymentMonth = $payment->payment_month;
        $this->paymentDate = $payment->payment_date->format('d.m.Y');
        $this->paymentNotes = $payment->notes ?? '';
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->isEditMode = false;
        $this->selectedPaymentId = null;
        $this->selectedDebtId = 0;
        $this->selectedDebtName = '';
        $this->paymentAmount = 0;
        $this->paymentDate = '';
        $this->paymentNotes = '';
        $this->selectedMonthNumber = 0;
        $this->selectedPaymentMonth = '';
        $this->plannedAmount = 0;
        $this->resetValidation();
    }

    public function recordPayment(PaymentService $paymentService): void
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date_format:d.m.Y',
            'paymentNotes' => 'nullable|string|max:500',
        ], [
            'paymentAmount.required' => __('app.payment_amount_required'),
            'paymentAmount.numeric' => __('app.payment_amount_numeric'),
            'paymentAmount.min' => __('app.payment_amount_min'),
            'paymentDate.required' => __('app.payment_date_required_error'),
            'paymentDate.date_format' => __('app.payment_date_format_error'),
            'paymentNotes.max' => __('app.payment_notes_max'),
        ]);

        // Check for duplicate payment (only when creating new)
        if (! $this->isEditMode && $paymentService->paymentExists($this->selectedDebtId, $this->selectedMonthNumber)) {
            $this->addError('paymentAmount', __('app.payment_duplicate_error'));

            return;
        }

        $dateObject = Carbon::createFromFormat('d.m.Y', $this->paymentDate);

        // Validate date is not in the future
        if ($dateObject->isFuture()) {
            $this->addError('paymentDate', __('app.payment_date_future_error'));

            return;
        }

        if ($this->isEditMode && $this->selectedPaymentId) {
            // Update existing payment
            DB::transaction(function () use ($paymentService, $dateObject) {
                $payment = Payment::findOrFail($this->selectedPaymentId);
                $payment->update([
                    'actual_amount' => $this->paymentAmount,
                    'payment_date' => $dateObject->format('Y-m-d'),
                    'notes' => $this->paymentNotes ?: null,
                ]);

                $paymentService->updateDebtBalances();
            });

            $flashMessage = 'Betaling oppdatert for '.$this->selectedDebtName;
        } else {
            // Create new payment
            $debt = Debt::findOrFail($this->selectedDebtId);

            DB::transaction(function () use ($debt, $paymentService, $dateObject) {
                $payment = $paymentService->recordPayment(
                    debt: $debt,
                    plannedAmount: $this->plannedAmount,
                    actualAmount: $this->paymentAmount,
                    monthNumber: $this->selectedMonthNumber,
                    paymentMonth: $this->selectedPaymentMonth
                );

                // Update with actual date and notes
                $payment->update([
                    'payment_date' => $dateObject->format('Y-m-d'),
                    'notes' => $this->paymentNotes ?: null,
                ]);

                $paymentService->updateDebtBalances();
            });

            $flashMessage = 'Betaling på '.number_format($this->paymentAmount, 0, ',', ' ').' kr registrert for '.$this->selectedDebtName;
        }

        $this->closePaymentModal();
        session()->flash('payment_recorded', $flashMessage);
    }

    public function openYnabModal(): void
    {
        $this->showYnabModal = true;
        $this->ynabComparisonResults = [];
        $this->ynabDebtSummary = [];
        $this->ynabError = '';
        $this->checkYnabTransactions();
    }

    public function closeYnabModal(): void
    {
        $this->showYnabModal = false;
        $this->ynabComparisonResults = [];
        $this->ynabDebtSummary = [];
        $this->ynabError = '';
        $this->isCheckingYnab = false;
    }

    public function checkYnabTransactions(): void
    {
        $this->isCheckingYnab = true;
        $this->ynabError = '';
        $this->ynabComparisonResults = [];
        $this->ynabDebtSummary = [];

        if (! $this->ynabService->isConfigured()) {
            $this->ynabError = __('app.ynab_not_configured');
            $this->isCheckingYnab = false;

            return;
        }

        // Get debts with YNAB account ID
        $debtsWithYnab = $this->getDebts()->filter(fn ($debt) => $debt->ynab_account_id !== null);

        if ($debtsWithYnab->isEmpty()) {
            $this->ynabError = __('app.no_debts_linked_to_ynab');
            $this->isCheckingYnab = false;

            return;
        }

        try {
            foreach ($debtsWithYnab as $debt) {
                // Get the latest payment date for this debt to use as "since" date
                $latestPayment = Payment::where('debt_id', $debt->id)
                    ->where('is_reconciliation_adjustment', false)
                    ->orderBy('payment_date', 'desc')
                    ->first();

                // Use start of the month the debt was created to catch transactions
                // made earlier in the same month before the debt was added
                $monthStart = $debt->created_at->copy()->startOfMonth();
                $paymentBasedDate = $latestPayment?->payment_date?->subDays(7);

                // Use the earlier of the two dates to ensure we catch all transactions
                $sinceDate = $paymentBasedDate && $paymentBasedDate->lt($monthStart)
                    ? $paymentBasedDate
                    : $monthStart;

                // Fetch transactions from YNAB
                $ynabTransactions = $this->ynabService->fetchPaymentTransactions(
                    $debt->ynab_account_id,
                    $sinceDate
                );

                if ($ynabTransactions->isEmpty()) {
                    // Track debts with no YNAB transactions
                    $this->ynabDebtSummary[] = [
                        'debt_id' => $debt->id,
                        'debt_name' => $debt->name,
                        'status' => 'no_transactions',
                        'transaction_count' => 0,
                    ];

                    continue;
                }

                // Use service to compare transactions
                $comparedTransactions = $this->ynabTransactionService->compareTransactionsForDebt($debt, $ynabTransactions);

                // Determine status based on transaction comparison
                $hasIssues = collect($comparedTransactions)->contains(fn ($tx) => $tx['status'] !== 'matched');

                $this->ynabDebtSummary[] = [
                    'debt_id' => $debt->id,
                    'debt_name' => $debt->name,
                    'status' => $hasIssues ? 'has_issues' : 'all_matched',
                    'transaction_count' => count($comparedTransactions),
                ];

                if (! empty($comparedTransactions)) {
                    $this->ynabComparisonResults[] = [
                        'debt_id' => $debt->id,
                        'debt_name' => $debt->name,
                        'ynab_transactions' => $comparedTransactions,
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->ynabError = __('app.ynab_connection_error').': '.$e->getMessage();
        }

        $this->isCheckingYnab = false;
    }

    public function importYnabTransaction(string $ynabTransactionId, int $debtId, PaymentService $paymentService): void
    {
        // Find the transaction in our results
        $transaction = null;
        foreach ($this->ynabComparisonResults as $result) {
            if ($result['debt_id'] === $debtId) {
                foreach ($result['ynab_transactions'] as $tx) {
                    if ($tx['id'] === $ynabTransactionId && $tx['status'] === 'missing') {
                        $transaction = $tx;
                        break 2;
                    }
                }
            }
        }

        if (! $transaction) {
            return;
        }

        $debt = Debt::findOrFail($debtId);
        $this->ynabTransactionService->importTransaction($debt, $transaction, $paymentService);

        // Refresh the comparison results
        $this->checkYnabTransactions();
        session()->flash('ynab_import_success', __('app.ynab_payment_imported'));
    }

    public function updatePaymentFromYnab(string $ynabTransactionId, int $localPaymentId, float $ynabAmount, string $ynabDate): void
    {
        $payment = Payment::findOrFail($localPaymentId);
        $this->ynabTransactionService->updatePaymentFromTransaction($payment, $ynabTransactionId, $ynabAmount, $ynabDate);

        // Refresh the comparison results
        $this->checkYnabTransactions();
        session()->flash('ynab_import_success', __('app.ynab_payment_updated'));
    }

    public function linkYnabTransaction(string $ynabTransactionId, int $debtId): void
    {
        // Find the transaction in our results
        $transaction = null;
        foreach ($this->ynabComparisonResults as $result) {
            if ($result['debt_id'] === $debtId) {
                foreach ($result['ynab_transactions'] as $tx) {
                    if ($tx['id'] === $ynabTransactionId && $tx['status'] === 'linkable') {
                        $transaction = $tx;
                        break 2;
                    }
                }
            }
        }

        if (! $transaction || ! isset($transaction['local_payment_id'])) {
            return;
        }

        $payment = Payment::findOrFail($transaction['local_payment_id']);
        $this->ynabTransactionService->linkTransactionToPayment(
            $payment,
            $ynabTransactionId,
            $transaction['amount'],
            $transaction['date']
        );

        // Refresh the comparison results
        $this->checkYnabTransactions();
        session()->flash('ynab_import_success', __('app.ynab_payment_linked'));
    }

    /**
     * @return array<int, int>
     */
    public function getAvailableYearsProperty(): array
    {
        $startYear = (int) now()->year;
        $endYear = $this->debtFreeDate
            ? (int) Carbon::parse($this->debtFreeDate)->year
            : $startYear + 5;

        return range($startYear, $endYear);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Debt>
     */
    protected function getDebts(): Collection
    {
        return once(fn () => Debt::all());
    }

    /**
     * @return array<string, mixed>
     */
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

        // Calculate offset to skip historical months (same pattern as PaymentPlan)
        $historicalPayments = $this->paymentService->getHistoricalPayments();
        $historicalMonthOffset = count($historicalPayments);

        return $this->calculationService->generatePaymentSchedule(
            $debts,
            $this->extraPayment,
            $this->strategy,
            $historicalMonthOffset
        );
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getCalendarProperty(): array
    {
        $firstOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);

        $calendarStart = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $firstOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $currentDate = $calendarStart->copy();

        while ($currentDate->lte($calendarEnd)) {
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

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Payment>
     */
    public function getActualPaymentsProperty(): Collection
    {
        $monthStart = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        return Payment::whereBetween('payment_date', [$monthStart, $monthEnd])
            ->with('debt')
            ->where('is_reconciliation_adjustment', false)
            ->get();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getPaymentEventsProperty(): array
    {
        $events = [];
        $schedule = $this->paymentSchedule;
        $actualPayments = $this->actualPayments;
        $currentMonthKey = Carbon::create($this->currentYear, $this->currentMonth, 1)->format('Y-m');
        $currentMonthDate = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $isPastMonth = $currentMonthDate->lt(now()->startOfMonth());

        // Calculate historical offset for correct month_number
        $historicalPayments = $this->paymentService->getHistoricalPayments();
        $historicalMonthOffset = count($historicalPayments);

        // Group actual payments by debt_id and payment_month for quick lookup
        $actualPaymentsByDebtAndMonth = $actualPayments->groupBy(function ($payment) {
            return $payment->debt_id.'_'.$payment->payment_month;
        })->map(fn ($group) => $group->first());

        // For past months, show actual payments directly (no schedule needed)
        if ($isPastMonth) {
            foreach ($actualPayments as $payment) {
                $actualDateKey = $payment->payment_date->format('Y-m-d');

                if (! isset($events[$actualDateKey])) {
                    $events[$actualDateKey] = [
                        'type' => 'payment',
                        'amount' => 0,
                        'debts' => [],
                    ];
                }

                $events[$actualDateKey]['amount'] += $payment->actual_amount;
                $events[$actualDateKey]['debts'][] = [
                    'payment_id' => $payment->id,
                    'debt_id' => $payment->debt_id,
                    'name' => $payment->debt->name,
                    'amount' => $payment->actual_amount,
                    'isPriority' => false,
                    'isPaid' => true,
                ];
            }

            return $events;
        }

        // For current/future months, use the schedule
        if (empty($schedule['schedule'])) {
            return [];
        }

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
                            'payment_id' => $actualPayment->id,
                            'debt_id' => $debt->id,
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
                            'debt_id' => $debt->id,
                            'name' => $payment['name'],
                            'amount' => $payment['amount'],
                            'isPriority' => $payment['isPriority'],
                            'isPaid' => false,
                            'isOverdue' => $isOverdue,
                            'month_number' => ($monthData['month'] ?? 1) + $historicalMonthOffset,
                            'payment_month' => $currentMonthKey,
                        ];
                    }
                }
            }
        }

        return $events;
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
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

    /**
     * @return array<string, int|null>
     */
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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.payoff-calendar')->layout('components.layouts.app');
    }
}
