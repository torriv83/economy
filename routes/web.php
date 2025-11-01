<?php

use App\Livewire\CreateDebt;
use App\Livewire\DebtList;
use Illuminate\Support\Facades\Route;

Route::get('/', DebtList::class)->name('home');

Route::get('/debts/create', CreateDebt::class)->name('debts.create');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'no'])) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('locale.switch');
