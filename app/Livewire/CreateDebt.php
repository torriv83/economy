<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Rules\MinimumPaymentRule;
use Livewire\Attributes\Title;
use Livewire\Component;

class CreateDebt extends Component
{
    public string $name = '';

    public string $type = 'kredittkort';

    public string $balance = '';

    public string $interestRate = '';

    public string $minimumPayment = '';

    public ?string $dueDay = null;

    public bool $showSuccessMessage = false;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:forbrukslån,kredittkort'],
            'balance' => ['required', 'numeric', 'min:0.01'],
            'interestRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimumPayment' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    // For kredittkort: use the existing 3% or 300 kr rule
                    if ($this->type === 'kredittkort') {
                        $rule = new MinimumPaymentRule(
                            $this->type,
                            (float) $this->balance,
                            (float) $this->interestRate
                        );
                        $rule->validate($attribute, $value, $fail);
                    } else {
                        // For forbrukslån: just ensure payment > monthly interest
                        $monthlyInterest = ((float) $this->balance * ((float) $this->interestRate / 100)) / 12;

                        if ((float) $value <= $monthlyInterest) {
                            $fail(__('validation.minimum_payment_must_cover_interest', [
                                'interest' => number_format($monthlyInterest, 2, ',', ' '),
                            ]));
                        }
                    }
                },
            ],
            'dueDay' => ['nullable', 'integer', 'min:1', 'max:31'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Navn er påkrevd.',
            'name.string' => 'Navn må være tekst.',
            'name.max' => 'Navn kan ikke være lengre enn 255 tegn.',
            'type.required' => 'Gjeldstype er påkrevd.',
            'type.in' => 'Gjeldstype må være enten forbrukslån eller kredittkort.',
            'balance.required' => 'Saldo er påkrevd.',
            'balance.numeric' => 'Saldo må være et tall.',
            'balance.min' => 'Saldo må være minst 0,01 kr.',
            'interestRate.required' => 'Rente er påkrevd.',
            'interestRate.numeric' => 'Rente må være et tall.',
            'interestRate.min' => 'Rente kan ikke være negativ.',
            'interestRate.max' => 'Rente kan ikke være mer enn 100%.',
            'minimumPayment.required' => 'Minimum betaling er påkrevd.',
            'minimumPayment.numeric' => 'Minimum betaling må være et tall.',
            'minimumPayment.min' => 'Minimum betaling må være minst 0,01 kr.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        // Auto-assign priority order: max + 1
        $maxPriority = Debt::max('custom_priority_order') ?? 0;

        Debt::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'balance' => $validated['balance'],
            'original_balance' => $validated['balance'],
            'interest_rate' => $validated['interestRate'],
            'minimum_payment' => $validated['minimumPayment'],
            'custom_priority_order' => $maxPriority + 1,
            'due_day' => $validated['dueDay'] ?? null,
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
