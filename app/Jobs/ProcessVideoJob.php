<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ProcessVideoJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Video $video)
    {
        //
    }

    /**
     * Grab the frame 10 seconds in and save it as
     * video-thumbnails/<video file name>.jpg on the public disk.
     */
    public function handle(): void
    {
        // Stored path relative to the public disk, e.g. "videos/abc.mp4"
        $video = $this->video->getRawOriginal('video');

        // File name without directory or extension, e.g. "abc"
        $thumbnail = pathinfo($video, PATHINFO_FILENAME);

        FFMpeg::fromDisk('public')
            ->open($video)
            ->getFrameFromSeconds(10)
            ->export()
            ->toDisk('public')
            ->save('video-thumbnails/' . $thumbnail . '.jpg');
    }
}
