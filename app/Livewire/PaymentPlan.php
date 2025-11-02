<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use Livewire\Component;

class PaymentPlan extends Component
{
    public float $extraPayment = 2000;

    public string $strategy = 'avalanche';

    public int $visibleMonths = 12;

    protected DebtCalculationService $calculationService;

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

    public function boot(DebtCalculationService $service): void
    {
        $this->calculationService = $service;
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

    public function render()
    {
        return view('livewire.payment-plan')->layout('components.layouts.app');
    }
}
