<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'no'])) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('locale.switch');
