<?php

namespace Tests\Feature\Music;

use App\Events\VideoUploadedEvent;
use App\Jobs\ProcessVideoJob;
use App\Models\Deco;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAlbum;
use App\Notifications\StoryCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Mockery;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Tests\TestCase;

class FeedAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_feed_shows_posts_from_musicians_the_viewer_follows(): void
    {
        $blackMusic = User::factory()->black()->create();
        $viewer = User::factory()->create();
        $followed = User::factory()->musician()->create();
        $onlyBlackMusicFollows = User::factory()->musician()->create();

        Follow::create(['user_id' => $viewer->id, 'followed_id' => $followed->id, 'muted' => ['posts' => false, 'stories' => false]]);
        Follow::create(['user_id' => $blackMusic->id, 'followed_id' => $onlyBlackMusicFollows->id, 'muted' => ['posts' => false, 'stories' => false]]);

        $followedPost = Post::factory()->for($followed)->create();
        Post::factory()->for($onlyBlackMusicFollows)->create();

        $ids = collect($this->actingAs($viewer, 'sanctum')->getJson('/api/posts')->assertOk()->json('data'))->pluck('id');

        $this->assertContains($followedPost->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_the_feed_skips_musicians_the_viewer_muted(): void
    {
        $viewer = User::factory()->create();
        $muted = User::factory()->musician()->create();
        Follow::create(['user_id' => $viewer->id, 'followed_id' => $muted->id, 'muted' => ['posts' => true, 'stories' => true]]);
        Post::factory()->for($muted)->create();
        Story::factory()->for($muted)->create();

        $this->actingAs($viewer, 'sanctum')->getJson('/api/posts')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($viewer, 'sanctum')->getJson('/api/stories')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_the_story_feed_shows_stories_from_musicians_the_viewer_follows(): void
    {
        $viewer = User::factory()->create();
        $followed = User::factory()->musician()->create();
        Follow::create(['user_id' => $viewer->id, 'followed_id' => $followed->id, 'muted' => ['posts' => false, 'stories' => false]]);
        $story = Story::factory()->for($followed)->create();
        Story::factory()->for(User::factory()->musician())->create();

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/stories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $story->id);
    }

    public function test_post_channels_match_the_names_events_broadcast_on(): void
    {
        $channels = array_keys(Broadcast::getChannels()->all());

        $this->assertContains('post-created', $channels);
        $this->assertContains('post-commented', $channels);
    }

    public function test_followers_are_notified_of_a_new_story_unless_they_muted_stories(): void
    {
        Notification::fake();

        $artist = User::factory()->musician()->create();
        $fan = User::factory()->create();
        $mutedFan = User::factory()->create();
        Follow::create(['user_id' => $fan->id, 'followed_id' => $artist->id, 'muted' => ['posts' => false, 'stories' => false]]);
        Follow::create(['user_id' => $mutedFan->id, 'followed_id' => $artist->id, 'muted' => ['posts' => false, 'stories' => true]]);

        $this->actingAs($artist, 'sanctum')
            ->postJson('/api/stories', ['media' => '{"image":"stories/a.jpg"}'])
            ->assertOk();

        Notification::assertSentTo($fan, StoryCreatedNotification::class);
        Notification::assertNotSentTo($mutedFan, StoryCreatedNotification::class);
        Notification::assertNotSentTo($artist, StoryCreatedNotification::class);
    }

    public function test_earning_a_deco_updates_the_fans_count(): void
    {
        $fan = User::factory()->create();
        $artist = User::factory()->musician()->create();

        $deco = Deco::create(['user_id' => $fan->id, 'artist_id' => $artist->id]);
        $this->assertSame(1, $fan->fresh()->decos);

        $deco->delete();
        $this->assertSame(0, $fan->fresh()->decos);
    }

    public function test_uploading_a_video_without_a_thumbnail_starts_thumbnail_generation(): void
    {
        Event::fake([VideoUploadedEvent::class]);
        $artist = User::factory()->musician()->create();
        $album = VideoAlbum::factory()->for($artist)->create();

        $this->actingAs($artist, 'sanctum')
            ->postJson('/api/videos', [
                'video' => 'videos/new.mp4',
                'name' => 'New song',
                'video_album_id' => $album->id,
                'genre' => 'Afro',
            ])
            ->assertOk();

        Event::assertDispatched(VideoUploadedEvent::class);
    }

    public function test_the_generated_frame_becomes_the_thumbnail_only_when_none_was_uploaded(): void
    {
        $artist = User::factory()->musician()->create();
        $album = VideoAlbum::factory()->for($artist)->create();
        $withoutThumbnail = Video::factory()->for($artist)->create(['video_album_id' => $album->id, 'video' => 'videos/a.mp4', 'thumbnail' => null]);
        $withThumbnail = Video::factory()->for($artist)->create(['video_album_id' => $album->id, 'video' => 'videos/b.mp4', 'thumbnail' => 'video-thumbnails/mine.jpg']);

        $this->fakeFfmpeg(times: 2, duration: 4);

        (new ProcessVideoJob($withoutThumbnail))->handle();
        (new ProcessVideoJob($withThumbnail))->handle();

        $this->assertSame('video-thumbnails/a.jpg', $withoutThumbnail->fresh()->getRawOriginal('thumbnail'));
        $this->assertSame('video-thumbnails/mine.jpg', $withThumbnail->fresh()->getRawOriginal('thumbnail'));
    }

    /**
     * Fake FFMpeg; a clip shorter than 20 seconds is grabbed halfway through.
     */
    private function fakeFfmpeg(int $times, int $duration): void
    {
        $media = Mockery::mock();
        $exporter = Mockery::mock();

        FFMpeg::shouldReceive('fromDisk')->times($times)->with('public')->andReturn($media);
        $media->shouldReceive('open')->andReturnSelf();
        $media->shouldReceive('getDurationInSeconds')->andReturn($duration);
        $media->shouldReceive('getFrameFromSeconds')->with(intdiv($duration, 2))->andReturnSelf();
        $media->shouldReceive('export')->andReturn($exporter);
        $exporter->shouldReceive('toDisk')->with('public')->andReturnSelf();
        $exporter->shouldReceive('save');
    }
}
