<?php

namespace App\Livewire;

use App\Models\Debt;
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
            'balance' => ['required', 'numeric', 'min:0.01'],
            'interestRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimumPayment' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Navn er påkrevd.',
            'name.string' => 'Navn må være tekst.',
            'name.max' => 'Navn kan ikke være lengre enn 255 tegn.',
            'balance.required' => 'Saldo er påkrevd.',
            'balance.numeric' => 'Saldo må være et tall.',
            'balance.min' => 'Saldo må være minst 0,01 kr.',
            'interestRate.required' => 'Rente er påkrevd.',
            'interestRate.numeric' => 'Rente må være et tall.',
            'interestRate.min' => 'Rente kan ikke være negativ.',
            'interestRate.max' => 'Rente kan ikke være mer enn 100%.',
            'minimumPayment.numeric' => 'Minimum betaling må være et tall.',
            'minimumPayment.min' => 'Minimum betaling kan ikke være negativ.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        Debt::create([
            'name' => $validated['name'],
            'balance' => $validated['balance'],
            'original_balance' => $validated['balance'],
            'interest_rate' => $validated['interestRate'],
            'minimum_payment' => $validated['minimumPayment'] ?: null,
        ]);

        session()->flash('message', 'Gjeld lagt til.');

        $this->redirect(route('home'));
    }

    #[Title('Create Debt')]
    public function render()
    {
        return view('livewire.create-debt')
            ->layout('components.layouts.app');
    }
}
