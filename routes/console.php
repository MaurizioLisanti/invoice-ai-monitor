<?php

use App\Console\Commands\CheckInvoiceQueueCommand;
use App\Console\Commands\SimulateInvoicesCommand;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Registra il comando come classe (Laravel 11 senza withCommands() in bootstrap/app.php).
ConsoleApplication::starting(function ($artisan): void {
    $artisan->resolve(CheckInvoiceQueueCommand::class);
    $artisan->resolve(SimulateInvoicesCommand::class);
});

// Scheduler — ogni minuto [M2]
Schedule::command('invoice:check-queue')->everyMinute();
