<?php

namespace App\Livewire;

use Livewire\Component;

class PaymentPlan extends Component
{
    public array $mockDebts = [];

    public array $paymentSchedule = [];

    public int $extraPayment = 2000;

    public string $strategy = 'Avalanche';

    public int $totalMonths = 27;

    public string $payoffDate = 'Mars 2027';

    public int $totalInterest = 22500;

    public function mount(): void
    {
        // Mock debts data
        $this->mockDebts = [
            [
                'id' => 1,
                'name' => 'Kredittkort',
                'balance' => 50000,
                'interestRate' => 8.5,
                'minimumPayment' => 500,
                'color' => 'red',
            ],
            [
                'id' => 2,
                'name' => 'Studielån',
                'balance' => 200000,
                'interestRate' => 2.5,
                'minimumPayment' => 0,
                'color' => 'blue',
            ],
            [
                'id' => 3,
                'name' => 'Billån',
                'balance' => 75000,
                'interestRate' => 5.0,
                'minimumPayment' => 1200,
                'color' => 'green',
            ],
        ];

        // Mock payment schedule for 6 months
        $this->paymentSchedule = [
            [
                'month' => 1,
                'monthName' => 'Januar 2025',
                'priorityDebt' => 'Kredittkort',
                'payments' => [
                    ['name' => 'Kredittkort', 'amount' => 2500, 'remaining' => 47500, 'isPriority' => true],
                    ['name' => 'Studielån', 'amount' => 0, 'remaining' => 200000, 'isPriority' => false],
                    ['name' => 'Billån', 'amount' => 1200, 'remaining' => 74000, 'isPriority' => false],
                ],
                'totalPaid' => 3700,
                'progress' => 3.7,
            ],
            [
                'month' => 2,
                'monthName' => 'Februar 2025',
                'priorityDebt' => 'Kredittkort',
                'payments' => [
                    ['name' => 'Kredittkort', 'amount' => 2500, 'remaining' => 45000, 'isPriority' => true],
                    ['name' => 'Studielån', 'amount' => 0, 'remaining' => 200000, 'isPriority' => false],
                    ['name' => 'Billån', 'amount' => 1200, 'remaining' => 73000, 'isPriority' => false],
                ],
                'totalPaid' => 3700,
                'progress' => 7.4,
            ],
            [
                'month' => 3,
                'monthName' => 'Mars 2025',
                'priorityDebt' => 'Kredittkort',
                'payments' => [
                    ['name' => 'Kredittkort', 'amount' => 2500, 'remaining' => 42500, 'isPriority' => true],
                    ['name' => 'Studielån', 'amount' => 0, 'remaining' => 200000, 'isPriority' => false],
                    ['name' => 'Billån', 'amount' => 1200, 'remaining' => 72000, 'isPriority' => false],
                ],
                'totalPaid' => 3700,
                'progress' => 11.1,
            ],
            [
                'month' => 4,
                'monthName' => 'April 2025',
                'priorityDebt' => 'Kredittkort',
                'payments' => [
                    ['name' => 'Kredittkort', 'amount' => 2500, 'remaining' => 40000, 'isPriority' => true],
                    ['name' => 'Studielån', 'amount' => 0, 'remaining' => 200000, 'isPriority' => false],
                    ['name' => 'Billån', 'amount' => 1200, 'remaining' => 71000, 'isPriority' => false],
                ],
                'totalPaid' => 3700,
                'progress' => 14.8,
            ],
            [
                'month' => 5,
                'monthName' => 'Mai 2025',
                'priorityDebt' => 'Kredittkort',
                'payments' => [
                    ['name' => 'Kredittkort', 'amount' => 2500, 'remaining' => 37500, 'isPriority' => true],
                    ['name' => 'Studielån', 'amount' => 0, 'remaining' => 200000, 'isPriority' => false],
                    ['name' => 'Billån', 'amount' => 1200, 'remaining' => 70000, 'isPriority' => false],
                ],
                'totalPaid' => 3700,
                'progress' => 18.5,
            ],
            [
                'month' => 6,
                'monthName' => 'Juni 2025',
                'priorityDebt' => 'Kredittkort',
                'payments' => [
                    ['name' => 'Kredittkort', 'amount' => 2500, 'remaining' => 35000, 'isPriority' => true],
                    ['name' => 'Studielån', 'amount' => 0, 'remaining' => 200000, 'isPriority' => false],
                    ['name' => 'Billån', 'amount' => 1200, 'remaining' => 69000, 'isPriority' => false],
                ],
                'totalPaid' => 3700,
                'progress' => 22.2,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.payment-plan')->layout('components.layouts.app');
    }
}
