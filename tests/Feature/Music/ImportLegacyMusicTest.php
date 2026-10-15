<?php

namespace Tests\Feature\Music;

use App\Models\Audio;
use App\Models\AudioComment;
use App\Models\BoughtAudio;
use App\Models\Chat;
use App\Models\Follow;
use App\Models\MPESATransaction;
use App\Models\Post;
use App\Models\SongPayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * music:import-legacy against a copy of the old schema, built by the old
 * app's own migrations (tests/Fixtures/legacy-migrations) on SQLite.
 */
class ImportLegacyMusicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.legacy' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('legacy');

        $this->artisan('migrate', [
            '--database' => 'legacy',
            '--path' => base_path('tests/Fixtures/legacy-migrations'),
            '--realpath' => true,
        ])->assertSuccessful();

        $this->seedLegacy();
    }

    private function legacy(string $table, array $rows): void
    {
        DB::connection('legacy')->table($table)->insert($rows);
    }

    /**
     * A small old database: @blackmusic, an artist with an album and a
     * song, and a fan who paid KES 50, bought the song, follows, comments,
     * chats and posts.
     */
    private function seedLegacy(): void
    {
        $user = fn(int $id, string $username, string $phone, string $type, string $at) => [
            'id' => $id,
            'name' => ucfirst(ltrim($username, '@')),
            'username' => $username,
            'email' => ltrim($username, '@') . '@example.com',
            'password' => Hash::make($phone),
            'phone' => $phone,
            'account_type' => $type,
            'avatar' => 'profile-pics/male_avatar.png',
            'backdrop' => 'img/headphones.jpg',
            'withdrawal' => '1000',
            'created_at' => $at,
            'updated_at' => $at,
        ];

        $this->legacy('users', [
            $user(1, '@blackmusic', '0700000000', 'musician', '2022-10-24 10:00:00'),
            $user(2, '@artist', '0711111111', 'musician', '2022-11-01 10:00:00'),
            $user(3, '@fan', '0722222222', 'normal', '2023-01-01 10:00:00'),
        ]);

        $at = ['created_at' => '2023-02-01 10:00:00', 'updated_at' => '2023-02-01 10:00:00'];
        $later = ['created_at' => '2023-03-01 10:00:00', 'updated_at' => '2023-03-01 10:00:00'];

        $this->legacy('follows', [
            ['id' => 1, 'followed' => '@fan', 'username' => '@fan', 'muted' => '{"posts":false,"stories":false}', 'blocked' => '', ...$at],
            ['id' => 2, 'followed' => '@artist', 'username' => '@fan', 'muted' => '{"posts":false,"stories":true}', 'blocked' => '', ...$at],
        ]);
        $this->legacy('audio_albums', [
            ['id' => 7, 'username' => '@artist', 'name' => 'Singles', 'cover' => 'audio-album-covers/musical-note.png', 'released' => '2023-01-15 00:00:00', ...$at],
        ]);
        $this->legacy('audios', [
            ['id' => 11, 'audio' => 'audios/old.mp3', 'name' => 'Old song', 'username' => '@artist', 'ft' => '@blackmusic', 'audio_album_id' => 7, 'genre' => 'Afro', 'thumbnail' => 'audio-thumbnails/old.jpg', 'description' => null, 'released' => '2023-01-15 00:00:00', ...$at],
            ['id' => 12, 'audio' => 'audios/new.mp3', 'name' => 'New song', 'username' => '@artist', 'ft' => null, 'audio_album_id' => 7, 'genre' => 'Afro', 'thumbnail' => 'audio-thumbnails/new.jpg', 'description' => null, 'released' => '2023-03-01 00:00:00', ...$later],
        ]);
        $this->legacy('bought_audios', [
            ['id' => 1, 'audio_id' => 11, 'name' => 'Old song', 'username' => '@fan', 'artist' => '@artist', 'price' => '10', ...$at],
        ]);
        $this->legacy('cart_audios', [
            ['id' => 1, 'audio_id' => 12, 'username' => '@fan', ...$at],
        ]);
        $this->legacy('audio_comments', [
            ['id' => 5, 'audio_id' => 11, 'text' => 'Fire', 'username' => '@fan', ...$at],
        ]);
        $this->legacy('audio_comment_likes', [
            ['id' => 1, 'audio_comment_id' => 5, 'username' => '@artist', ...$at],
            // A like from a user that no longer exists is skipped.
            ['id' => 2, 'audio_comment_id' => 5, 'username' => '@ghost', ...$at],
        ]);
        $this->legacy('decos', [
            ['id' => 1, 'username' => '@fan', 'artist' => '@artist', ...$at],
        ]);
        $this->legacy('posts', [
            ['id' => 3, 'username' => '@artist', 'text' => 'New music Friday', 'media' => null, 'parameter_1' => 'Yes', 'parameter_2' => 'No', ...$at],
        ]);
        $this->legacy('polls', [
            ['id' => 1, 'post_id' => 3, 'username' => '@fan', 'parameter' => 'Yes', ...$at],
        ]);
        $this->legacy('chats', [
            ['id' => 1, 'username' => '@fan', 'to' => '@artist', 'text' => 'Hi', 'media' => null, ...$at],
        ]);
        $this->legacy('song_payouts', [
            ['id' => 1, 'username' => '@artist', 'amount' => '500', ...$at],
        ]);
        $this->legacy('kopokopos', [
            ['id' => 1, 'kopokopo_id' => 'k2-1', 'reference' => 'ABC123', 'amount' => '50', 'username' => '@fan', ...$at],
            // A payment no user could be matched to.
            ['id' => 2, 'kopokopo_id' => 'k2-2', 'reference' => 'DEF456', 'amount' => '20', 'username' => null, ...$at],
        ]);
    }

    public function test_it_imports_users_with_their_handles_and_passwords(): void
    {
        $this->artisan('music:import-legacy')->assertSuccessful();

        $fan = User::where('username', '@fan')->sole();

        $this->assertSame('fan@example.com', $fan->email);
        $this->assertSame('normal', $fan->account_type);
        // OLD's password was the phone number, so it still works.
        $this->assertTrue(Hash::check('0722222222', $fan->password));
        $this->assertSame(hash('sha256', '254722222222'), $fan->hashed_phone);
        $this->assertSame('/storage/profile-pics/male_avatar.png', $fan->avatar);
        $this->assertSame(3, User::count());
    }

    public function test_it_links_rows_by_user_id_instead_of_username(): void
    {
        $this->artisan('music:import-legacy')->assertSuccessful();

        $fan = User::where('username', '@fan')->sole();
        $artist = User::where('username', '@artist')->sole();

        $oldSong = Audio::where('name', 'Old song')->sole();
        $this->assertTrue($oldSong->user->is($artist));
        $this->assertSame('@blackmusic', $oldSong->ft->username);
        $this->assertSame('Singles', $oldSong->album->name);

        $this->assertTrue($oldSong->hasBoughtAudio($fan->id));
        $this->assertTrue(BoughtAudio::sole()->artist->is($artist));
        $this->assertTrue(Audio::where('name', 'New song')->sole()->inCart($fan->id));

        $this->assertTrue($artist->hasFollowed($fan->id));
        $this->assertTrue(Follow::where('user_id', $fan->id)->where('followed_id', $artist->id)->sole()->muted['stories']);

        $comment = AudioComment::sole();
        $this->assertTrue($comment->user->is($fan));
        $this->assertTrue($comment->hasLiked($artist->id));
        $this->assertSame(1, $comment->likes()->count());

        $post = Post::sole();
        $this->assertTrue($post->hasVoted($fan->id, 'Yes'));
        $this->assertTrue(Chat::sole()->to->is($artist));
    }

    public function test_wallets_payouts_and_decos_carry_over(): void
    {
        $this->artisan('music:import-legacy')->assertSuccessful();

        $fan = User::where('username', '@fan')->sole();
        $artist = User::where('username', '@artist')->sole();

        // KES 50 paid in, one KES 10 audio bought.
        $this->assertSame(40, $fan->balance());
        // The unmatched payment is left out.
        $this->assertSame(1, MPESATransaction::count());

        $payout = SongPayout::sole();
        $this->assertTrue($payout->user->is($artist));
        $this->assertNotNull($payout->paid_at);

        // users.decos is recounted from the deco rows.
        $this->assertSame(1, $fan->decos);
        $this->assertSame(0, $artist->decos);
    }

    public function test_new_ids_keep_the_original_order(): void
    {
        $this->artisan('music:import-legacy')->assertSuccessful();

        $this->assertSame(
            ['Old song', 'New song'],
            Audio::orderBy('id')->pluck('name')->all()
        );
        $this->assertSame('2023-03-01 10:00:00', Audio::where('name', 'New song')->sole()->getRawOriginal('created_at'));
    }

    public function test_read_receipts_and_deleted_chats_carry_over(): void
    {
        DB::connection('legacy')->table('chats')->update([
            'seen_at' => '2023-02-02 08:00:00',
            'deleted_at' => '2023-02-03 08:00:00',
        ]);

        $this->artisan('music:import-legacy')->assertSuccessful();

        $chat = Chat::withTrashed()->sole();
        $this->assertSame('2023-02-02 08:00:00', $chat->seen_at->format('Y-m-d H:i:s'));
        $this->assertTrue($chat->trashed());
    }

    public function test_zero_dates_are_read_as_missing(): void
    {
        DB::connection('legacy')->table('users')->where('username', '@artist')->update([
            'created_at' => '0000-00-00 00:00:00',
            'updated_at' => '0000-00-00 00:00:00',
            'email_verified_at' => '0000-00-00 00:00:00',
        ]);

        $this->artisan('music:import-legacy')->assertSuccessful();

        $artist = User::where('username', '@artist')->sole();

        // It takes the previous user's created_at, so it keeps its place.
        $this->assertSame('2022-10-24 10:00:00', $artist->getRawOriginal('created_at'));
        $this->assertSame('2022-10-24 10:00:00', $artist->getRawOriginal('updated_at'));
        $this->assertNull($artist->email_verified_at);
        $this->assertSame(
            ['@blackmusic', '@artist', '@fan'],
            User::orderBy('id')->pluck('username')->all()
        );
    }

    public function test_it_refuses_a_database_that_already_has_users(): void
    {
        User::factory()->create();

        $this->artisan('music:import-legacy')->assertFailed();

        $this->assertSame(0, Audio::count());
    }

    public function test_it_reads_and_writes_the_connections_it_is_given(): void
    {
        // A second name for the same in-memory old database.
        config(['database.connections.old_music' => config('database.connections.legacy')]);
        DB::connection('old_music')->setPdo(DB::connection('legacy')->getPdo());

        $this->artisan('music:import-legacy', [
            '--from' => 'old_music',
            '--to' => config('database.default'),
        ])->assertSuccessful();

        $this->assertTrue(User::where('username', '@fan')->exists());
    }

    public function test_it_refuses_to_import_a_connection_into_itself(): void
    {
        $this->artisan('music:import-legacy', ['--from' => 'legacy', '--to' => 'legacy'])
            ->expectsOutput('--from and --to must be different connections.')
            ->assertFailed();
    }
}
