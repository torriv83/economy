<?php

declare(strict_types=1);

namespace App\Livewire\Ynab;

use App\Services\YnabService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ReadyToAssign extends Component
{
    public ?float $amount = null;

    public bool $isLoading = true;

    public bool $hasError = false;

    public bool $isConfigured = true;

    protected YnabService $ynabService;

    public function boot(YnabService $ynabService): void
    {
        $this->ynabService = $ynabService;
    }

    public function mount(): void
    {
        $this->loadReadyToAssign();
    }

    public function loadReadyToAssign(): void
    {
        $this->isLoading = true;
        $this->hasError = false;

        // Check if YNAB is configured
        if (empty(config('services.ynab.token')) || empty(config('services.ynab.budget_id'))) {
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
        // Clear YNAB cache to get fresh data
        $this->ynabService->clearCache();
        $this->loadReadyToAssign();
    }

    public function render(): View
    {
        return view('livewire.ynab.ready-to-assign');
    }
}
