<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\SettingsService;
use App\Services\YnabService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class YnabSettings extends Component
{
    public bool $ynabEnabled = false;

    public string $token = '';

    public string $budgetId = '';

    public bool $isConfigured = false;

    public ?bool $connectionStatus = null;

    public bool $isTesting = false;

    public bool $backgroundSyncEnabled = false;

    public int $backgroundSyncInterval = 30;

    public ?string $lastSyncAt = null;

    /** @var array<int, int> */
    public array $intervalOptions = [5, 10, 15, 30, 60];

    protected SettingsService $settingsService;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'min:20'],
            'budgetId' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => __('app.ynab_token_required'),
            'token.string' => __('app.ynab_token_string'),
            'token.min' => __('app.ynab_token_min'),
            'budgetId.required' => __('app.ynab_budget_id_required'),
            'budgetId.string' => __('app.ynab_budget_id_string'),
            'budgetId.uuid' => __('app.ynab_budget_id_uuid'),
        ];
    }

    public function boot(SettingsService $settingsService): void
    {
        $this->settingsService = $settingsService;
    }

    public function mount(): void
    {
        $this->ynabEnabled = $this->settingsService->isYnabEnabled();
        $this->isConfigured = $this->settingsService->getYnabToken() !== null
            && $this->settingsService->getYnabBudgetId() !== null;

        // Load budget ID for display (but never load the token for security)
        $storedBudgetId = $this->settingsService->getYnabBudgetId();
        if ($storedBudgetId !== null) {
            $this->budgetId = $storedBudgetId;
        }

        // Load background sync settings
        $this->backgroundSyncEnabled = $this->settingsService->isYnabBackgroundSyncEnabled();
        $this->backgroundSyncInterval = $this->settingsService->getYnabBackgroundSyncInterval();
        $lastSync = $this->settingsService->getYnabLastSyncAt();
        $this->lastSyncAt = $lastSync?->format('d.m.Y H:i');
    }

    public function updatedYnabEnabled(): void
    {
        $this->settingsService->setYnabEnabled($this->ynabEnabled);
        $this->dispatch('ynab-settings-saved');
    }

    public function updatedBackgroundSyncEnabled(): void
    {
        $this->settingsService->setYnabBackgroundSyncEnabled($this->backgroundSyncEnabled);
        $this->dispatch('ynab-settings-saved');
    }

    public function updatedBackgroundSyncInterval(): void
    {
        $this->settingsService->setYnabBackgroundSyncInterval($this->backgroundSyncInterval);
        $this->dispatch('ynab-settings-saved');
    }

    public function saveCredentials(): void
    {
        $this->validate();

        $this->settingsService->setYnabToken($this->token);
        $this->settingsService->setYnabBudgetId($this->budgetId);

        $this->isConfigured = true;
        $this->connectionStatus = null;
        $this->token = ''; // Clear the token from memory after saving

        $this->dispatch('ynab-credentials-saved');
    }

    public function testConnection(): void
    {
        if (! $this->isConfigured) {
            return;
        }

        $this->isTesting = true;

        $token = $this->settingsService->getYnabToken();
        $budgetId = $this->settingsService->getYnabBudgetId();

        if ($token === null || $budgetId === null) {
            $this->connectionStatus = false;
            $this->isTesting = false;

            return;
        }

        $ynabService = new YnabService($token, $budgetId);
        $this->connectionStatus = $ynabService->isAccessible();
        $this->isTesting = false;
    }

    public function clearCredentials(): void
    {
        $this->settingsService->clearYnabCredentials();

        $this->token = '';
        $this->budgetId = '';
        $this->isConfigured = false;
        $this->connectionStatus = null;

        $this->dispatch('ynab-credentials-cleared');
    }

    public function render(): View
    {
        return view('livewire.settings.ynab-settings');
    }
}
