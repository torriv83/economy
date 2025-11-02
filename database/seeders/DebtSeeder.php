<?php

namespace Database\Seeders;

use App\Models\Debt;
use Illuminate\Database\Seeder;

class DebtSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Debt::create([
            'name' => 'Kredittkort',
            'balance' => 50000.00,
            'interest_rate' => 8.5,
            'minimum_payment' => 500.00,
        ]);

        Debt::create([
            'name' => 'Studielån',
            'balance' => 200000.00,
            'interest_rate' => 2.5,
            'minimum_payment' => null,
        ]);

        Debt::create([
            'name' => 'Billån',
            'balance' => 75000.00,
            'interest_rate' => 5.0,
            'minimum_payment' => 1200.00,
        ]);
    }
}
