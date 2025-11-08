<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Rules\MinimumPaymentRule;
use Livewire\Attributes\Title;
use Livewire\Component;

class EditDebt extends Component
{
    public Debt $debt;

    public string $name = '';

    public string $type = '';

    public string $balance = '';

    public string $interestRate = '';

    public string $minimumPayment = '';

    public ?string $dueDay = null;

    public function mount(Debt $debt): void
    {
        $this->debt = $debt;
        $this->name = $debt->name;
        $this->type = $debt->type;
        $this->balance = (string) $debt->balance; // Read-only, for display purposes
        $this->interestRate = (string) $debt->interest_rate;
        $this->minimumPayment = (string) $debt->minimum_payment;
        $this->dueDay = $debt->due_day ? (string) $debt->due_day : null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:forbrukslån,kredittkort'],
            'interestRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimumPayment' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    // Use debt's current balance for validation (read-only)
                    $balance = $this->debt->balance;

                    // For kredittkort: use the existing 3% or 300 kr rule
                    if ($this->type === 'kredittkort') {
                        $rule = new MinimumPaymentRule(
                            $this->type,
                            $balance,
                            (float) $this->interestRate
                        );
                        $rule->validate($attribute, $value, $fail);
                    } else {
                        // For forbrukslån: just ensure payment > monthly interest
                        $monthlyInterest = ($balance * ((float) $this->interestRate / 100)) / 12;

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
            'interestRate.required' => 'Rente er påkrevd.',
            'interestRate.numeric' => 'Rente må være et tall.',
            'interestRate.min' => 'Rente kan ikke være negativ.',
            'interestRate.max' => 'Rente kan ikke være mer enn 100%.',
            'minimumPayment.required' => 'Minimum betaling er påkrevd.',
            'minimumPayment.numeric' => 'Minimum betaling må være et tall.',
            'minimumPayment.min' => 'Minimum betaling må være minst 0,01 kr.',
        ];
    }

    public function update(): void
    {
        $validated = $this->validate();

        $this->debt->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            // Balance is read-only and calculated from payments - not editable
            'interest_rate' => $validated['interestRate'],
            'minimum_payment' => $validated['minimumPayment'],
            'due_day' => $validated['dueDay'] ?? null,
        ]);

        session()->flash('message', 'Gjeld oppdatert.');

        $this->redirect(route('home'));
    }

    #[Title('Edit Debt')]
    public function render()
    {
        return view('livewire.edit-debt')
            ->layout('components.layouts.app');
    }
}
