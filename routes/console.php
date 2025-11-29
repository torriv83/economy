<?php

use App\Jobs\SyncYnabDataJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// YNAB background sync - runs every minute, job determines if sync is due
Schedule::job(new SyncYnabDataJob)->everyMinute();
