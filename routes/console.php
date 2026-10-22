<?php

use App\Jobs\DeleteStaleTemporaryUploadsJob;
use App\Models\Story;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune Telescope entries older than 30 days
Schedule::command('telescope:prune --hours=720')->daily();

// TODO: `websockets:clean` came from the old laravel-websockets package,
// which this project no longer uses (replaced by Reverb). Remove or swap
// in a Reverb-equivalent cleanup task, then re-enable.
// Schedule::command('websockets:clean')->weekly();

Schedule::job(new DeleteStaleTemporaryUploadsJob)
    // ->everyMinute();
    ->dailyAt("01:00");


// Delete stories (and their media files) once they're 24 hours old
Schedule::command('model:prune', ['--model' => [Story::class]])->hourly();