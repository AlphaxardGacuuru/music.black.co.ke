<?php

namespace Tests\Feature\Music\Social;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SocialFilePondTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_guests_cannot_upload(): void
    {
        $this->postJson('/api/filepond/posts')->assertUnauthorized();
        $this->postJson('/api/filepond/chats')->assertUnauthorized();
        $this->postJson('/api/filepond/stories')->assertUnauthorized();
    }

    public function test_media_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        foreach (['posts', 'chats', 'stories'] as $type) {
            $this->postJson("/api/filepond/{$type}", [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['filepond-media']);
        }
    }

    public function test_post_media_upload_and_delete(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $path = $this->post('/api/filepond/posts', [
            'filepond-media' => UploadedFile::fake()->image('a.jpg'),
        ])->assertOk()->getContent();

        $this->assertStringStartsWith('post-media/', $path);
        Storage::disk('public')->assertExists($path);

        $this->delete('/api/filepond/posts/'.basename($path))
            ->assertOk()
            ->assertSee('Post media deleted');
        Storage::disk('public')->assertMissing($path);
    }

    public function test_chat_media_upload_and_delete(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $path = $this->post('/api/filepond/chats', [
            'filepond-media' => UploadedFile::fake()->image('a.jpg'),
        ])->assertOk()->getContent();

        $this->assertStringStartsWith('chat-media/', $path);
        Storage::disk('public')->assertExists($path);

        $this->delete('/api/filepond/chats/'.basename($path))
            ->assertOk()
            ->assertSee('Chat media deleted');
        Storage::disk('public')->assertMissing($path);
    }

    public function test_story_upload_returns_mime_keyed_path_and_delete(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->post('/api/filepond/stories', [
            'filepond-media' => UploadedFile::fake()->image('a.jpg'),
        ])->assertOk();

        $path = $response->json('image');
        $this->assertStringStartsWith('stories/', $path);
        Storage::disk('public')->assertExists($path);

        $this->delete('/api/filepond/stories/'.basename($path))
            ->assertOk()
            ->assertSee('Story deleted');
        Storage::disk('public')->assertMissing($path);
    }
}
