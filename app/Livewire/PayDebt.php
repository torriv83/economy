<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCacheService;
use App\Services\DebtCalculationService;
use App\Services\PaymentService;
use App\Services\SettingsService;
use Livewire\Component;

/**
 * Focused component for quickly registering debt payments.
 * Shows only current month's payments with minimal UI.
 *
 * @property-read array<string, mixed>|null $currentMonth
 * @property-read \Illuminate\Database\Eloquent\Collection<string, Debt> $debts
 */
class PayDebt extends Component
{
    /** @var array<string, float> */
    public array $editingPayments = [];

    protected DebtCalculationService $calculationService;

    protected PaymentService $paymentService;

    protected DebtCacheService $debtCacheService;

    protected SettingsService $settingsService;

    public function boot(
        DebtCalculationService $calculationService,
        PaymentService $paymentService,
        DebtCacheService $debtCacheService,
        SettingsService $settingsService
    ): void {
        $this->calculationService = $calculationService;
        $this->paymentService = $paymentService;
        $this->debtCacheService = $debtCacheService;
        $this->settingsService = $settingsService;
    }

    /**
     * Get current month's payments from the payment schedule.
     *
     * @return array<string, mixed>|null
     */
    public function getCurrentMonthProperty(): ?array
    {
        $debts = $this->debtCacheService->getAllWithPayments();

        if ($debts->isEmpty()) {
            return null;
        }

        $extraPayment = $this->settingsService->get('extra_payment', '2000') ?? '2000';
        $strategy = $this->settingsService->get('strategy', 'avalanche') ?? 'avalanche';

        $historicalPayments = $this->paymentService->getHistoricalPayments();
        $highestHistoricalMonth = count($historicalPayments);

        $fullSchedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            (float) $extraPayment,
            $strategy,
            $highestHistoricalMonth
        );

        // Get the first future month (month 1 after historical)
        $firstFutureMonth = $fullSchedule['schedule'][0] ?? null;

        if (! $firstFutureMonth) {
            return null;
        }

        // Adjust month number to account for historical months
        $firstFutureMonth['month'] = $firstFutureMonth['month'] + $highestHistoricalMonth;

        return $firstFutureMonth;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<string, Debt>
     */
    public function getDebtsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->debtCacheService->getAllWithPayments()->keyBy('name');
    }

    public function togglePayment(int $monthNumber, int $debtId): void
    {
        // Check if already paid - if so, delete it (undo)
        if ($this->paymentService->paymentExists($debtId, $monthNumber)) {
            $this->paymentService->deletePayment($debtId, $monthNumber);
            session()->flash('message', __('app.payment_deleted'));

            return;
        }

        $monthData = $this->currentMonth;

        if (! $monthData || $monthData['month'] !== $monthNumber) {
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

    public function markAllAsPaid(): void
    {
        $monthData = $this->currentMonth;

        if (! $monthData) {
            return;
        }

        $monthNumber = $monthData['month'];
        $debts = $this->debts;
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

    public function isAllPaid(): bool
    {
        $monthData = $this->currentMonth;

        if (! $monthData) {
            return false;
        }

        $debts = $this->debts;
        $debtIds = [];

        foreach ($monthData['payments'] as $payment) {
            $debt = $debts->get($payment['name']);
            if ($debt && $payment['amount'] > 0) {
                $debtIds[] = $debt->id;
            }
        }

        return $this->paymentService->isMonthFullyPaid($monthData['month'], $debtIds);
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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.pay-debt');
    }
}
