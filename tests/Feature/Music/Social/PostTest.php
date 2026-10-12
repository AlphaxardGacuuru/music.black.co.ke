<?php

namespace Tests\Feature\Music\Social;

use App\Events\PostedEvent;
use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    protected User $blackMusic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blackMusic = User::factory()->black()->create();
    }

    public function test_guests_only_see_blackmusic_posts(): void
    {
        Post::factory()->create(['user_id' => $this->blackMusic->id]);
        Post::factory()->create();

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', '@blackmusic');
    }

    public function test_users_see_posts_of_musicians_they_follow_and_not_muted(): void
    {
        $viewer = User::factory()->create();
        $musician = User::factory()->create();
        $muted = User::factory()->create();
        Follow::factory()->create(['followed_id' => $musician->id, 'user_id' => $viewer->id]);
        Follow::factory()->create([
            'followed_id' => $muted->id,
            'user_id' => $viewer->id,
            'muted' => ['posts' => true, 'stories' => false],
        ]);

        Post::factory()->create(['user_id' => $musician->id]);
        Post::factory()->create(['user_id' => $muted->id]);
        Post::factory()->create(['user_id' => $this->blackMusic->id]);
        Post::factory()->create();

        Sanctum::actingAs($viewer);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [[
                'id', 'name', 'username', 'avatar', 'decos', 'text', 'media',
                'parameter1', 'parameter5', 'hasVoted1', 'percentage1', 'winner',
                'totalVotes', 'isWithin24Hrs', 'hasMuted', 'hasFollowed', 'hasLiked',
                'hasEdited', 'likes', 'comments', 'updatedAt', 'createdAt',
            ]], 'links', 'meta']);
    }

    public function test_show_returns_post(): void
    {
        $post = Post::factory()->create();

        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonPath('data.username', $post->user->username)
            ->assertJsonPath('data.media', '/storage/'.$post->getRawOriginal('media'));
    }

    public function test_guests_cannot_post(): void
    {
        $this->postJson('/api/posts', ['text' => 'Hi'])->assertUnauthorized();
    }

    public function test_store_validates_text(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/posts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    public function test_user_can_post_a_poll_and_event_is_dispatched(): void
    {
        Event::fake([PostedEvent::class]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'text' => 'Which?',
            'media' => 'post-media/a.jpg',
            'para1' => 'A',
            'para2' => 'B',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Post posted');

        $post = Post::firstWhere('text', 'Which?');
        $this->assertSame($user->id, $post->user_id);
        $this->assertSame('A', $post->parameter_1);
        $this->assertSame('', $post->parameter_3);

        Event::assertDispatched(PostedEvent::class, function (PostedEvent $event) use ($post) {
            return $event->post->is($post)
                && $event->broadcastOn()[0] == new PrivateChannel('post-created');
        });
    }

    public function test_user_can_edit_post(): void
    {
        $post = Post::factory()->create();
        Sanctum::actingAs($post->user);

        $this->putJson("/api/posts/{$post->id}", ['text' => 'Edited'])
            ->assertOk()
            ->assertJsonPath('message', 'Post edited');

        $this->assertSame('Edited', $post->fresh()->text);
    }

    public function test_user_can_delete_post_and_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('post-media/a.jpg', 'x');
        $post = Post::factory()->create(['media' => 'post-media/a.jpg']);
        Sanctum::actingAs($post->user);

        $this->deleteJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Post deleted');

        $this->assertModelMissing($post);
        Storage::disk('public')->assertMissing('post-media/a.jpg');
    }

    public function test_user_can_mute_and_unmute_posts(): void
    {
        $user = User::factory()->create();
        $musician = User::factory()->create();
        $follow = Follow::factory()->create(['followed_id' => $musician->id, 'user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->putJson("/api/posts/mute/{$musician->username}")
            ->assertOk()
            ->assertJsonPath('message', 'You muted posts from '.$musician->username);
        $this->assertTrue($follow->fresh()->muted['posts']);

        $this->putJson("/api/posts/mute/{$musician->username}")
            ->assertOk()
            ->assertJsonPath('message', 'You unmuted posts from '.$musician->username);
        $this->assertFalse($follow->fresh()->muted['posts']);
    }

    public function test_guests_cannot_mute(): void
    {
        $this->putJson('/api/posts/mute/@someone')->assertUnauthorized();
    }

    public function test_artist_posts_only_lists_posts_of_followed_artist(): void
    {
        $user = User::factory()->create();
        $musician = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $musician->id]);
        Post::factory()->create();

        // Not following yet
        Sanctum::actingAs($user);
        $this->getJson("/api/artist/posts/{$musician->username}")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Follow::factory()->create(['followed_id' => $musician->id, 'user_id' => $user->id]);

        $this->getJson("/api/artist/posts/{$musician->username}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.hasFollowed', true);
    }

    public function test_poll_percentages_and_winner(): void
    {
        $post = Post::factory()->create(['parameter_1' => 'A', 'parameter_2' => 'B']);
        $voter = User::factory()->create();
        $post->polls()->create(['user_id' => $voter->id, 'parameter' => 'A']);
        $post->polls()->create(['user_id' => User::factory()->create()->id, 'parameter' => 'A']);
        $post->polls()->create(['user_id' => User::factory()->create()->id, 'parameter' => 'B']);

        Sanctum::actingAs($voter);

        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.hasVoted1', true)
            ->assertJsonPath('data.hasVoted2', false)
            ->assertJsonPath('data.percentage1', 66.7)
            ->assertJsonPath('data.winner', 'A')
            ->assertJsonPath('data.totalVotes', 3);
    }
}
