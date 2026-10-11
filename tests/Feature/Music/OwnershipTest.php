<?php

namespace Tests\Feature\Music;

use App\Models\Audio;
use App\Models\AudioAlbum;
use App\Models\AudioComment;
use App\Models\Chat;
use App\Models\Karaoke;
use App\Models\KaraokeComment;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Search;
use App\Models\Story;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAlbum;
use App\Models\VideoComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Only the owner of a song, album, post, comment, story, chat or search
 * (or the admin) may change or delete it, and file uploads need an account.
 */
class OwnershipTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->owner = User::factory()->musician()->create();
        $this->stranger = User::factory()->create();
    }

    public function test_only_the_owner_can_edit_an_audio(): void
    {
        $audio = Audio::factory()->for($this->owner)->create([
            'audio_album_id' => AudioAlbum::factory()->for($this->owner),
        ]);

        $this->actingAs($this->stranger, 'sanctum')
            ->putJson("/api/audios/{$audio->id}", ['name' => 'Stolen'])
            ->assertForbidden();

        $this->assertNotSame('Stolen', $audio->fresh()->name);
    }

    public function test_the_owner_can_edit_their_audio(): void
    {
        $audio = Audio::factory()->for($this->owner)->create([
            'audio_album_id' => AudioAlbum::factory()->for($this->owner),
        ]);

        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/audios/{$audio->id}", ['name' => 'Renamed'])
            ->assertOk();

        $this->assertSame('Renamed', $audio->fresh()->name);
    }

    public function test_the_admin_can_edit_any_audio(): void
    {
        $admin = User::factory()->create(['email' => config('admin.email')]);
        $audio = Audio::factory()->for($this->owner)->create([
            'audio_album_id' => AudioAlbum::factory()->for($this->owner),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/audios/{$audio->id}", ['name' => 'Moderated'])
            ->assertOk();

        $this->assertSame('Moderated', $audio->fresh()->name);
    }

    public function test_only_the_owner_can_edit_a_video(): void
    {
        $video = Video::factory()->for($this->owner)->create([
            'video_album_id' => VideoAlbum::factory()->for($this->owner),
        ]);

        $this->actingAs($this->stranger, 'sanctum')
            ->putJson("/api/videos/{$video->id}", ['name' => 'Stolen'])
            ->assertForbidden();

        $this->assertNotSame('Stolen', $video->fresh()->name);
    }

    public function test_only_the_owner_can_edit_an_album(): void
    {
        $audioAlbum = AudioAlbum::factory()->for($this->owner)->create();
        $videoAlbum = VideoAlbum::factory()->for($this->owner)->create();

        $this->actingAs($this->stranger, 'sanctum')
            ->putJson("/api/audio-albums/{$audioAlbum->id}", ['name' => 'Stolen'])
            ->assertForbidden();

        $this->actingAs($this->stranger, 'sanctum')
            ->putJson("/api/video-albums/{$videoAlbum->id}", ['name' => 'Stolen'])
            ->assertForbidden();
    }

    public function test_only_the_author_can_edit_or_delete_a_post(): void
    {
        $post = Post::factory()->for($this->owner)->create(['text' => 'Original']);

        $this->actingAs($this->stranger, 'sanctum')
            ->putJson("/api/posts/{$post->id}", ['text' => 'Stolen'])
            ->assertForbidden();

        $this->actingAs($this->stranger, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}")
            ->assertForbidden();

        $this->assertSame('Original', $post->fresh()->text);
    }

    /**
     * @return array<string, array{0: string, 1: callable(User): object}>
     */
    public static function ownedRecords(): array
    {
        return [
            'audio comment' => ['audio-comments', fn(User $owner) => AudioComment::factory()->for($owner)->create()],
            'video comment' => ['video-comments', fn(User $owner) => VideoComment::factory()->for($owner)->create()],
            'karaoke comment' => ['karaoke-comments', fn(User $owner) => KaraokeComment::factory()->for($owner)->create([
                'karaoke_id' => Karaoke::factory(),
            ])],
            'post comment' => ['post-comments', fn(User $owner) => PostComment::factory()->for($owner)->create()],
            'story' => ['stories', fn(User $owner) => Story::factory()->for($owner)->create()],
            'chat' => ['chats', fn(User $owner) => Chat::factory()->for($owner)->create()],
            'search' => ['search', fn(User $owner) => Search::factory()->for($owner)->create()],
        ];
    }

    #[DataProvider('ownedRecords')]
    public function test_only_the_owner_can_delete(string $uri, callable $create): void
    {
        $record = $create($this->owner);

        $this->actingAs($this->stranger, 'sanctum')
            ->deleteJson("/api/{$uri}/{$record->id}")
            ->assertForbidden();

        $this->assertModelExists($record);
    }

    #[DataProvider('ownedRecords')]
    public function test_the_owner_can_delete(string $uri, callable $create): void
    {
        $record = $create($this->owner);

        $this->actingAs($this->owner, 'sanctum')
            ->deleteJson("/api/{$uri}/{$record->id}")
            ->assertOk();

        $this->assertFalse($record::query()->whereKey($record->id)->exists());
    }

    public function test_guests_cannot_upload_files(): void
    {
        $this->postJson('/api/filepond/audio', [
            'filepond-audio' => UploadedFile::fake()->create('song.mp3', 10, 'audio/mpeg'),
        ])->assertUnauthorized();

        $this->postJson('/api/filepond/video', [
            'filepond-video' => UploadedFile::fake()->create('song.mp4', 10, 'video/mp4'),
        ])->assertUnauthorized();
    }

    public function test_only_the_owner_can_replace_an_audios_file(): void
    {
        $audio = Audio::factory()->for($this->owner)->create([
            'audio_album_id' => AudioAlbum::factory()->for($this->owner),
        ]);

        $this->actingAs($this->stranger, 'sanctum')
            ->post("/api/filepond/audio/{$audio->id}", [
                'filepond-audio' => UploadedFile::fake()->create('song.mp3', 10, 'audio/mpeg'),
            ])
            ->assertForbidden();

        $this->assertSame($audio->getRawOriginal('audio'), $audio->fresh()->getRawOriginal('audio'));
    }

    public function test_only_the_owner_can_replace_a_videos_thumbnail(): void
    {
        $video = Video::factory()->for($this->owner)->create([
            'video_album_id' => VideoAlbum::factory()->for($this->owner),
        ]);

        $this->actingAs($this->stranger, 'sanctum')
            ->post("/api/filepond/video-thumbnail/{$video->id}", [
                'filepond-thumbnail' => UploadedFile::fake()->image('cover.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_reverting_an_upload_cannot_delete_a_published_songs_file(): void
    {
        Storage::disk('public')->put('audios/published.mp3', 'song');
        Audio::factory()->for($this->owner)->create([
            'audio_album_id' => AudioAlbum::factory()->for($this->owner),
            'audio' => 'audios/published.mp3',
        ]);

        $this->actingAs($this->stranger, 'sanctum')
            ->deleteJson('/api/filepond/audio/published.mp3')
            ->assertForbidden();

        Storage::disk('public')->assertExists('audios/published.mp3');
    }

    public function test_reverting_an_unsaved_upload_deletes_it(): void
    {
        Storage::disk('public')->put('audios/draft.mp3', 'song');

        $this->actingAs($this->owner, 'sanctum')
            ->deleteJson('/api/filepond/audio/draft.mp3')
            ->assertOk();

        Storage::disk('public')->assertMissing('audios/draft.mp3');
    }

    public function test_reverting_cannot_delete_a_storys_media(): void
    {
        Storage::disk('public')->put('stories/moment.jpg', 'image');
        Story::factory()->for($this->owner)->create([
            'media' => [['image' => 'stories/moment.jpg']],
        ]);

        $this->actingAs($this->stranger, 'sanctum')
            ->deleteJson('/api/filepond/stories/moment.jpg')
            ->assertForbidden();

        Storage::disk('public')->assertExists('stories/moment.jpg');
    }

    public function test_users_cannot_change_someone_elses_avatar(): void
    {
        $this->actingAs($this->stranger, 'sanctum')
            ->post("/api/filepond/avatar/{$this->owner->id}", [
                'filepond-avatar' => UploadedFile::fake()->image('me.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_change_an_avatar(): void
    {
        $this->post("/api/filepond/avatar/{$this->owner->id}", [
            'filepond-avatar' => UploadedFile::fake()->image('me.jpg'),
        ], ['Accept' => 'application/json'])->assertUnauthorized();
    }
}
