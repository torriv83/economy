<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Rules\MinimumPaymentRule;
use Livewire\Attributes\Title;
use Livewire\Component;

class EditDebt extends Component
{
    public ?Debt $debt = null;

    public ?int $debtId = null;

    public string $name = '';

    public string $type = '';

    public string $balance = '';

    public string $interestRate = '';

    public string $minimumPayment = '';

    public ?string $dueDay = null;

    public function mount(?int $debtId = null, ?Debt $debt = null): void
    {
        // Support both SPA mode (debtId prop) and direct URL (route model binding)
        // In Livewire 3, props are set as public properties BEFORE mount() is called.
        // The mount() parameter may still be null even if the prop was passed.
        // So we need to check BOTH the parameter AND the public property.

        // Route model binding takes priority (direct URL access)
        if ($debt !== null) {
            $this->debt = $debt;
            $this->debtId = $debt->id;
        } else {
            // Merge parameter and property - either could be set by Livewire
            $resolvedDebtId = $debtId ?? $this->debtId;

            if ($resolvedDebtId === null) {
                throw new \InvalidArgumentException('Either debtId or debt must be provided');
            }

            $this->debtId = $resolvedDebtId;
            $this->debt = Debt::findOrFail($resolvedDebtId);
        }

        // Populate form fields from loaded debt
        $this->name = $this->debt->name ?? '';
        $this->type = $this->debt->type ?? '';
        $this->balance = (string) ($this->debt->balance ?? 0);
        $this->interestRate = (string) ($this->debt->interest_rate ?? 0);
        $this->minimumPayment = (string) ($this->debt->minimum_payment ?? 0);
        $this->dueDay = $this->debt->due_day !== null ? (string) $this->debt->due_day : null;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
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
                    $balance = $this->debt->balance ?? 0;

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

    /**
     * @return array<string, string>
     */
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

        // Dispatch event for parent component in SPA mode
        $this->dispatch('debtUpdated');
    }

    #[Title('Edit Debt')]
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.edit-debt')
            ->layout('components.layouts.app');
    }
}
