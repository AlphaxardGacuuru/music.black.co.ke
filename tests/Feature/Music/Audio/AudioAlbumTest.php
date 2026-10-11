<?php

namespace Tests\Feature\Music\Audio;

use App\Models\AudioAlbum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AudioAlbumTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_list_and_show_albums(): void
    {
        $album = AudioAlbum::factory()->create();

        $this->getJson('/api/audio-albums')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/audio-albums/{$album->id}")
            ->assertOk()
            ->assertJsonPath('data.username', $album->user->username)
            ->assertJsonStructure(['data' => ['id', 'username', 'name', 'cover', 'released', 'createdAt']]);
    }

    public function test_guests_cannot_create_albums(): void
    {
        $this->postJson('/api/audio-albums', [])->assertUnauthorized();
    }

    public function test_store_validates_input(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/audio-albums', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'released', 'cover']);
    }

    public function test_user_can_create_album(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->post('/api/audio-albums', [
            'name' => 'First',
            'released' => '2026-01-01',
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', 'Audio Album First created.');

        $album = AudioAlbum::firstWhere('name', 'First');
        $this->assertSame($user->id, $album->user_id);
        $this->assertStringStartsWith('audio-album-covers/', $album->getRawOriginal('cover'));
        Storage::disk('public')->assertExists($album->getRawOriginal('cover'));
    }

    public function test_user_can_update_album_and_replace_cover(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audio-album-covers/old.jpg', 'x');
        $album = AudioAlbum::factory()->create(['cover' => 'audio-album-covers/old.jpg']);
        Sanctum::actingAs($album->user);

        $this->post("/api/audio-albums/{$album->id}", [
            '_method' => 'PUT',
            'name' => 'Renamed',
            'cover' => UploadedFile::fake()->image('new.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', 'Renamed updated');

        Storage::disk('public')->assertMissing('audio-album-covers/old.jpg');
        Storage::disk('public')->assertExists($album->fresh()->getRawOriginal('cover'));
    }

    public function test_artist_audio_albums(): void
    {
        $album = AudioAlbum::factory()->create();
        AudioAlbum::factory()->create();

        $this->getJson("/api/artist/audio-albums/{$album->user->username}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $album->id);
    }
}
