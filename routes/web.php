<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Debts\DebtLayout;
use App\Livewire\Payoff\PayoffLayout;
use App\Livewire\SelfLoans\SelfLoanLayout;
use App\Livewire\Settings\SettingsLayout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/', DebtLayout::class)->name('home');
    Route::get('/debts', DebtLayout::class)->name('debts');
    Route::get('/payoff', PayoffLayout::class)->name('payoff');
    Route::get('/self-loans', SelfLoanLayout::class)->name('self-loans');
    Route::get('/settings', SettingsLayout::class)->name('settings');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});

// Public routes
Route::get('/locale/{locale}', function (
    string $locale, 
    \App\Services\SettingsService $settings
) {
    $settings->setLocale($locale);

    return redirect()->back();
})->name('locale.switch');
