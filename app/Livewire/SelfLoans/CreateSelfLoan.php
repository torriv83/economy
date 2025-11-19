<?php

declare(strict_types=1);

namespace App\Livewire\SelfLoans;

use App\Models\SelfLoan\SelfLoan;
use Livewire\Component;

class CreateSelfLoan extends Component
{
    public string $name = '';

    public string $description = '';

    public string $amount = '';

    public function createLoan(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:0.01',
        ]);

        SelfLoan::create([
            'name' => $this->name,
            'description' => $this->description,
            'original_amount' => $this->amount,
            'current_balance' => $this->amount,
        ]);

        session()->flash('message', 'Self-loan created successfully.');

        $this->dispatch('loanCreated');

        $this->reset();
    }

    public function render()
    {
        return view('livewire.self-loans.create-self-loan');
    }
}
