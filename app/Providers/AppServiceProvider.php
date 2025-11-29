<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Debt;
use App\Models\Payment;
use App\Observers\DebtObserver;
use App\Observers\PaymentObserver;
use App\Services\SettingsService;
use App\Services\YnabService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(YnabService::class, function ($app) {
            $settings = $app->make(SettingsService::class);

            return new YnabService(
                token: $settings->getYnabToken() ?? '',
                budgetId: $settings->getYnabBudgetId() ?? ''
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Debt::observe(DebtObserver::class);
        Payment::observe(PaymentObserver::class);

        // Override runtime config with database values so existing config() calls work
        $this->app->booted(function () {
            try {
                $settings = app(SettingsService::class);
                config([
                    'debt.minimum_payment.kredittkort.percentage' => $settings->getKredittkortPercentage(),
                    'debt.minimum_payment.kredittkort.minimum_amount' => $settings->getKredittkortMinimum(),
                    'debt.minimum_payment.forbrukslån.payoff_months' => $settings->getForbrukslånPayoffMonths(),
                ]);
            } catch (\Exception $e) {
                // Silently fail if settings table doesn't exist yet (e.g., during tests before migrations)
                // Config will fall back to default values from config/debt.php
            }
        });
    }
}
