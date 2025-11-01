<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

class CreateDebt extends Component
{
    public string $name = '';

    public string $balance = '';

    public string $interestRate = '';

    public string $minimumPayment = '';

    public bool $showSuccessMessage = false;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'balance' => ['required', 'numeric', 'min:0'],
            'interestRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimumPayment' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        // TODO: Save to database once Debt model is created
        // For now, just show success message
        $this->showSuccessMessage = true;

        // Reset form
        $this->reset(['name', 'balance', 'interestRate', 'minimumPayment']);

        // Hide success message after 3 seconds
        $this->dispatch('debt-saved');
    }

    #[Title('Create Debt')]
    public function render()
    {
        return view('livewire.create-debt')
            ->layout('components.layouts.app');
    }
}
