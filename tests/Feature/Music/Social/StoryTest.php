<?php

namespace Tests\Feature\Music\Social;

use App\Events\StoryCreatedEvent;
use App\Models\Follow;
use App\Models\SeenStory;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $blackMusic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blackMusic = User::factory()->black()->create();
    }

    public function test_guests_only_see_blackmusic_stories(): void
    {
        Story::factory()->create(['user_id' => $this->blackMusic->id]);
        Story::factory()->create();

        $this->getJson('/api/stories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', '@blackmusic')
            ->assertJsonPath('data.0.hasMuted', false)
            ->assertJsonPath('data.0.hasSeen', false);
    }

    public function test_users_see_unmuted_stories_of_musicians_they_follow(): void
    {
        $viewer = User::factory()->create();
        $musician = User::factory()->create();
        $muted = User::factory()->create();
        Follow::factory()->create(['followed_id' => $musician->id, 'user_id' => $viewer->id]);
        Follow::factory()->create([
            'followed_id' => $muted->id,
            'user_id' => $viewer->id,
            'muted' => ['posts' => false, 'stories' => true],
        ]);
        Story::factory()->create(['user_id' => $musician->id]);
        Story::factory()->create(['user_id' => $muted->id]);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/stories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', $musician->username)
            ->assertJsonStructure(['data' => [['id', 'name', 'username', 'avatar', 'media', 'text', 'hasMuted', 'hasSeen']]]);
    }

    public function test_show_returns_story(): void
    {
        $story = Story::factory()->create();

        $this->getJson("/api/stories/{$story->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $story->id)
            ->assertJsonPath('data.media.0.image', $story->media[0]['image']);
    }

    public function test_guests_cannot_post_stories(): void
    {
        $this->postJson('/api/stories', ['media' => '{"image":"stories/a.jpg"}'])->assertUnauthorized();
        $this->postJson('/api/stories/seen/'.Story::factory()->create()->id)->assertUnauthorized();
        $this->putJson('/api/stories/mute/@someone')->assertUnauthorized();
    }

    public function test_store_validates_media(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/stories', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['media']);
    }

    public function test_user_can_post_story(): void
    {
        Event::fake([StoryCreatedEvent::class]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/stories', [
            'media' => '{"image":"stories/a.jpg"},{"video":"stories/b.mp4"}',
            'text' => 'Hello',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Story posted');

        $story = Story::firstWhere('user_id', $user->id);
        $this->assertSame([['image' => 'stories/a.jpg'], ['video' => 'stories/b.mp4']], $story->media);

        Event::assertDispatched(StoryCreatedEvent::class, fn (StoryCreatedEvent $event) => $event->story->is($story));
    }

    public function test_story_listener_sends_no_notification(): void
    {
        Notification::fake();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/stories', ['media' => '{"image":"stories/a.jpg"}'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_user_can_delete_story_and_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('stories/a.jpg', 'x');
        Storage::disk('public')->put('stories/b.mp4', 'x');
        $story = Story::factory()->create(['media' => [['image' => 'stories/a.jpg'], ['video' => 'stories/b.mp4']]]);
        Sanctum::actingAs($story->user);

        $this->deleteJson("/api/stories/{$story->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Story deleted');

        $this->assertModelMissing($story);
        Storage::disk('public')->assertMissing(['stories/a.jpg', 'stories/b.mp4']);
    }

    public function test_stories_older_than_a_day_are_pruned_with_their_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('stories/old.jpg', 'x');
        Storage::disk('public')->put('stories/old.mp4', 'x');
        Storage::disk('public')->put('stories/new.jpg', 'x');

        $expired = Story::factory()->create([
            'media' => [['image' => 'stories/old.jpg'], ['video' => 'stories/old.mp4']],
            'created_at' => now()->subHours(25),
        ]);
        SeenStory::factory()->create(['story_id' => $expired->id]);
        $fresh = Story::factory()->create([
            'media' => [['image' => 'stories/new.jpg']],
            'created_at' => now()->subHours(23),
        ]);

        $this->artisan('model:prune', ['--model' => [Story::class]])->assertSuccessful();

        $this->assertModelMissing($expired);
        $this->assertModelExists($fresh);
        $this->assertSame(0, SeenStory::count());
        Storage::disk('public')->assertMissing(['stories/old.jpg', 'stories/old.mp4']);
        Storage::disk('public')->assertExists('stories/new.jpg');
    }

    public function test_user_can_mark_story_seen(): void
    {
        $story = Story::factory()->create();
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/stories/seen/{$story->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Story seen');

        $this->assertTrue(SeenStory::where('story_id', $story->id)->where('user_id', $user->id)->exists());

        $this->getJson("/api/stories/{$story->id}")->assertJsonPath('data.hasSeen', true);
    }

    public function test_user_can_mute_and_unmute_stories(): void
    {
        $user = User::factory()->create();
        $musician = User::factory()->create();
        $follow = Follow::factory()->create(['followed_id' => $musician->id, 'user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->putJson("/api/stories/mute/{$musician->username}")
            ->assertOk()
            ->assertJsonPath('message', 'Stories from '.$musician->username.' muted')
            ->assertJsonPath('data', null);
        $this->assertTrue($follow->fresh()->muted['stories']);

        $this->putJson("/api/stories/mute/{$musician->username}")
            ->assertOk()
            ->assertJsonPath('message', 'Stories from '.$musician->username.' unmuted');
        $this->assertFalse($follow->fresh()->muted['stories']);
    }
}
