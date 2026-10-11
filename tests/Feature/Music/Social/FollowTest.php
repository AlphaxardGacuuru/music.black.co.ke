<?php

namespace Tests\Feature\Music\Social;

use App\Events\FollowedEvent;
use App\Models\Audio;
use App\Models\BoughtAudio;
use App\Models\Follow;
use App\Models\User;
use App\Notifications\FollowedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_follow(): void
    {
        $this->postJson('/api/follows', ['musician' => '@x'])->assertUnauthorized();
    }

    public function test_musician_must_exist(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/follows', ['musician' => '@nobody'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['musician']);
    }

    public function test_cannot_follow_without_buying_a_song(): void
    {
        Event::fake([FollowedEvent::class]);
        $musician = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/follows', ['musician' => $musician->username])
            ->assertOk()
            ->assertJsonPath('message', 'You must have bought atleast one song by '.$musician->username);

        Event::assertNotDispatched(FollowedEvent::class);
        $this->assertSame(0, $musician->fans());
    }

    public function test_buyer_can_follow_and_unfollow_and_musician_is_notified(): void
    {
        Notification::fake();
        $musician = User::factory()->create();
        $fan = User::factory()->create();
        $this->buy($fan, $musician);
        Sanctum::actingAs($fan);

        $this->postJson('/api/follows', ['musician' => $musician->username])
            ->assertOk()
            ->assertExactJson(['message' => 'You Followed '.$musician->username]);

        $follow = Follow::where('followed_id', $musician->id)->where('user_id', $fan->id)->first();
        $this->assertSame(['posts' => false, 'stories' => false], $follow->muted);
        $this->assertSame(1, $musician->fans());

        Notification::assertSentTo($musician, FollowedNotification::class, function ($n) use ($musician, $fan) {
            return $n->toArray($musician) === [
                'url' => '/profile/show/'.$fan->username,
                'from' => $fan->username,
                'message' => $fan->username.' followed you.',
            ];
        });

        $this->postJson('/api/follows', ['musician' => $musician->username])
            ->assertOk()
            ->assertExactJson(['message' => 'You Unfollowed '.$musician->username]);
        $this->assertSame(0, $musician->fans());
    }

    public function test_blackmusic_can_follow_without_buying(): void
    {
        $blackMusic = User::factory()->black()->create();
        $musician = User::factory()->create();
        Sanctum::actingAs($blackMusic);

        $this->postJson('/api/follows', ['musician' => $musician->username])
            ->assertOk()
            ->assertJsonPath('message', 'You Followed '.$musician->username);
    }

    protected function buy(User $fan, User $musician): void
    {
        $audio = Audio::factory()->create(['user_id' => $musician->id]);

        BoughtAudio::create([
            'audio_id' => $audio->id,
            'user_id' => $fan->id,
            'artist_id' => $musician->id,
            'price' => BoughtAudio::PRICE,
        ]);
    }
}
