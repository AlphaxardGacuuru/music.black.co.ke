<?php

namespace Tests\Feature\Music\Video;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VideoFilePondTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_store_video_thumbnail_returns_the_stored_path(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $path = $this->post('/api/filepond/video-thumbnail', [
            'filepond-thumbnail' => UploadedFile::fake()->image('t.jpg'),
        ])->assertOk()->getContent();

        $this->assertStringStartsWith('video-thumbnails/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_store_video_thumbnail_requires_an_image(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/filepond/video-thumbnail', [
            'filepond-thumbnail' => UploadedFile::fake()->create('t.txt'),
        ])->assertUnprocessable();
    }

    public function test_store_video_returns_the_stored_path(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $path = $this->post('/api/filepond/video', [
            'filepond-video' => UploadedFile::fake()->create('v.mp4', 100, 'video/mp4'),
        ])->assertOk()->getContent();

        $this->assertStringStartsWith('videos/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_update_video_thumbnail_replaces_the_file(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Storage::disk('public')->put('video-thumbnails/old.jpg', 'x');
        $video = Video::factory()->create(['thumbnail' => 'video-thumbnails/old.jpg']);
        Sanctum::actingAs($video->user);

        $this->post("/api/filepond/video-thumbnail/{$video->id}", [
            'filepond-thumbnail' => UploadedFile::fake()->image('t.jpg'),
        ])->assertOk();

        Storage::disk('public')->assertMissing('video-thumbnails/old.jpg');
        $new = $video->refresh()->getRawOriginal('thumbnail');
        $this->assertStringStartsWith('video-thumbnails/', $new);
        Storage::disk('public')->assertExists($new);
    }

    public function test_update_video_replaces_the_file(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Storage::disk('public')->put('videos/old.mp4', 'x');
        $video = Video::factory()->create(['video' => 'videos/old.mp4']);
        Sanctum::actingAs($video->user);

        $this->post("/api/filepond/video/{$video->id}", [
            'filepond-video' => UploadedFile::fake()->create('v.mp4', 100, 'video/mp4'),
        ])->assertOk();

        Storage::disk('public')->assertMissing('videos/old.mp4');
        $new = $video->refresh()->getRawOriginal('video');
        $this->assertStringStartsWith('videos/', $new);
        Storage::disk('public')->assertExists($new);
    }

    public function test_destroy_video_thumbnail_and_video(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Storage::disk('public')->put('video-thumbnails/a.jpg', 'x');
        Storage::disk('public')->put('videos/a.mp4', 'x');

        $this->delete('/api/filepond/video-thumbnail/a.jpg')
            ->assertOk()
            ->assertSee('Video thumbnail deleted');
        $this->delete('/api/filepond/video/a.mp4')
            ->assertOk()
            ->assertSee('Video deleted');

        Storage::disk('public')->assertMissing('video-thumbnails/a.jpg');
        Storage::disk('public')->assertMissing('videos/a.mp4');
    }
}
