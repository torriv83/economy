<?php

declare(strict_types=1);

namespace App\Livewire\Ynab;

use App\Models\Debt;
use App\Services\AccelerationService;
use App\Services\SettingsService;
use App\Services\YnabService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class AccelerationOpportunities extends Component
{
    public Debt $debt;

    /** @var Collection<int, array<string, mixed>> */
    public Collection $opportunities;

    public bool $isLoading = true;

    public bool $hasError = false;

    public bool $isConfigured = true;

    public bool $ynabEnabled = false;

    protected AccelerationService $accelerationService;

    protected YnabService $ynabService;

    protected SettingsService $settingsService;

    public function boot(AccelerationService $accelerationService, YnabService $ynabService, SettingsService $settingsService): void
    {
        $this->accelerationService = $accelerationService;
        $this->ynabService = $ynabService;
        $this->settingsService = $settingsService;
    }

    public function mount(Debt $debt): void
    {
        $this->debt = $debt;
        $this->opportunities = collect();
        $this->ynabEnabled = $this->settingsService->isYnabEnabled();
        $this->isConfigured = $this->settingsService->isYnabConfigured();

        // Don't load YNAB data here - let wire:init handle it for instant page navigation
        // Set isLoading to false if YNAB is disabled or not configured (nothing to load)
        if (! $this->ynabEnabled || ! $this->isConfigured) {
            $this->isLoading = false;
        }
    }

    public function loadOpportunities(): void
    {
        // Don't set isLoading = true here - it's already true from mount()
        // Setting it again causes unnecessary re-renders that break Alpine.js state
        $this->hasError = false;

        // Check if YNAB is configured
        if (! $this->settingsService->isYnabConfigured()) {
            $this->isConfigured = false;
            $this->isLoading = false;

            return;
        }

        try {
            $this->opportunities = $this->accelerationService->getOpportunities($this->debt);
            $this->isLoading = false;
        } catch (\Exception $e) {
            $this->hasError = true;
            $this->isLoading = false;
        }
    }

    public function refresh(): void
    {
        // Show loading state for refresh
        $this->isLoading = true;

        // Clear YNAB cache to get fresh data
        $this->ynabService->clearCache();
        $this->loadOpportunities();
    }

    /**
     * Get tier label for display.
     */
    public function getTierLabel(int $tier): string
    {
        return match ($tier) {
            1 => __('app.acceleration_tier_1'),
            2 => __('app.acceleration_tier_2'),
            3 => __('app.acceleration_tier_3'),
            default => __('app.tier', ['number' => $tier]),
        };
    }

    /**
     * Get tier color classes.
     */
    public function getTierColor(int $tier): string
    {
        return match ($tier) {
            1 => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            2 => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
            3 => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    public function render(): View
    {
        return view('livewire.ynab.acceleration-opportunities');
    }
}
