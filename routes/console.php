<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('blog:publish-scheduled')->everyMinute()->withoutOverlapping();

Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=60')
    ->everyMinute()
    ->withoutOverlapping(10);

Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->withoutOverlapping(120);

Schedule::command('backup:run --only-files')
    ->dailyAt('02:15')
    ->withoutOverlapping(180);

Schedule::command('backup:run --config=backup_full')
    ->weeklyOn(0, '03:00')
    ->withoutOverlapping(360);

Schedule::command('backup:clean')
    ->dailyAt('04:00')
    ->withoutOverlapping(60);

Schedule::command('backup:monitor')
    ->dailyAt('08:00')
    ->withoutOverlapping(30);
