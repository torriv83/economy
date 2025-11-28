<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasDebtValidation;
use App\Models\Debt;
use Livewire\Attributes\Title;
use Livewire\Component;

class EditDebt extends Component
{
    use HasDebtValidation;

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
     * Override to use debt's current balance for validation (read-only in edit mode).
     */
    protected function getBalanceForValidation(): float
    {
        return $this->debt->balance ?? 0;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->debtValidationRules(includeBalance: false);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->debtValidationMessages();
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
