<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Debt;
use App\Models\Payment;
use App\Observers\DebtObserver;
use App\Observers\PaymentObserver;
use App\Services\YnabService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(YnabService::class, function () {
            return new YnabService(
                token: config('services.ynab.token'),
                budgetId: config('services.ynab.budget_id')
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
    }
}
