<?php

namespace Tests\Feature\Music\Social;

use App\Events\PostCommentedEvent;
use App\Events\PostCommentLikedEvent;
use App\Events\PostLikedEvent;
use App\Listeners\PostCommentedListener;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use App\Notifications\PostCommentedNotification;
use App\Notifications\PostCommentLikedNotification;
use App\Notifications\PostLikedNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostLikeAndCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_like_or_comment(): void
    {
        $this->postJson('/api/post-likes', [])->assertUnauthorized();
        $this->postJson('/api/post-comments', [])->assertUnauthorized();
        $this->postJson('/api/post-comment-likes', [])->assertUnauthorized();
        $this->postJson('/api/polls', [])->assertUnauthorized();
    }

    public function test_like_toggles_and_notifies_owner(): void
    {
        Notification::fake();
        $post = Post::factory()->create();
        $liker = User::factory()->create();
        Sanctum::actingAs($liker);

        $this->postJson('/api/post-likes', ['post' => $post->id])
            ->assertOk()
            ->assertJsonPath('message', 'Post liked');
        $this->assertSame(1, $post->likes()->count());

        Notification::assertSentTo($post->user, PostLikedNotification::class, function ($notification) use ($post, $liker) {
            $data = $notification->toArray($post->user);

            return $data['url'] === '/post/' . $post->id . '/view'
                && $data['from'] === $liker->username
                && $data['message'] === $liker->username.' liked your post.';
        });

        $this->postJson('/api/post-likes', ['post' => $post->id])
            ->assertOk()
            ->assertJsonPath('message', 'Like removed');
        $this->assertSame(0, $post->likes()->count());
    }

    public function test_liking_own_post_does_not_dispatch(): void
    {
        Event::fake([PostLikedEvent::class]);
        $post = Post::factory()->create();
        Sanctum::actingAs($post->user);

        $this->postJson('/api/post-likes', ['post' => $post->id])->assertOk();

        Event::assertNotDispatched(PostLikedEvent::class);
    }

    public function test_comments_index_and_show(): void
    {
        $post = Post::factory()->create();
        PostComment::factory()->count(2)->create(['post_id' => $post->id]);
        PostComment::factory()->create();

        $this->getJson('/api/post-comments')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->getJson("/api/post-comments/{$post->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [[
                'id', 'post_id', 'name', 'username', 'decos', 'avatar', 'text', 'likes', 'hasLiked', 'created_at',
            ]]]);
    }

    public function test_comment_validates_text(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/post-comments', ['id' => Post::factory()->create()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    public function test_comment_is_saved_broadcast_and_notified(): void
    {
        Notification::fake();
        Event::fake([PostCommentedEvent::class]);
        $post = Post::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/post-comments', ['id' => $post->id, 'text' => 'Nice'])
            ->assertOk()
            ->assertJsonPath('message', 'Comment posted');

        $comment = PostComment::firstWhere('text', 'Nice');
        $this->assertSame($user->id, $comment->user_id);

        Event::assertDispatched(PostCommentedEvent::class, fn (PostCommentedEvent $event) => $event->comment->is($comment)
            && $event->broadcastOn()[0] == new PrivateChannel('post-commented'));

        // The listener notifies the post owner
        (new PostCommentedListener)->handle(new PostCommentedEvent($comment, $post, $user));
        Notification::assertSentTo($post->user, PostCommentedNotification::class, fn ($n) => $n->toArray($post->user)['message'] === $user->username.' commented on '.$post->text);
    }

    public function test_comment_can_be_deleted(): void
    {
        $comment = PostComment::factory()->create();
        Sanctum::actingAs($comment->user);

        $this->deleteJson("/api/post-comments/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Comment deleted');

        $this->assertModelMissing($comment);
    }

    public function test_comment_like_toggles_and_notifies_comment_owner(): void
    {
        Notification::fake();
        $comment = PostComment::factory()->create();
        $liker = User::factory()->create();
        Sanctum::actingAs($liker);

        $this->postJson('/api/post-comment-likes', ['comment' => $comment->id])
            ->assertOk()
            ->assertJsonPath('message', 'Comment liked');

        Notification::assertSentTo($comment->user, PostCommentLikedNotification::class, function ($n) use ($comment, $liker) {
            return $n->toArray($comment->user)['message'] === $liker->username.' liked your comment on '.$comment->post->text;
        });

        $this->postJson('/api/post-comment-likes', ['comment' => $comment->id])
            ->assertOk()
            ->assertJsonPath('message', 'Like removed');
        $this->assertSame(0, $comment->likes()->count());
    }

    public function test_liking_own_comment_does_not_dispatch(): void
    {
        Event::fake([PostCommentLikedEvent::class]);
        $comment = PostComment::factory()->create();
        Sanctum::actingAs($comment->user);

        $this->postJson('/api/post-comment-likes', ['comment' => $comment->id])->assertOk();

        Event::assertNotDispatched(PostCommentLikedEvent::class);
    }

    public function test_poll_vote_toggles(): void
    {
        $post = Post::factory()->create(['parameter_1' => 'A', 'parameter_2' => 'B']);
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/polls', ['post' => $post->id, 'parameter' => 'A'])
            ->assertOk()
            ->assertExactJson(['status' => true, 'message' => 'Voted']);
        $this->assertDatabaseHas('polls', ['post_id' => $post->id, 'user_id' => $user->id, 'parameter' => 'A']);

        $this->postJson('/api/polls', ['post' => $post->id, 'parameter' => 'A'])
            ->assertOk()
            ->assertExactJson(['status' => false, 'message' => 'Vote removed']);
        $this->assertDatabaseMissing('polls', ['post_id' => $post->id]);
    }
}
