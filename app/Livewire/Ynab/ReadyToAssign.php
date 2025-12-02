<?php

declare(strict_types=1);

namespace App\Livewire\Ynab;

use App\Services\SettingsService;
use App\Services\YnabService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class ReadyToAssign extends Component
{
    public ?float $amount = null;

    public bool $isLoading = true;

    public bool $hasError = false;

    public bool $isConfigured = true;

    public bool $ynabEnabled = false;

    public bool $compact = false;

    protected YnabService $ynabService;

    protected SettingsService $settingsService;

    public function boot(YnabService $ynabService, SettingsService $settingsService): void
    {
        $this->ynabService = $ynabService;
        $this->settingsService = $settingsService;
    }

    public function mount(): void
    {
        $this->ynabEnabled = $this->settingsService->isYnabEnabled();
        $this->isConfigured = $this->settingsService->isYnabConfigured();

        if (! $this->ynabEnabled) {
            $this->isLoading = false;

            return;
        }

        $this->loadReadyToAssign();
    }

    public function loadReadyToAssign(): void
    {
        $this->isLoading = true;
        $this->hasError = false;

        // Check if YNAB is configured
        if (! $this->settingsService->isYnabConfigured()) {
            $this->isConfigured = false;
            $this->isLoading = false;

            return;
        }

        try {
            $summary = $this->ynabService->fetchBudgetSummary();
            $this->amount = $summary['ready_to_assign'];
            $this->isLoading = false;
        } catch (\Exception $e) {
            $this->hasError = true;
            $this->isLoading = false;
        }
    }

    public function refresh(): void
    {
        // Rate limit refreshes to prevent hitting YNAB API limits (200/hour)
        $rateLimitKey = 'ynab:refresh_rate_limit:'.auth()->id();
        $lastRefresh = Cache::get($rateLimitKey);

        if ($lastRefresh && now()->diffInSeconds($lastRefresh) < 30) {
            // Too soon - just reload from cache without clearing
            $this->loadReadyToAssign();

            return;
        }

        // Record this refresh attempt
        Cache::put($rateLimitKey, now(), 60);

        // Clear YNAB cache to get fresh data
        $this->ynabService->clearCache();
        $this->loadReadyToAssign();
    }

    public function render(): View
    {
        return view('livewire.ynab.ready-to-assign');
    }
}
