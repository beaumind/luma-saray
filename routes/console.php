<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Issue each building's monthly charge once per Jalali month (idempotent).
Schedule::command('charges:generate')->dailyAt('02:00')->withoutOverlapping();
