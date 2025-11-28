<?php

use App\Livewire\Debts\DebtLayout;
use App\Livewire\EditDebt;
use App\Livewire\Payoff\PayoffLayout;
use App\Livewire\SelfLoans\SelfLoanLayout;
use App\Livewire\Settings\SettingsLayout;
use Illuminate\Support\Facades\Route;

Route::get('/', DebtLayout::class)->name('home');

Route::get('/debts', DebtLayout::class)->name('debts');

Route::get('/debts/{debt}/edit', EditDebt::class)->name('debts.edit');

Route::get('/payoff', PayoffLayout::class)->name('payoff');

Route::get('/self-loans', SelfLoanLayout::class)->name('self-loans');

Route::get('/settings', SettingsLayout::class)->name('settings');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'no'])) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('locale.switch');
