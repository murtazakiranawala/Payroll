<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// BRD FR-1.1: fetch employee master data from AIIMS on a scheduled (daily) basis,
// in addition to the on-demand "Sync now" action exposed in the Employees screen.
Schedule::command('payroll:sync-employees --full=0')
    ->dailyAt('02:00')
    ->name('aiims-employee-sync')
    ->withoutOverlapping()
    ->onOneServer();
