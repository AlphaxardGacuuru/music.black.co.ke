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
     * Grab the frame 10 seconds in (or halfway through a shorter clip), save
     * it as video-thumbnails/<video file name>.jpg on the public disk, and use
     * it as the video's thumbnail when the artist didn't upload one.
     */
    public function handle(): void
    {
        // Stored path relative to the public disk, e.g. "videos/abc.mp4"
        $video = $this->video->getRawOriginal('video');

        // File name without directory or extension, e.g. "abc"
        $thumbnail = 'video-thumbnails/' . pathinfo($video, PATHINFO_FILENAME) . '.jpg';

        $media = FFMpeg::fromDisk('public')->open($video);

        $seconds = min(10, intdiv($media->getDurationInSeconds(), 2));

        $media->getFrameFromSeconds($seconds)
            ->export()
            ->toDisk('public')
            ->save($thumbnail);

        if (! $this->video->getRawOriginal('thumbnail')) {
            $this->video->thumbnail = $thumbnail;
            $this->video->save();
        }
    }
}
