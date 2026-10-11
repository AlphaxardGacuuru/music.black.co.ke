<?php

namespace Tests\Feature\Music\Video;

use App\Events\VideoCommentedEvent;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoComment;
use App\Models\VideoCommentLike;
use App\Notifications\VideoCommentedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VideoCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_comments(): void
    {
        VideoComment::factory()->count(2)->create();

        $this->getJson('/api/video-comments')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'videoId', 'text', 'username', 'name', 'avatar', 'hasLiked', 'likes', 'createdAt']]]);
    }

    public function test_show_returns_paginated_comments_of_a_video_newest_first(): void
    {
        $video = Video::factory()->create();
        $old = VideoComment::factory()->create(['video_id' => $video->id, 'created_at' => now()->subDay()]);
        $new = VideoComment::factory()->create(['video_id' => $video->id]);
        VideoComment::factory()->create();
        VideoCommentLike::factory()->create(['video_comment_id' => $new->id, 'user_id' => $new->user_id]);

        Sanctum::actingAs($new->user);

        $this->getJson("/api/video-comments/{$video->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.0.username', $new->user->username)
            ->assertJsonPath('data.0.hasLiked', true)
            ->assertJsonPath('data.0.likes', 1)
            ->assertJsonPath('data.1.id', $old->id)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_guest_cannot_comment(): void
    {
        $this->postJson('/api/video-comments', [])->assertUnauthorized();
    }

    public function test_comment_requires_text(): void
    {
        $video = Video::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/video-comments', ['id' => $video->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');
    }

    public function test_user_can_comment_and_owner_is_notified(): void
    {
        Notification::fake();
        $video = Video::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/video-comments', ['id' => $video->id, 'text' => 'Nice'])
            ->assertOk()
            ->assertJsonPath('message', 'Comment posted');

        $this->assertDatabaseHas('video_comments', ['video_id' => $video->id, 'user_id' => $user->id, 'text' => 'Nice']);

        Notification::assertSentTo($video->user, VideoCommentedNotification::class, function($notification) use ($video, $user) {
            return $notification->toArray($video->user)['message'] == $user->username . ' commented on ' . $video->name;
        });
    }

    public function test_owner_commenting_does_not_dispatch(): void
    {
        Event::fake([VideoCommentedEvent::class]);
        $video = Video::factory()->create();
        Sanctum::actingAs($video->user);

        $this->postJson('/api/video-comments', ['id' => $video->id, 'text' => 'Mine'])->assertOk();

        Event::assertNotDispatched(VideoCommentedEvent::class);
    }

    public function test_user_can_delete_a_comment(): void
    {
        $comment = VideoComment::factory()->create();
        Sanctum::actingAs($comment->user);

        $this->deleteJson("/api/video-comments/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Comment deleted');

        $this->assertModelMissing($comment);
    }
}
