<?php

namespace Tests\Feature\Music\Audio;

use App\Models\Audio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AudioFilePondTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_audio_thumbnail_returns_the_stored_path(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Storage::fake('public');

        $path = $this->post('/api/filepond/audio-thumbnail', [
            'filepond-thumbnail' => UploadedFile::fake()->image('thumb.jpg'),
        ])->assertOk()->getContent();

        $this->assertStringStartsWith('audio-thumbnails/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_store_audio_returns_the_stored_path(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Storage::fake('public');

        $path = $this->post('/api/filepond/audio', [
            'filepond-audio' => UploadedFile::fake()->create('song.mp3', 10, 'audio/mpeg'),
        ])->assertOk()->getContent();

        $this->assertStringStartsWith('audios/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_store_validates_the_file_field(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/filepond/audio-thumbnail', [])
            ->assertJsonValidationErrors(['filepond-thumbnail']);

        $this->postJson('/api/filepond/audio', [])
            ->assertJsonValidationErrors(['filepond-audio']);
    }

    public function test_update_audio_thumbnail_replaces_the_old_file(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Storage::fake('public');
        Storage::disk('public')->put('audio-thumbnails/old.jpg', 'x');
        $audio = Audio::factory()->create(['thumbnail' => 'audio-thumbnails/old.jpg']);
        Sanctum::actingAs($audio->user);

        $this->post("/api/filepond/audio-thumbnail/{$audio->id}", [
            'filepond-thumbnail' => UploadedFile::fake()->image('new.jpg'),
        ])->assertOk();

        $new = $audio->fresh()->getRawOriginal('thumbnail');
        $this->assertNotSame('audio-thumbnails/old.jpg', $new);
        Storage::disk('public')->assertExists($new);
        Storage::disk('public')->assertMissing('audio-thumbnails/old.jpg');
    }

    public function test_update_audio_replaces_the_old_file(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Storage::fake('public');
        Storage::disk('public')->put('audios/old.mp3', 'x');
        $audio = Audio::factory()->create(['audio' => 'audios/old.mp3']);
        Sanctum::actingAs($audio->user);

        $this->post("/api/filepond/audio/{$audio->id}", [
            'filepond-audio' => UploadedFile::fake()->create('new.mp3', 10, 'audio/mpeg'),
        ])->assertOk();

        $new = $audio->fresh()->getRawOriginal('audio');
        $this->assertNotSame('audios/old.mp3', $new);
        Storage::disk('public')->assertExists($new);
        Storage::disk('public')->assertMissing('audios/old.mp3');
    }

    public function test_destroy_endpoints_delete_files(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Storage::fake('public');
        Storage::disk('public')->put('audio-thumbnails/a.jpg', 'x');
        Storage::disk('public')->put('audios/a.mp3', 'x');

        $this->delete('/api/filepond/audio-thumbnail/a.jpg')
            ->assertOk()
            ->assertSee('Audio thumbnail deleted');

        $this->delete('/api/filepond/audio/a.mp3')
            ->assertOk()
            ->assertSee('Audio deleted');

        Storage::disk('public')->assertMissing('audio-thumbnails/a.jpg');
        Storage::disk('public')->assertMissing('audios/a.mp3');
    }
}
