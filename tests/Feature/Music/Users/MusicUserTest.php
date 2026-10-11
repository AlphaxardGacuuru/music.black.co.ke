<?php

namespace Tests\Feature\Music\Users;

use App\Models\Follow;
use App\Models\MPESATransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MusicUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_get_a_unique_username(): void
    {
        $first = User::factory()->create(['name' => 'Jane Doe']);
        $second = User::factory()->create(['name' => 'Jane Doe']);

        $this->assertSame('@janedoe', $first->username);
        $this->assertSame('@janedoe2', $second->username);
    }

    public function test_new_users_follow_themselves_and_blackmusic(): void
    {
        $blackMusic = User::factory()->black()->create();
        $user = User::factory()->create();

        $followed = Follow::query()->where('user_id', $user->id)->pluck('followed_id');

        $this->assertEqualsCanonicalizing([$user->id, $blackMusic->id], $followed->all());
        $this->assertSame(1, $blackMusic->fans());
    }

    public function test_guests_can_view_a_profile_by_username(): void
    {
        $user = User::factory()->create(['username' => '@artist']);

        $this->getJson('/api/users/@artist')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.username', '@artist')
            ->assertJsonPath('data.hasFollowed', false)
            ->assertJsonPath('data.balance', 0);
    }

    public function test_artists_lists_musicians_except_the_viewer_and_blackmusic(): void
    {
        User::factory()->black()->create();
        $viewer = User::factory()->musician()->create();
        $artist = User::factory()->musician()->create();
        User::factory()->create(['account_type' => 'normal']);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/artists')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $artist->id);
    }

    public function test_becoming_a_musician_creates_singles_albums(): void
    {
        $user = User::factory()->create(['account_type' => 'normal']);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/users/{$user->id}", [
                'accountType' => 'musician',
                'bio' => 'Afro-fusion',
            ])
            ->assertOk();

        $user->refresh();

        $this->assertSame('musician', $user->account_type);
        $this->assertSame('Afro-fusion', $user->bio);
        $this->assertSame(['Singles'], $user->videoAlbums()->pluck('name')->all());
        $this->assertSame(['Singles'], $user->audioAlbums()->pluck('name')->all());
    }

    public function test_users_cannot_update_someone_elses_profile(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/users/{$other->id}", ['bio' => 'Hacked'])
            ->assertForbidden();

        $this->assertNotSame('Hacked', $other->fresh()->bio);
    }

    public function test_balance_is_payments_less_songs_bought(): void
    {
        $user = User::factory()->create();
        MPESATransaction::factory()->for($user)->create(['amount' => '100']);

        $this->assertSame(100, $user->fresh()->balance());
    }
}
