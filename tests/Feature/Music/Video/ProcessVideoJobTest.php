<?php

namespace Tests\Feature\Music\Video;

use App\Events\VideoUploadedEvent;
use App\Jobs\ProcessVideoJob;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ProcessVideoJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_uploaded_listener_dispatches_the_job(): void
    {
        Bus::fake([ProcessVideoJob::class]);
        $video = Video::factory()->create();

        VideoUploadedEvent::dispatch($video);

        Bus::assertDispatched(ProcessVideoJob::class, fn ($job) => $job->video->is($video));
    }

    public function test_job_grabs_frame_at_ten_seconds_into_video_thumbnails(): void
    {
        $video = Video::factory()->create(['video' => 'videos/abc123.mp4']);

        $media = Mockery::mock();
        $exporter = Mockery::mock();

        FFMpeg::shouldReceive('fromDisk')->once()->with('public')->andReturn($media);
        $media->shouldReceive('open')->once()->with('videos/abc123.mp4')->andReturnSelf();
        $media->shouldReceive('getFrameFromSeconds')->once()->with(10)->andReturnSelf();
        $media->shouldReceive('export')->once()->andReturn($exporter);
        $exporter->shouldReceive('toDisk')->once()->with('public')->andReturnSelf();
        $exporter->shouldReceive('save')->once()->with('video-thumbnails/abc123.jpg');

        (new ProcessVideoJob($video))->handle();
    }

    public function test_job_creates_a_real_thumbnail_with_ffmpeg(): void
    {
        $ffmpeg = (new ExecutableFinder)->find('ffmpeg');

        if (! $ffmpeg) {
            $this->markTestSkipped('ffmpeg is not installed.');
        }

        Storage::fake('public');
        Storage::disk('public')->makeDirectory('videos');

        $process = new Process([
            $ffmpeg, '-y', '-loglevel', 'error',
            '-f', 'lavfi', '-i', 'testsrc=duration=12:size=160x120:rate=5',
            Storage::disk('public')->path('videos/sample.mp4'),
        ]);
        $process->mustRun();

        $video = Video::factory()->create(['video' => 'videos/sample.mp4']);

        ProcessVideoJob::dispatchSync($video);

        Storage::disk('public')->assertExists('video-thumbnails/sample.jpg');
    }
}
