<?php

declare(strict_types=1);

namespace App\Livewire\Payoff;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use App\Services\PayoffSettingsService;
use Livewire\Component;

class PayoffSettings extends Component
{
    public float $extraPayment = 2000;

    public string $strategy = 'avalanche';

    protected DebtCalculationService $calculationService;

    protected PayoffSettingsService $settingsService;

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'extraPayment' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function messages(): array
    {
        return [
            'extraPayment.required' => __('validation.required', ['attribute' => __('app.extra_monthly_payment')]),
            'extraPayment.numeric' => __('validation.numeric', ['attribute' => __('app.extra_monthly_payment')]),
            'extraPayment.min' => __('validation.min.numeric', ['attribute' => __('app.extra_monthly_payment'), 'min' => 0]),
            'extraPayment.max' => __('validation.max.numeric', ['attribute' => __('app.extra_monthly_payment'), 'max' => '1 000 000']),
        ];
    }

    public function boot(DebtCalculationService $calculationService, PayoffSettingsService $settingsService): void
    {
        $this->calculationService = $calculationService;
        $this->settingsService = $settingsService;
    }

    public function mount(): void
    {
        $this->extraPayment = $this->settingsService->getExtraPayment();
        $this->strategy = $this->settingsService->getStrategy();
    }

    public function updatedExtraPayment(): void
    {
        $this->validate(['extraPayment' => $this->rules()['extraPayment']]);
        $this->settingsService->setExtraPayment($this->extraPayment);
        $this->dispatch('planSettingsUpdated', extraPayment: $this->extraPayment, strategy: $this->strategy);
    }

    public function updatedStrategy(): void
    {
        $this->settingsService->setStrategy($this->strategy);
        $this->dispatch('planSettingsUpdated', extraPayment: $this->extraPayment, strategy: $this->strategy);
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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.payoff.payoff-settings');
    }
}
