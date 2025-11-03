<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use App\Services\PaymentService;
use Livewire\Component;

class PaymentPlan extends Component
{
    public float $extraPayment = 2000;

    public string $strategy = 'avalanche';

    public int $visibleMonths = 12;

    public array $editingPayments = [];

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

    public function getPaymentScheduleProperty(): array
    {
        $debts = Debt::all();

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
        $debts = Debt::all();

        if ($debts->isEmpty()) {
            return [];
        }

        $fullSchedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $this->extraPayment,
            $this->strategy
        );

        return array_slice($fullSchedule['schedule'], 0, $this->visibleMonths);
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
        $debts = Debt::all();

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
        $debts = Debt::all();

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
        $debts = Debt::all();

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

    public function getDebtPayoffScheduleProperty(): array
    {
        $debts = Debt::all();

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

        $debts = Debt::all()->keyBy('name');
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

        $debts = Debt::all()->keyBy('name');
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

    public function render()
    {
        return view('livewire.payment-plan')->layout('components.layouts.app');
    }
}
