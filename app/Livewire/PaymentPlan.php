<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use App\Services\PaymentService;
use Livewire\Component;

/**
 * @property int $totalMonths
 * @property array<int, array<string, mixed>> $detailedSchedule
 */
class PaymentPlan extends Component
{
    public float $extraPayment;

    public string $strategy;

    public int $visibleMonths = 12;

    /** @var array<string, float> */
    public array $editingPayments = [];

    /** @var array<string, string> */
    public array $editingNotes = [];

    /** @var array<string, bool> */
    public array $showNoteInput = [];

    protected DebtCalculationService $calculationService;

    protected PaymentService $paymentService;

    public function boot(DebtCalculationService $service, PaymentService $paymentService): void
    {
        $this->calculationService = $service;
        $this->paymentService = $paymentService;
    }

    public function mount(float $extraPayment = 2000, string $strategy = 'avalanche'): void
    {
        $this->extraPayment = $extraPayment;
        $this->strategy = $strategy;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPaymentScheduleProperty(): array
    {
        $debts = Debt::with('payments')->get();

        if ($debts->isEmpty()) {
            return [];
        }

        $fullSchedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $this->extraPayment,
            $this->strategy
        );

        return array_slice($fullSchedule['schedule'], 0, 6);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDetailedScheduleProperty(): array
    {
        $debts = Debt::with('payments')->get();

        if ($debts->isEmpty()) {
            return [];
        }

        $historicalPayments = $this->paymentService->getHistoricalPayments();

        $fullSchedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $this->extraPayment,
            $this->strategy
        );

        $highestHistoricalMonth = count($historicalPayments);

        $futureSchedule = array_map(function ($month) use ($highestHistoricalMonth) {
            $month['month'] = $month['month'] + $highestHistoricalMonth;

            return $month;
        }, $fullSchedule['schedule']);

        $combined = array_merge($historicalPayments, $futureSchedule);

        return array_slice($combined, 0, $this->visibleMonths + count($historicalPayments));
    }

    public function loadMoreMonths(): void
    {
        $this->visibleMonths += 12;
    }

    public function showAllMonths(): void
    {
        $this->visibleMonths = $this->totalMonths;
    }

    public function getTotalMonthsProperty(): int
    {
        $debts = Debt::with('payments')->get();

        if ($debts->isEmpty()) {
            return 0;
        }

        $schedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $this->extraPayment,
            $this->strategy
        );

