<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DebtSettings extends Component
{
    public float $kredittkortPercentage = 3;

    public float $kredittkortMinimum = 300;

    public int $forbrukslånPayoffMonths = 60;

    protected SettingsService $settingsService;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'kredittkortPercentage' => ['required', 'numeric', 'min:1', 'max:100'],
            'kredittkortMinimum' => ['required', 'numeric', 'min:0'],
            'forbrukslånPayoffMonths' => ['required', 'integer', 'min:1', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kredittkortPercentage.required' => __('app.validation_kredittkort_percentage_required'),
            'kredittkortPercentage.numeric' => __('app.validation_kredittkort_percentage_numeric'),
            'kredittkortPercentage.min' => __('app.validation_kredittkort_percentage_min'),
            'kredittkortPercentage.max' => __('app.validation_kredittkort_percentage_max'),
            'kredittkortMinimum.required' => __('app.validation_kredittkort_minimum_required'),
            'kredittkortMinimum.numeric' => __('app.validation_kredittkort_minimum_numeric'),
            'kredittkortMinimum.min' => __('app.validation_kredittkort_minimum_min'),
            'forbrukslånPayoffMonths.required' => __('app.validation_forbrukslan_months_required'),
            'forbrukslånPayoffMonths.integer' => __('app.validation_forbrukslan_months_integer'),
            'forbrukslånPayoffMonths.min' => __('app.validation_forbrukslan_months_min'),
            'forbrukslånPayoffMonths.max' => __('app.validation_forbrukslan_months_max'),
        ];
    }

    public function boot(SettingsService $settingsService): void
    {
        $this->settingsService = $settingsService;
    }

    public function mount(): void
    {
        $this->kredittkortPercentage = $this->settingsService->getKredittkortPercentage() * 100;
        $this->kredittkortMinimum = $this->settingsService->getKredittkortMinimum();
        $this->forbrukslånPayoffMonths = $this->settingsService->getForbrukslånPayoffMonths();
    }

    public function updatedKredittkortPercentage(): void
    {
        $this->validate(['kredittkortPercentage' => $this->rules()['kredittkortPercentage']]);
        $this->settingsService->setKredittkortPercentage($this->kredittkortPercentage / 100);
        $this->dispatch('debt-settings-saved');
    }

    public function updatedKredittkortMinimum(): void
    {
        $this->validate(['kredittkortMinimum' => $this->rules()['kredittkortMinimum']]);
        $this->settingsService->setKredittkortMinimum($this->kredittkortMinimum);
        $this->dispatch('debt-settings-saved');
    }

    public function updatedForbrukslånPayoffMonths(): void
    {
        $this->validate(['forbrukslånPayoffMonths' => $this->rules()['forbrukslånPayoffMonths']]);
        $this->settingsService->setForbrukslånPayoffMonths($this->forbrukslånPayoffMonths);
        $this->dispatch('debt-settings-saved');
    }

    public function resetToDefaults(): void
    {
        $this->settingsService->resetDebtSettingsToDefaults();

        $this->kredittkortPercentage = $this->settingsService->getKredittkortPercentage() * 100;
        $this->kredittkortMinimum = $this->settingsService->getKredittkortMinimum();
        $this->forbrukslånPayoffMonths = $this->settingsService->getForbrukslånPayoffMonths();

        $this->dispatch('debt-settings-reset');
    }

    public function render(): View
    {
        return view('livewire.settings.debt-settings');
    }
}
