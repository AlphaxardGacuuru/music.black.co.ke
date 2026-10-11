<?php

namespace Tests\Feature\Music\Karaoke;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KaraokeFilePondTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_and_delete_karaoke_file(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $file = UploadedFile::fake()->create('karaoke.mp4', 100, 'video/mp4');

        $response = $this->post('api/filepond/karaokes', ['filepond-karaoke' => $file]);

        $response->assertOk();
        $this->assertSame('karaokes/'.$file->hashName(), $response->getContent());
        Storage::disk('public')->assertExists('karaokes/'.$file->hashName());

        $this->delete('api/filepond/karaokes/'.$file->hashName())
            ->assertOk()
            ->assertSee('Karaoke deleted');

        Storage::disk('public')->assertMissing('karaokes/'.$file->hashName());
    }

    public function test_upload_requires_file(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('api/filepond/karaokes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['filepond-karaoke']);
    }

    public function test_guest_cannot_upload_karaoke_file(): void
    {
        Storage::fake('public');

        $this->postJson('api/filepond/karaokes', [
            'filepond-karaoke' => UploadedFile::fake()->create('karaoke.mp4', 100),
        ])->assertUnauthorized();
    }
}
