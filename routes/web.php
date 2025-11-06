<?php

use App\Livewire\CreateDebt;
use App\Livewire\DebtList;
use App\Livewire\EditDebt;
use App\Livewire\PaymentPlan;
use App\Livewire\PayoffCalendar;
use App\Livewire\StrategyComparison;
use Illuminate\Support\Facades\Route;

Route::get('/', DebtList::class)->name('home');

Route::get('/debts/create', CreateDebt::class)->name('debts.create');

Route::get('/debts/{debt}/edit', EditDebt::class)->name('debts.edit');

Route::get('/strategies', StrategyComparison::class)->name('strategies');

Route::get('/payment-plan', PaymentPlan::class)->name('payment-plan');

Route::get('/calendar', PayoffCalendar::class)->name('calendar');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'no'])) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('locale.switch');
