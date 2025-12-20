<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\SettingsService;
use App\Services\YnabService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * @property-read bool $isYnabConfigured
 * @property-read array<int, array{id: string, name: string, group_name: string}> $ynabCategories
 * @property-read array<int, string> $invalidCategories
 */
class BufferSettings extends Component
{
    public float $bufferTargetAmount = 20000;

    /** @var array<int, array{name: string, target: float}> */
    public array $categories = [];

    public string $newCategoryName = '';

    public float $newCategoryTarget = 0;

    protected SettingsService $settingsService;

    protected YnabService $ynabService;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'bufferTargetAmount' => ['required', 'numeric', 'min:0'],
            'newCategoryName' => ['required', 'string', 'min:1', 'max:100'],
            'newCategoryTarget' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bufferTargetAmount.required' => __('app.validation_buffer_target_amount_required'),
            'bufferTargetAmount.numeric' => __('app.validation_buffer_target_amount_numeric'),
            'bufferTargetAmount.min' => __('app.validation_buffer_target_amount_min'),
            'newCategoryName.required' => __('app.validation_category_name_required'),
            'newCategoryName.string' => __('app.validation_category_name_string'),
            'newCategoryName.min' => __('app.validation_category_name_min'),
            'newCategoryName.max' => __('app.validation_category_name_max'),
            'newCategoryTarget.required' => __('app.validation_category_target_required'),
            'newCategoryTarget.numeric' => __('app.validation_category_target_numeric'),
            'newCategoryTarget.min' => __('app.validation_category_target_min'),
        ];
    }

    public function boot(SettingsService $settingsService, YnabService $ynabService): void
    {
        $this->settingsService = $settingsService;
        $this->ynabService = $ynabService;
    }

    public function getIsYnabConfiguredProperty(): bool
    {
        return $this->settingsService->isYnabConfigured();
    }

    /**
     * @return array<int, array{id: string, name: string, group_name: string}>
     */
    public function getYnabCategoriesProperty(): array
    {
        if (! $this->isYnabConfigured) {
            return [];
        }

        try {
            return $this->ynabService->fetchCategories()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get list of configured categories that don't exist in YNAB.
     *
     * @return array<int, string>
     */
    public function getInvalidCategoriesProperty(): array
    {
        if (! $this->isYnabConfigured || empty($this->ynabCategories)) {
            return [];
        }

        $ynabCategoryNames = array_column($this->ynabCategories, 'name');
        $invalidCategories = [];

        foreach ($this->categories as $category) {
            if (! in_array($category['name'], $ynabCategoryNames, true)) {
                $invalidCategories[] = $category['name'];
            }
        }

        return $invalidCategories;
    }

    public function mount(): void
    {
        $this->bufferTargetAmount = $this->settingsService->getBufferTargetAmount();
        $this->categories = $this->settingsService->getBufferCategories();
    }

    public function updatedBufferTargetAmount(): void
    {
        $this->validate(['bufferTargetAmount' => $this->rules()['bufferTargetAmount']]);
        $this->settingsService->setBufferTargetAmount($this->bufferTargetAmount);
        $this->dispatch('buffer-settings-saved');
    }

    public function addCategory(): void
    {
        $this->validate([
            'newCategoryName' => $this->rules()['newCategoryName'],
            'newCategoryTarget' => $this->rules()['newCategoryTarget'],
        ]);

        $this->settingsService->addBufferCategory($this->newCategoryName, $this->newCategoryTarget);
        $this->categories = $this->settingsService->getBufferCategories();

        $this->newCategoryName = '';
        $this->newCategoryTarget = 0;

        $this->dispatch('buffer-settings-saved');
    }

    public function removeCategory(int $index): void
    {
        if (isset($this->categories[$index])) {
            $categoryName = $this->categories[$index]['name'];
            $this->settingsService->removeBufferCategory($categoryName);
            $this->categories = $this->settingsService->getBufferCategories();
            $this->dispatch('buffer-settings-saved');
        }
    }

    public function updateCategoryTarget(int $index, float $target): void
    {
        if (isset($this->categories[$index]) && $target >= 0) {
            $categoryName = $this->categories[$index]['name'];
            $this->settingsService->updateBufferCategoryTarget($categoryName, $target);
            $this->categories = $this->settingsService->getBufferCategories();
            $this->dispatch('buffer-settings-saved');
        }
    }

    public function updateCategoryName(int $index, string $newName): void
    {
        if (isset($this->categories[$index]) && $newName !== '') {
            $oldName = $this->categories[$index]['name'];
            if ($oldName !== $newName) {
                $this->settingsService->updateBufferCategoryName($oldName, $newName);
                $this->categories = $this->settingsService->getBufferCategories();
                $this->dispatch('buffer-settings-saved');
            }
        }
    }

    public function resetToDefaults(): void
    {
        $this->settingsService->resetBufferSettingsToDefaults();

        $this->bufferTargetAmount = $this->settingsService->getBufferTargetAmount();
        $this->categories = $this->settingsService->getBufferCategories();

        $this->dispatch('buffer-settings-reset');
    }

    public function render(): View
    {
        return view('livewire.settings.buffer-settings');
    }
}
