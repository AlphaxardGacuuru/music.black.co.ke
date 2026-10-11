<?php

namespace Tests\Feature\Music\Video;

use App\Models\User;
use App\Models\VideoAlbum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VideoAlbumTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_and_show_albums(): void
    {
        $album = VideoAlbum::factory()->create();

        $this->getJson('/api/video-albums')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['id', 'username', 'name', 'cover', 'released', 'createdAt']]]);

        $this->getJson("/api/video-albums/{$album->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $album->id)
            ->assertJsonPath('data.username', $album->user->username)
            ->assertJsonPath('data.cover', '/storage/' . $album->getRawOriginal('cover'));
    }

    public function test_guest_cannot_create_an_album(): void
    {
        $this->postJson('/api/video-albums', [])->assertUnauthorized();
    }

    public function test_cover_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/video-albums', ['name' => 'X'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cover');
    }

    public function test_user_can_create_an_album(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->post('/api/video-albums', [
            'name' => 'First',
            'released' => '2026-01-01',
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Video Album First created');

        $album = VideoAlbum::firstWhere('name', 'First');
        $this->assertSame($user->id, $album->user_id);
        $this->assertStringStartsWith('video-album-covers/', $album->getRawOriginal('cover'));
        Storage::disk('public')->assertExists($album->getRawOriginal('cover'));
    }

    public function test_user_can_update_an_album_and_old_cover_is_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('video-album-covers/old.jpg', 'x');
        $album = VideoAlbum::factory()->create(['cover' => 'video-album-covers/old.jpg']);

        Sanctum::actingAs($album->user);

        $this->post("/api/video-albums/{$album->id}", [
            '_method' => 'put',
            'name' => 'Renamed',
            'cover' => UploadedFile::fake()->image('new.jpg'),
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Renamed edited');

        Storage::disk('public')->assertMissing('video-album-covers/old.jpg');
        $album->refresh();
        $this->assertSame('Renamed', $album->name);
        Storage::disk('public')->assertExists($album->getRawOriginal('cover'));
    }

    public function test_artist_albums_are_listed_by_username(): void
    {
        $album = VideoAlbum::factory()->create();
        VideoAlbum::factory()->create();

        $this->getJson("/api/artist/video-albums/{$album->user->username}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $album->id);
    }
}
