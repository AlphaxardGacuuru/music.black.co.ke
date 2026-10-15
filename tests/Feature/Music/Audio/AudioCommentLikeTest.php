<?php

namespace Tests\Feature\Music\Audio;

use App\Events\AudioCommentLikedEvent;
use App\Models\AudioComment;
use App\Models\User;
use App\Notifications\AudioCommentLikedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AudioCommentLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_like_comments(): void
    {
        $this->postJson('/api/audio-comment-likes', [])->assertUnauthorized();
    }

    public function test_like_requires_an_existing_comment(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/audio-comment-likes', ['comment' => 'missing'])
            ->assertJsonValidationErrors(['comment']);
    }

    public function test_comment_like_toggles_and_notifies_the_commenter(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $comment = AudioComment::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/audio-comment-likes', ['comment' => $comment->id])
            ->assertOk()
            ->assertJsonPath('message', 'Comment liked');

        $this->assertDatabaseHas('audio_comment_likes', ['audio_comment_id' => $comment->id, 'user_id' => $user->id]);

        Notification::assertSentTo($comment->user, AudioCommentLikedNotification::class, function ($notification) use ($comment, $user) {
            $data = $notification->toArray($comment->user);

            return $data['url'] === '/audios/' . $comment->audio_id . '/view'
                && $data['from'] === $user->username
                && $data['message'] === $user->username . ' liked your comment on ' . $comment->audio->name;
        });

        $this->postJson('/api/audio-comment-likes', ['comment' => $comment->id])
            ->assertOk()
            ->assertJsonPath('message', 'Like removed');

        $this->assertDatabaseMissing('audio_comment_likes', ['audio_comment_id' => $comment->id, 'user_id' => $user->id]);
    }

    public function test_liking_own_comment_does_not_dispatch(): void
    {
        Event::fake([AudioCommentLikedEvent::class]);
        $comment = AudioComment::factory()->create();
        Sanctum::actingAs($comment->user);

        $this->postJson('/api/audio-comment-likes', ['comment' => $comment->id])->assertOk();

        Event::assertNotDispatched(AudioCommentLikedEvent::class);
    }
}
