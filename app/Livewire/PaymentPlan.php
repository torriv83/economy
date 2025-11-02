<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use Livewire\Component;

class PaymentPlan extends Component
{
    public float $extraPayment = 2000;

    public string $strategy = 'avalanche';

    protected DebtCalculationService $calculationService;

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