        return $schedule['months'];
    }

    public function getOverallProgressProperty(): float
    {
        return $this->paymentService->calculateOverallProgress();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<string, Debt>
     */
    public function getDebtsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Debt::with('payments')->get()->keyBy('name');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDebtPayoffScheduleProperty(): array
    {
        $debts = Debt::with('payments')->get();

        if ($debts->isEmpty()) {
            return [];
        }

        $fullSchedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $this->extraPayment,
            $this->strategy
        );

        $payoffDates = [];

        foreach ($debts as $debt) {
            $payoffMonth = null;

            foreach ($fullSchedule['schedule'] as $month) {
                foreach ($month['payments'] as $payment) {
                    if ($payment['name'] === $debt->name && $payment['remaining'] <= 0.01) {
                        $payoffMonth = $month;
                        break 2;
                    }
                }
            }

            $payoffDates[] = [
                'name' => $debt->name,
                'balance' => $debt->balance,
                'payoff_month' => $payoffMonth ? $payoffMonth['month'] : null,
                'payoff_date' => $payoffMonth ? $payoffMonth['monthName'] : null,
                'payments_made' => $debt->payments()->count(),
                'total_payments' => $payoffMonth ? $payoffMonth['month'] : null,
            ];
        }

        /** @var array<int, array<string, mixed>> */
        return collect($payoffDates)->sortBy('payoff_month')->values()->toArray();
    }

    public function togglePayment(int $monthNumber, int $debtId): void
    {
        // Check if already paid - if so, delete it (undo)
        if ($this->paymentService->paymentExists($debtId, $monthNumber)) {
            $this->paymentService->deletePayment($debtId, $monthNumber);
            session()->flash('message', __('app.payment_deleted'));

            return;
        }

        $schedule = $this->detailedSchedule;
        $monthData = collect($schedule)->firstWhere('month', $monthNumber);

        if (! $monthData) {
            return;
        }

        $debt = Debt::find($debtId);
        if (! $debt) {
            return;
        }

        /** @var array<int, array<string, mixed>> $payments */
        $payments = $monthData['payments'];
        $payment = collect($payments)->firstWhere('name', $debt->name);

        if (! $payment || $payment['amount'] <= 0) {
            return;
        }

        $paymentMonth = now()->addMonths($monthNumber - 1)->format('Y-m');

        $this->paymentService->recordPayment(
            $debt,
            $payment['amount'],
            $payment['amount'],
            $monthNumber,
            $paymentMonth
        );

        $this->paymentService->updateDebtBalances();

        session()->flash('message', __('app.payment_saved'));
    }

    public function markMonthAsPaid(int $monthNumber): void
    {
        $schedule = $this->detailedSchedule;
        $monthData = collect($schedule)->firstWhere('month', $monthNumber);

        if (! $monthData) {
            return;
        }

        $debts = Debt::with('payments')->get()->keyBy('name');
        $debtIds = [];

        foreach ($monthData['payments'] as $payment) {
            $debt = $debts->get($payment['name']);
            if ($debt && $payment['amount'] > 0) {
                $debtIds[] = $debt->id;
            }
        }

        // Check if all payments are already paid
        if ($this->paymentService->isMonthFullyPaid($monthNumber, $debtIds)) {
            // Unmark all
            $this->paymentService->deleteMonthPayments($monthNumber);
            session()->flash('message', __('app.payments_deleted'));

            return;
        }

        // Mark all as paid
        $payments = [];
        $paymentMonth = now()->addMonths($monthNumber - 1)->format('Y-m');

        foreach ($monthData['payments'] as $payment) {
            $debt = $debts->get($payment['name']);

            if ($debt && $payment['amount'] > 0) {
                // Skip if payment already exists
                if ($this->paymentService->paymentExists($debt->id, $monthNumber)) {
                    continue;
                }

                $payments[] = [
                    'debt_id' => $debt->id,
                    'planned_amount' => $payment['amount'],
                    'actual_amount' => $payment['amount'],
                ];
            }
        }

        if (! empty($payments)) {
            $this->paymentService->recordMonthPayments($payments, $paymentMonth, $monthNumber);
            session()->flash('message', __('app.payments_saved'));
        }
    }

    public function isMonthFullyPaid(int $monthNumber): bool
    {
        $schedule = $this->detailedSchedule;
        $monthData = collect($schedule)->firstWhere('month', $monthNumber);

        if (! $monthData) {
            return false;
        }

        $debts = Debt::with('payments')->get()->keyBy('name');
        $debtIds = [];

        foreach ($monthData['payments'] as $payment) {
            $debt = $debts->get($payment['name']);
            if ($debt && $payment['amount'] > 0) {
                $debtIds[] = $debt->id;
            }
        }

        return $this->paymentService->isMonthFullyPaid($monthNumber, $debtIds);
    }

    public function updatePaymentAmount(int $monthNumber, int $debtId): void
    {
        $key = "{$monthNumber}_{$debtId}";

        if (! isset($this->editingPayments[$key])) {
            return;
        }

        $amount = (float) $this->editingPayments[$key];

        if ($amount <= 0) {
            session()->flash('error', 'Beløpet må være større enn 0');

            return;
        }

        $this->paymentService->updatePaymentAmount($debtId, $monthNumber, $amount);

        unset($this->editingPayments[$key]);

        session()->flash('message', __('app.payment_saved'));
    }

    public function toggleNoteInput(int $monthNumber, int $debtId): void
    {
        $key = "{$monthNumber}_{$debtId}";

        if (isset($this->showNoteInput[$key]) && $this->showNoteInput[$key]) {
            unset($this->showNoteInput[$key]);
        } else {
            $this->showNoteInput[$key] = true;

            $payment = $this->paymentService->getPayment($debtId, $monthNumber);
            if ($payment && $payment->notes) {
                $this->editingNotes[$key] = $payment->notes;
            }
        }
    }

    public function saveNote(int $monthNumber, int $debtId): void
    {
        $key = "{$monthNumber}_{$debtId}";

        $note = isset($this->editingNotes[$key]) ? trim($this->editingNotes[$key]) : '';

        $this->paymentService->updatePaymentNote($debtId, $monthNumber, $note);

        unset($this->showNoteInput[$key]);
        unset($this->editingNotes[$key]);

        session()->flash('message', __('app.payment_saved'));
    }

    public function deleteNote(int $monthNumber, int $debtId): void
    {
        $key = "{$monthNumber}_{$debtId}";

        $this->paymentService->updatePaymentNote($debtId, $monthNumber, '');

        unset($this->showNoteInput[$key]);
        unset($this->editingNotes[$key]);

        session()->flash('message', __('app.payment_saved'));
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.payment-plan')->layout('components.layouts.app');
    }
}
