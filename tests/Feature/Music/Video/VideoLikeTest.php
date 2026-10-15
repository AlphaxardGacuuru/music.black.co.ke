<?php

namespace Tests\Feature\Music\Video;

use App\Events\VideoLikedEvent;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoLike;
use App\Notifications\VideoLikedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VideoLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_like(): void
    {
        $this->postJson('/api/video-likes', [])->assertUnauthorized();
    }

    public function test_video_must_exist(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/video-likes', ['video' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('video');
    }

    public function test_like_toggles_and_dispatches_event_only_when_added(): void
    {
        Event::fake([VideoLikedEvent::class]);
        $video = Video::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/video-likes', ['video' => $video->id])
            ->assertOk()
            ->assertJsonPath('message', 'Video liked');
        $this->assertDatabaseHas('video_likes', ['video_id' => $video->id, 'user_id' => $user->id]);
        Event::assertDispatchedTimes(VideoLikedEvent::class, 1);

        $this->postJson('/api/video-likes', ['video' => $video->id])
            ->assertOk()
            ->assertJsonPath('message', 'Like removed');
        $this->assertDatabaseMissing('video_likes', ['video_id' => $video->id, 'user_id' => $user->id]);
        Event::assertDispatchedTimes(VideoLikedEvent::class, 1);
    }

    public function test_owner_liking_own_video_does_not_dispatch(): void
    {
        Event::fake([VideoLikedEvent::class]);
        $video = Video::factory()->create();
        Sanctum::actingAs($video->user);

        $this->postJson('/api/video-likes', ['video' => $video->id])->assertOk();

        Event::assertNotDispatched(VideoLikedEvent::class);
    }

    public function test_owner_is_notified(): void
    {
        Notification::fake();
        $video = Video::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/video-likes', ['video' => $video->id])->assertOk();

        Notification::assertSentTo($video->user, VideoLikedNotification::class, function($notification, $channels) use ($video, $user) {
            $data = $notification->toArray($video->user);

            return $channels == ['database', 'broadcast']
                && $data['url'] == '/videos/' . $video->id . '/view'
                && $data['from'] == $user->username
                && $data['message'] == $user->username . ' liked ' . $video->name;
        });
    }

    public function test_has_liked_counts_only_current_user(): void
    {
        $like = VideoLike::factory()->create();

        $this->getJson("/api/videos/{$like->video_id}")
            ->assertJsonPath('data.hasLiked', false)
            ->assertJsonPath('data.likes', 1);
    }
}
