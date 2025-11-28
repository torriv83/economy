<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasDebtValidation;
use App\Models\Debt;
use Livewire\Attributes\Title;
use Livewire\Component;

class CreateDebt extends Component
{
    use HasDebtValidation;

    public string $name = '';

    public string $type = 'kredittkort';

    public string $balance = '';

    public string $interestRate = '';

    public string $minimumPayment = '';

    public ?string $dueDay = null;

    public bool $showSuccessMessage = false;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->debtValidationRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->debtValidationMessages();
    }

    public function save(): void
    {
        $validated = $this->validate();

        // Auto-assign priority order: max + 1
        $maxPriorityValue = Debt::max('custom_priority_order');
        $maxPriority = is_numeric($maxPriorityValue) ? (int) $maxPriorityValue : 0;

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
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.create-debt')
            ->layout('components.layouts.app');
    }
}
