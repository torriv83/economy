<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RecommendationSettings extends Component
{
    public float $highInterestThreshold = 15;

    public float $lowInterestThreshold = 5;

    public int $bufferTargetMonths = 2;

    public float $minInterestSavings = 1000;

    protected SettingsService $settingsService;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'highInterestThreshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'lowInterestThreshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'bufferTargetMonths' => ['required', 'integer', 'min:1', 'max:12'],
            'minInterestSavings' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'highInterestThreshold.required' => __('app.validation_high_interest_required'),
            'highInterestThreshold.numeric' => __('app.validation_high_interest_numeric'),
            'highInterestThreshold.min' => __('app.validation_high_interest_min'),
            'highInterestThreshold.max' => __('app.validation_high_interest_max'),
            'lowInterestThreshold.required' => __('app.validation_low_interest_required'),
            'lowInterestThreshold.numeric' => __('app.validation_low_interest_numeric'),
            'lowInterestThreshold.min' => __('app.validation_low_interest_min'),
            'lowInterestThreshold.max' => __('app.validation_low_interest_max'),
            'bufferTargetMonths.required' => __('app.validation_buffer_target_required'),
            'bufferTargetMonths.integer' => __('app.validation_buffer_target_integer'),
            'bufferTargetMonths.min' => __('app.validation_buffer_target_min'),
            'bufferTargetMonths.max' => __('app.validation_buffer_target_max'),
            'minInterestSavings.required' => __('app.validation_min_interest_savings_required'),
            'minInterestSavings.numeric' => __('app.validation_min_interest_savings_numeric'),
            'minInterestSavings.min' => __('app.validation_min_interest_savings_min'),
        ];
    }

    public function boot(SettingsService $settingsService): void
    {
        $this->settingsService = $settingsService;
    }

    public function mount(): void
    {
        $this->highInterestThreshold = $this->settingsService->getHighInterestThreshold();
        $this->lowInterestThreshold = $this->settingsService->getLowInterestThreshold();
        $this->bufferTargetMonths = $this->settingsService->getBufferTargetMonths();
        $this->minInterestSavings = $this->settingsService->getMinInterestSavings();
    }

    public function updatedHighInterestThreshold(): void
    {
        $this->validate(['highInterestThreshold' => $this->rules()['highInterestThreshold']]);
        $this->settingsService->setHighInterestThreshold($this->highInterestThreshold);
        $this->dispatch('recommendation-settings-saved');
    }

    public function updatedLowInterestThreshold(): void
    {
        $this->validate(['lowInterestThreshold' => $this->rules()['lowInterestThreshold']]);
        $this->settingsService->setLowInterestThreshold($this->lowInterestThreshold);
        $this->dispatch('recommendation-settings-saved');
    }

    public function updatedBufferTargetMonths(): void
    {
        $this->validate(['bufferTargetMonths' => $this->rules()['bufferTargetMonths']]);
        $this->settingsService->setBufferTargetMonths($this->bufferTargetMonths);
        $this->dispatch('recommendation-settings-saved');
    }

    public function updatedMinInterestSavings(): void
    {
        $this->validate(['minInterestSavings' => $this->rules()['minInterestSavings']]);
        $this->settingsService->setMinInterestSavings($this->minInterestSavings);
        $this->dispatch('recommendation-settings-saved');
    }

    public function resetToDefaults(): void
    {
        $this->settingsService->resetRecommendationSettingsToDefaults();

        $this->highInterestThreshold = $this->settingsService->getHighInterestThreshold();
        $this->lowInterestThreshold = $this->settingsService->getLowInterestThreshold();
        $this->bufferTargetMonths = $this->settingsService->getBufferTargetMonths();
        $this->minInterestSavings = $this->settingsService->getMinInterestSavings();

        $this->dispatch('recommendation-settings-reset');
    }

    public function render(): View
    {
        return view('livewire.settings.recommendation-settings');
    }
}
