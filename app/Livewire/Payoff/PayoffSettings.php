<?php

declare(strict_types=1);

namespace App\Livewire\Payoff;

use App\Models\Debt;
use App\Services\DebtCalculationService;
use App\Services\PayoffSettingsService;
use Livewire\Attributes\Computed;
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

    /**
     * Generate chart data for debt projection visualization.
     *
     * @return array{labels: array<string>, datasets: array<array{label: string, data: array<float>, borderColor: string}>}
     */
    #[Computed]
    public function debtProjectionData(): array
    {
        $debts = Debt::with('payments')->get();

        if ($debts->isEmpty()) {
            return ['labels' => [], 'datasets' => []];
        }

        $schedule = $this->calculationService->generatePaymentSchedule(
            $debts,
            $this->extraPayment,
            $this->strategy
        );

        if (empty($schedule['schedule'])) {
            return ['labels' => [], 'datasets' => []];
        }

        // Color palette for debt lines
        $colors = [
            '#3B82F6', // blue
            '#10B981', // green
            '#F59E0B', // amber
            '#EF4444', // red
            '#8B5CF6', // purple
            '#EC4899', // pink
            '#06B6D4', // cyan
            '#84CC16', // lime
        ];

        // Get debt names from the first month's payments
        /** @var array<int, array{name: string, payment: float, interest: float, principal: float, remaining: float}> $firstMonthPayments */
        $firstMonthPayments = $schedule['schedule'][0]['payments'];
        $debtNames = collect($firstMonthPayments)
            ->pluck('name')
            ->toArray();

        // Build labels (month names) - one label per data point (current month + each schedule month)
        $scheduleCount = count($schedule['schedule']);
        $labels = [];
        $currentDate = now();
        $locale = app()->getLocale();
        for ($i = 0; $i <= $scheduleCount; $i++) {
            $labels[] = $currentDate->copy()->addMonths($i)->locale($locale)->translatedFormat('F Y');
        }

        // Build datasets - one per debt
        $datasets = [];
        foreach ($debtNames as $debtIndex => $debtName) {
            // Get initial balance for this debt
            $debt = $debts->firstWhere('name', $debtName);
            $initialBalance = $debt ? $debt->balance : 0;

            // Collect remaining balances for each month
            $data = [$initialBalance]; // Start with current balance
            for ($i = 0; $i < $scheduleCount; $i++) {
                /** @var array<int, array{name: string, payment: float, interest: float, principal: float, remaining: float}> $monthPayments */
                $monthPayments = $schedule['schedule'][$i]['payments'];
                $payment = collect($monthPayments)->firstWhere('name', $debtName);
                $data[] = $payment ? $payment['remaining'] : 0;
            }

            $datasets[] = [
                'label' => $debtName,
                'data' => $data,
                'borderColor' => $colors[$debtIndex % count($colors)],
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.payoff.payoff-settings');
    }
}
