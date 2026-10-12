<?php

namespace Tests\Feature\Music\Video;

use App\Events\VideoUploadedEvent;
use App\Models\BoughtVideo;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAlbum;
use App\Models\VideoLike;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_videos(): void
    {
        Video::factory()->count(2)->create();

        $this->getJson('/api/videos')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [[
                'id', 'video', 'name', 'artistName', 'username', 'avatar', 'artistDecos',
                'ft', 'videoAlbumId', 'album', 'genre', 'thumbnail', 'description',
                'released', 'hasLiked', 'likes', 'comments', 'inCart', 'hasBoughtVideo',
                'hasBought1', 'hasFollowed', 'downloads', 'createdAt',
            ]]]);
    }

    public function test_show_returns_the_video_with_usernames_and_user_state(): void
    {
        $ft = User::factory()->create();
        $video = Video::factory()->create(['ft_id' => $ft->id]);
        $viewer = User::factory()->create();
        VideoLike::factory()->create(['video_id' => $video->id, 'user_id' => $viewer->id]);

        Sanctum::actingAs($viewer);

        $this->getJson("/api/videos/{$video->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $video->id)
            ->assertJsonPath('data.username', $video->user->username)
            ->assertJsonPath('data.ft', $ft->username)
            ->assertJsonPath('data.album', $video->album->name)
            ->assertJsonPath('data.video', 'storage/' . $video->getRawOriginal('video'))
            ->assertJsonPath('data.hasLiked', true)
            ->assertJsonPath('data.likes', 1);
    }

    public function test_guest_cannot_store_a_video(): void
    {
        $this->postJson('/api/videos', [])->assertUnauthorized();
    }

    public function test_store_validates_input(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/videos', ['ft' => 'nobody'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['video', 'name', 'ft']);
    }

    public function test_user_can_store_a_video(): void
    {
        $user = User::factory()->create();
        $ft = User::factory()->create();
        $album = VideoAlbum::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        // Thumbnail generation is covered in ProcessVideoJobTest.
        Event::fake([VideoUploadedEvent::class]);

        $this->postJson('/api/videos', [
            'video' => 'videos/new.mp4',
            'name' => 'My Video',
            'thumbnail' => 'video-thumbnails/new.jpg',
            'ft' => $ft->username,
            'video_album_id' => $album->id,
            'genre' => 'Afro',
            'description' => 'Desc',
            'released' => '2026-01-01',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Video My Video uploaded');

        $this->assertDatabaseHas('videos', [
            'name' => 'My Video',
            'user_id' => $user->id,
            'ft_id' => $ft->id,
            'video' => 'videos/new.mp4',
            'video_album_id' => $album->id,
        ]);
    }

    public function test_user_can_update_a_video_and_old_files_are_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/old.mp4', 'x');
        Storage::disk('public')->put('video-thumbnails/old.jpg', 'x');

        $video = Video::factory()->create([
            'video' => 'videos/old.mp4',
            'thumbnail' => 'video-thumbnails/old.jpg',
        ]);
        $ft = User::factory()->create();

        Sanctum::actingAs($video->user);

        $this->putJson("/api/videos/{$video->id}", [
            'name' => 'Renamed',
            'ft' => $ft->username,
            'video' => 'videos/new.mp4',
            'thumbnail' => 'video-thumbnails/new.jpg',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Renamed edited');

        Storage::disk('public')->assertMissing('videos/old.mp4');
        Storage::disk('public')->assertMissing('video-thumbnails/old.jpg');

        $video->refresh();
        $this->assertSame('Renamed', $video->name);
        $this->assertSame($ft->id, $video->ft_id);
        $this->assertSame('videos/new.mp4', $video->getRawOriginal('video'));
        $this->assertSame('video-thumbnails/new.jpg', $video->getRawOriginal('thumbnail'));
    }

    public function test_download_returns_the_file_by_path_segment_and_query(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('videos/clip.mp4', 'video-bytes');

        $video = Video::factory()->create(['video' => 'videos/clip.mp4', 'name' => 'Clip']);
        // Downloads are for the artist and buyers (see PrivacyTest).
        Sanctum::actingAs($video->user);

        $this->get("/api/videos/download/{$video->id}")
            ->assertOk()
            ->assertDownload('Clip.mp4');

        $this->get("/api/videos/download?id={$video->id}")
            ->assertOk()
            ->assertDownload('Clip.mp4');
    }

    public function test_download_of_missing_video_is_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->get('/api/videos/download/does-not-exist')->assertNotFound();
    }

    public function test_artist_videos_are_listed_by_username(): void
    {
        $video = Video::factory()->create();
        Video::factory()->create();

        $this->getJson("/api/artist/videos/{$video->user->username}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $video->id);
    }

    public function test_newly_released_chart(): void
    {
        $old = Video::factory()->create(['created_at' => now()->subDay()]);
        $new = Video::factory()->create();

        $this->getJson('/api/video-charts/newly-released')
            ->assertOk()
            ->assertJsonCount(2, 'data.videos')
            ->assertJsonCount(2, 'data.artists')
            ->assertJsonPath('data.videos.0.id', $new->id)
            ->assertJsonPath('data.videos.1.id', $old->id);
    }

    public function test_top_liked_chart(): void
    {
        $top = Video::factory()->create();
        $other = Video::factory()->create();
        VideoLike::factory()->count(2)->create(['video_id' => $top->id]);
        VideoLike::factory()->create(['video_id' => $other->id]);

        $this->getJson('/api/video-charts/top-liked')
            ->assertOk()
            ->assertJsonPath('data.videos.0.id', $top->id)
            ->assertJsonPath('data.videos.1.id', $other->id)
            ->assertJsonPath('data.artists.0.username', $top->user->username);
    }

    public function test_top_downloaded_and_trending_charts(): void
    {
        $recent = Video::factory()->create();
        $old = Video::factory()->create();

        $buy = fn (Video $video, $when) => BoughtVideo::forceCreate([
            'video_id' => $video->id,
            'user_id' => User::factory()->create()->id,
            'artist_id' => $video->user_id,
            'price' => BoughtVideo::PRICE,
            'created_at' => $when,
            'updated_at' => $when,
        ]);

        $buy($recent, now());
        $buy($old, now()->subMonth());
        $buy($old, now()->subMonth());

        $this->getJson('/api/video-charts/top-downloaded')
            ->assertOk()
            ->assertJsonCount(2, 'data.videos')
            ->assertJsonPath('data.videos.0.id', $old->id);

        $this->getJson('/api/video-charts/trending')
            ->assertOk()
            ->assertJsonCount(1, 'data.videos')
            ->assertJsonPath('data.videos.0.id', $recent->id);
    }
}
