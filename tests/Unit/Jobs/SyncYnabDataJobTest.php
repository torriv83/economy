<?php

declare(strict_types=1);

use App\Jobs\SyncYnabDataJob;
use App\Services\SettingsService;
use App\Services\YnabService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->settingsService = new SettingsService;
});

it('does not sync when sync is not due', function () {
    $this->settingsService->setYnabEnabled(true);
    $this->settingsService->setYnabToken('test-token');
    $this->settingsService->setYnabBudgetId('test-budget');
    $this->settingsService->setYnabBackgroundSyncEnabled(true);
    $this->settingsService->setYnabBackgroundSyncInterval(30);
    $this->settingsService->setYnabLastSyncAt(new \DateTimeImmutable('now'));

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldNotReceive('fetchBudgetSummary');
        $mock->shouldNotReceive('fetchCategories');
        $mock->shouldNotReceive('fetchDebtAccounts');
    });

    $job = new SyncYnabDataJob;
    $job->handle($this->settingsService);

    // Last sync should not be updated
    $lastSync = $this->settingsService->getYnabLastSyncAt();
    expect($lastSync)->not->toBeNull();
});

it('does not sync when background sync is disabled', function () {
    $this->settingsService->setYnabEnabled(true);
    $this->settingsService->setYnabToken('test-token');
    $this->settingsService->setYnabBudgetId('test-budget');
    $this->settingsService->setYnabBackgroundSyncEnabled(false);

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldNotReceive('fetchBudgetSummary');
        $mock->shouldNotReceive('fetchCategories');
        $mock->shouldNotReceive('fetchDebtAccounts');
    });

    $job = new SyncYnabDataJob;
    $job->handle($this->settingsService);
});

it('does not sync when YNAB is not configured', function () {
    $this->settingsService->setYnabBackgroundSyncEnabled(true);

    $this->mock(YnabService::class, function ($mock) {
        $mock->shouldNotReceive('fetchBudgetSummary');
        $mock->shouldNotReceive('fetchCategories');
        $mock->shouldNotReceive('fetchDebtAccounts');
    });

    $job = new SyncYnabDataJob;
    $job->handle($this->settingsService);
});

it('logs error when API call fails', function () {
    $this->settingsService->setYnabEnabled(true);
    $this->settingsService->setYnabToken('test-token');
    $this->settingsService->setYnabBudgetId('test-budget');
    $this->settingsService->setYnabBackgroundSyncEnabled(true);

    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn ($message) => str_contains($message, 'YNAB background sync failed'));

    Log::shouldReceive('info')->never();

    $job = new SyncYnabDataJob;
    $job->handle($this->settingsService);

    // Last sync should not be updated on failure
    expect($this->settingsService->getYnabLastSyncAt())->toBeNull();
});
