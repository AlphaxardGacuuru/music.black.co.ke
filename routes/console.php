<?php

use App\Jobs\DeleteStaleTemporaryUploadsJob;
use App\Jobs\PostDailyStoryJob;
use App\Jobs\PostLocalArtistNewsJob;
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

// Post local artist news from the house account at 8am, 1pm and 6pm
// (Nairobi time, the app timezone)
Schedule::job(new PostLocalArtistNewsJob)
    ->cron('0 8,13,18 * * *')
    ->withoutOverlapping();

// The house account's daily story (headlines, trending, gigs, on this day)
// at 7am, from the news posted over the previous day
Schedule::job(new PostDailyStoryJob)
    ->dailyAt('07:00')
    ->withoutOverlapping();