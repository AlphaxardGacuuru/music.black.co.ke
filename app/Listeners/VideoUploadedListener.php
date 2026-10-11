<?php

namespace App\Listeners;

use App\Events\VideoUploadedEvent;
use App\Jobs\ProcessVideoJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class VideoUploadedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(VideoUploadedEvent $event): void
    {
        ProcessVideoJob::dispatch($event->video);
    }
}
