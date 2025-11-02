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
            'name' => 'Norwegian',
            'balance' => 24596.58,
            'original_balance' => 24596.58,
            'interest_rate' => 24.35,
            'minimum_payment' => 900.00,
        ]);

        Debt::create([
            'name' => 'Nordax',
            'balance' => 23819.70,
            'original_balance' => 23819.70,
            'interest_rate' => 15.02,
            'minimum_payment' => 1000.00,
        ]);

        Debt::create([
            'name' => 'Nordax 2',
            'balance' => 23168.45,
            'original_balance' => 23168.45,
            'interest_rate' => 18.32,
            'minimum_payment' => 664.95,
        ]);

        Debt::create([
            'name' => 'Klarna',
            'balance' => 7403.69,
            'original_balance' => 7403.69,
            'interest_rate' => 0.00,
            'minimum_payment' => 749.59,
        ]);
    }
}
