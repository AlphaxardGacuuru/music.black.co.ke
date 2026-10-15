<?php

namespace Tests\Feature\Music\Video;

use App\Events\VideoCommentLikedEvent;
use App\Models\User;
use App\Models\VideoComment;
use App\Notifications\VideoCommentLikedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VideoCommentLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_like_a_comment(): void
    {
        $this->postJson('/api/video-comment-likes', [])->assertUnauthorized();
    }

    public function test_comment_must_exist(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/video-comment-likes', ['comment' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('comment');
    }

    public function test_like_toggles_and_comment_owner_is_notified(): void
    {
        Notification::fake();
        $comment = VideoComment::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/video-comment-likes', ['comment' => $comment->id])
            ->assertOk()
            ->assertJsonPath('message', 'Comment liked');
        $this->assertDatabaseHas('video_comment_likes', ['video_comment_id' => $comment->id, 'user_id' => $user->id]);

        Notification::assertSentTo($comment->user, VideoCommentLikedNotification::class, function($notification) use ($comment, $user) {
            $data = $notification->toArray($comment->user);

            return $data['url'] == '/videos/' . $comment->video->id . '/view'
                && $data['message'] == $user->username . ' liked your comment on ' . $comment->video->name;
        });

        $this->postJson('/api/video-comment-likes', ['comment' => $comment->id])
            ->assertOk()
            ->assertJsonPath('message', 'Like removed');
        $this->assertDatabaseMissing('video_comment_likes', ['video_comment_id' => $comment->id, 'user_id' => $user->id]);
        Notification::assertSentToTimes($comment->user, VideoCommentLikedNotification::class, 1);
    }

    public function test_owner_liking_own_comment_does_not_dispatch(): void
    {
        Event::fake([VideoCommentLikedEvent::class]);
        $comment = VideoComment::factory()->create();
        Sanctum::actingAs($comment->user);

        $this->postJson('/api/video-comment-likes', ['comment' => $comment->id])->assertOk();

        Event::assertNotDispatched(VideoCommentLikedEvent::class);
    }
}
