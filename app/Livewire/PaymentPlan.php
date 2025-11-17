<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use App\Services\PaymentService;
use Livewire\Component;

/**
 * @property int $totalMonths
 * @property array $detailedSchedule
 */
class PaymentPlan extends Component
{
    public float $extraPayment = 2000;

    public string $strategy = 'avalanche';

    public int $visibleMonths = 12;

    public array $editingPayments = [];

    public array $editingNotes = [];

    public array $showNoteInput = [];

    public array $reconciliationModals = [];

    public array $reconciliationBalances = [];

    public array $reconciliationDates = [];

    public array $reconciliationNotes = [];

    protected DebtCalculationService $calculationService;

    protected PaymentService $paymentService;

    public function rules(): array
    {
        return [
            'extraPayment' => ['required', 'numeric', 'min:0', 'max:1000000'],
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

    public function boot(DebtCalculationService $service, PaymentService $paymentService): void
    {
        $this->calculationService = $service;
        $this->paymentService = $paymentService;
    }

    public function mount(): void
    {
        // Initialize reconciliation modal states for all debts
        foreach (Debt::all() as $debt) {
            $this->reconciliationModals[$debt->id] = false;
            $this->reconciliationDates[$debt->id] = now()->format('Y-m-d');
        }
    }

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

    public function getPayoffDateProperty(): string
    {
        $debts = Debt::with('payments')->get();

        if ($debts->isEmpty()) {
            return now()->locale('nb')->translatedFormat('F Y');
        }

        $schedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $this->extraPayment,
            $this->strategy
        );

        return now()->parse($schedule['payoffDate'])->locale('nb')->translatedFormat('F Y');
    }

    public function getTotalInterestProperty(): float
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

        return $schedule['totalInterest'];
    }

    public function getOverallProgressProperty(): float
    {
        return $this->paymentService->calculateOverallProgress();
    }

    public function getDebtsProperty()
    {
        return Debt::with('payments')->get()->keyBy('name');
    }

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
            ];
        }

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

        $payment = collect($monthData['payments'])->firstWhere('name', $debt->name);

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

    public function openReconciliationModal(int $debtId): void
    {
        $this->reconciliationModals[$debtId] = true;

        $debt = Debt::find($debtId);
        if ($debt) {
            $this->reconciliationBalances[$debtId] = (string) $debt->balance;
            $this->reconciliationDates[$debtId] = now()->format('Y-m-d');
            $this->reconciliationNotes[$debtId] = null;
        }
    }

    public function closeReconciliationModal(int $debtId): void
    {
        unset($this->reconciliationModals[$debtId]);
        unset($this->reconciliationBalances[$debtId]);
        unset($this->reconciliationNotes[$debtId]);

        $debt = Debt::find($debtId);
        if ($debt) {
            $this->reconciliationBalances[$debtId] = (string) $debt->balance;
            $this->reconciliationDates[$debtId] = now()->format('Y-m-d');
        }
    }

    public function getReconciliationDifference(int $debtId): float
    {
        $debt = Debt::find($debtId);
        if (! $debt) {
            return 0;
        }

        $actualBalance = isset($this->reconciliationBalances[$debtId])
            ? (float) $this->reconciliationBalances[$debtId]
            : $debt->balance;

        return round($actualBalance - $debt->balance, 2);
    }

    public function reconcileDebt(int $debtId): void
    {
        $debt = Debt::find($debtId);
        if (! $debt) {
            return;
        }

        $this->validate([
            "reconciliationBalances.{$debtId}" => ['required', 'numeric', 'min:0'],
            "reconciliationDates.{$debtId}" => ['required', 'date'],
            "reconciliationNotes.{$debtId}" => ['nullable', 'string', 'max:500'],
        ], [
            "reconciliationBalances.{$debtId}.required" => 'Faktisk saldo er påkrevd.',
            "reconciliationBalances.{$debtId}.numeric" => 'Faktisk saldo må være et tall.',
            "reconciliationBalances.{$debtId}.min" => 'Faktisk saldo kan ikke være negativ.',
            "reconciliationDates.{$debtId}.required" => 'Avstemmingsdato er påkrevd.',
            "reconciliationDates.{$debtId}.date" => 'Avstemmingsdato må være en gyldig dato.',
            "reconciliationNotes.{$debtId}.max" => 'Notater kan ikke være lengre enn 500 tegn.',
        ]);

        $difference = $this->getReconciliationDifference($debtId);

        if (abs($difference) < 0.01) {
            session()->flash('message', 'Ingen justering nødvendig - saldo er allerede korrekt.');
            $this->closeReconciliationModal($debtId);

            return;
        }

        $this->paymentService->reconcileDebt(
            $debt,
            (float) $this->reconciliationBalances[$debtId],
            $this->reconciliationDates[$debtId],
            $this->reconciliationNotes[$debtId] ?? null
        );

        session()->flash('message', 'Gjeld avstemt.');

        $this->closeReconciliationModal($debtId);
    }

    public function render()
    {
        return view('livewire.payment-plan')->layout('components.layouts.app');
    }
}
