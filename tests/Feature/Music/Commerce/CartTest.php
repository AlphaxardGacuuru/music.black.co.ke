<?php

namespace Tests\Feature\Music\Commerce;

use App\Models\BoughtAudio;
use App\Models\BoughtVideo;
use App\Models\CartAudio;
use App\Models\CartVideo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use CreatesSongs, RefreshDatabase;

    public function test_guests_get_an_empty_cart(): void
    {
        CartAudio::create(['audio_id' => $this->makeAudio()->id, 'user_id' => User::factory()->create()->id]);

        $this->getJson('/api/cart-audios')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/cart-videos')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_cart_audios_lists_the_users_cart(): void
    {
        $user = User::factory()->create();
        $artist = $this->musician();
        $audio = $this->makeAudio($artist);
        $cartAudio = CartAudio::create(['audio_id' => $audio->id, 'user_id' => $user->id]);
        CartAudio::create(['audio_id' => $this->makeAudio()->id, 'user_id' => User::factory()->create()->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/cart-audios')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cartId', $cartAudio->id)
            ->assertJsonPath('data.0.id', $audio->id)
            ->assertJsonPath('data.0.audio', 'storage/audios/song.mp3')
            ->assertJsonPath('data.0.username', $artist->username)
            ->assertJsonPath('data.0.album', 'Singles')
            ->assertJsonPath('data.0.inCart', true)
            ->assertJsonPath('data.0.hasBoughtAudio', false)
            ->assertJsonStructure(['data' => [[
                'cartId', 'id', 'audio', 'name', 'artistName', 'username', 'avatar',
                'artistDecos', 'ft', 'audioAlbumId', 'album', 'genre', 'thumbnail',
                'description', 'released', 'hasLiked', 'likes', 'comments', 'inCart',
                'hasBoughtAudio', 'hasBought1', 'hasFollowed', 'downloads', 'createdAt',
            ]]]);
    }

    public function test_cart_videos_lists_the_users_cart(): void
    {
        $user = User::factory()->create();
        $video = $this->makeVideo();
        CartVideo::create(['video_id' => $video->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/cart-videos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $video->id)
            ->assertJsonPath('data.0.video', 'storage/videos/song.mp4')
            ->assertJsonPath('data.0.videoAlbumId', $video->video_album_id)
            ->assertJsonPath('data.0.hasBoughtVideo', false);
    }

    public function test_adding_to_cart_requires_authentication(): void
    {
        $this->postJson('/api/cart-audios', ['audio' => $this->makeAudio()->id])->assertUnauthorized();
        $this->postJson('/api/cart-videos', ['video' => $this->makeVideo()->id])->assertUnauthorized();
    }

    public function test_adding_to_cart_validates_the_song(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart-audios', ['audio' => 'missing'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('audio');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart-videos', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('video');
    }

    public function test_posting_an_audio_toggles_it_in_the_cart(): void
    {
        $user = User::factory()->create();
        $audio = $this->makeAudio();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart-audios', ['audio' => $audio->id])
            ->assertOk()
            ->assertJsonPath('message', 'Audio added to Cart')
            ->assertJsonPath('data.audio_id', $audio->id);

        $this->assertDatabaseHas('cart_audios', ['audio_id' => $audio->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart-audios', ['audio' => $audio->id])
            ->assertOk()
            ->assertExactJson(['message' => 'Audio removed from Cart', 'data' => 'data']);

        $this->assertDatabaseMissing('cart_audios', ['audio_id' => $audio->id, 'user_id' => $user->id]);
    }

    public function test_posting_a_video_toggles_it_in_the_cart(): void
    {
        $user = User::factory()->create();
        $video = $this->makeVideo();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart-videos', ['video' => $video->id])
            ->assertOk()
            ->assertJsonPath('message', 'Video added to Cart');

        $this->assertDatabaseHas('cart_videos', ['video_id' => $video->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart-videos', ['video' => $video->id])
            ->assertOk()
            ->assertJsonPath('message', 'Video removed from Cart');

        $this->assertDatabaseMissing('cart_videos', ['video_id' => $video->id, 'user_id' => $user->id]);
    }

    public function test_a_bought_song_already_in_the_cart_is_not_duplicated(): void
    {
        $user = User::factory()->create();
        $audio = $this->makeAudio();
        $video = $this->makeVideo();

        CartAudio::create(['audio_id' => $audio->id, 'user_id' => $user->id]);
        BoughtAudio::create(['audio_id' => $audio->id, 'user_id' => $user->id, 'artist_id' => $audio->user_id, 'price' => '10']);
        CartVideo::create(['video_id' => $video->id, 'user_id' => $user->id]);
        BoughtVideo::create(['video_id' => $video->id, 'user_id' => $user->id, 'artist_id' => $video->user_id, 'price' => '20']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart-audios', ['audio' => $audio->id])
            ->assertOk()
            ->assertJsonPath('message', 'Audio added to Cart');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart-videos', ['video' => $video->id])
            ->assertOk()
            ->assertJsonPath('message', 'Video added to Cart');

        $this->assertSame(1, CartAudio::where('user_id', $user->id)->count());
        $this->assertSame(1, CartVideo::where('user_id', $user->id)->count());
    }
}
