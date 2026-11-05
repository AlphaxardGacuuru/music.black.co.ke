<?php

namespace App\Jobs;

use App\Services\News\LocalArtistNewsPoster;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Posts the latest local artist news from the house account (see
 * LocalArtistNewsPoster). Scheduled in routes/console.php.
 */
class PostLocalArtistNewsJob implements ShouldQueue
{
    use Queueable;

    /**
     * A failed run isn't retried; the next scheduled run picks up the news.
     */
    public int $tries = 1;

    /**
     * Each story can take a web search and an AI reply.
     */
    public int $timeout = 600;

    public function handle(LocalArtistNewsPoster $poster): void
    {
        $poster->run(config('services.news.posts_per_run'));
    }
}
