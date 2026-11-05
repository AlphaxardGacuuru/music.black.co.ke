<?php

namespace App\Jobs;

use App\Services\Stories\DailyStoryPoster;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Posts the house account's daily story (see DailyStoryPoster). Scheduled
 * in routes/console.php.
 */
class PostDailyStoryJob implements ShouldQueue
{
    use Queueable;

    /**
     * Not retried, so a half-failed run can't post the story twice.
     */
    public int $tries = 1;

    /**
     * Two AI research calls, source checks and drawing the cards.
     */
    public int $timeout = 600;

    public function handle(DailyStoryPoster $poster): void
    {
        $poster->run();
    }
}
