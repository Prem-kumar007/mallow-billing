<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\GenerateDueInvoicesJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:aggregate-previous-day-usage')
    ->dailyAt('00:10')
    ->withoutOverlapping();

Schedule::job(new GenerateDueInvoicesJob)
    ->dailyAt('00:20')
    ->withoutOverlapping();
