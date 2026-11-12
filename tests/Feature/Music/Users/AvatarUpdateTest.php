<?php

namespace Tests\Feature\Music\Users;

use App\Http\Services\AuthService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AvatarUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    protected function uploadAvatar(User $user, string $name = 'me.jpg', ?User $as = null): string
    {
        $this->actingAs($as ?? $user, 'sanctum')
            ->post("/api/filepond/avatar/{$user->id}", [
                'filepond-avatar' => UploadedFile::fake()->image($name),
            ])
            ->assertOk();

        return $user
            ->refresh()
            ->getRawOriginal('avatar');
    }

    public function test_a_user_can_upload_a_profile_picture(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->post("/api/filepond/avatar/{$user->id}", [
                'filepond-avatar' => UploadedFile::fake()->image('me.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Profile picture updated');

        $path = $user
            ->refresh()
            ->getRawOriginal('avatar');

        $response->assertJsonPath('avatar', "/storage/{$path}");
        $this->assertStringStartsWith(User::AVATAR_DIRECTORY . '/', $path);
        $this->assertTrue($user->hasUploadedAvatar());
        Storage::disk('public')->assertExists($path);
    }

    public function test_uploading_a_new_picture_deletes_the_previous_upload(): void
    {
        $user = User::factory()->create();

        $first = $this->uploadAvatar($user, 'first.jpg');
        $second = $this->uploadAvatar($user, 'second.png');

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_the_shared_default_picture_is_never_deleted(): void
    {
        Storage::disk('public')->put('avatars/male-avatar.png', 'default');
        Storage::disk('public')->put('avatars/male_avatar.png', 'legacy default');

        $user = User::factory()->create(['avatar' => 'avatars/male-avatar.png']);
        $legacyUser = User::factory()->create(['avatar' => 'avatars/male_avatar.png']);

        $this->assertFalse($user->hasUploadedAvatar());
        $this->assertFalse($legacyUser->hasUploadedAvatar());

        $this->uploadAvatar($user);
        $this->uploadAvatar($legacyUser);

        Storage::disk('public')->assertExists('avatars/male-avatar.png');
        Storage::disk('public')->assertExists('avatars/male_avatar.png');
    }

    public function test_replacing_a_google_picture_deletes_nothing(): void
    {
        Storage::disk('public')->put('avatars/someone-else.jpg', 'image');

        $user = User::factory()->create(['avatar' => 'https://lh3.googleusercontent.com/a/avatar']);

        $this->assertFalse($user->hasUploadedAvatar());

        $this->uploadAvatar($user);

        Storage::disk('public')->assertExists('avatars/someone-else.jpg');
    }

    public function test_the_admin_can_change_someone_elses_picture(): void
    {
        $admin = User::factory()->create(['email' => config('admin.email')]);
        $user = User::factory()->create();

        $path = $this->uploadAvatar($user, as: $admin);

        $this->assertTrue($user->hasUploadedAvatar());
        Storage::disk('public')->assertExists($path);
    }

    public function test_the_upload_must_be_a_small_enough_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/filepond/avatar/{$user->id}", [
                'filepond-avatar' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('filepond-avatar');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/filepond/avatar/{$user->id}", [
                'filepond-avatar' => UploadedFile::fake()->image('me.gif'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['filepond-avatar' => 'Use a JPG, PNG or WebP picture.']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/filepond/avatar/{$user->id}", [
                'filepond-avatar' => UploadedFile::fake()
                    ->image('huge.jpg')
                    ->size(User::MAX_AVATAR_KB + 1),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('filepond-avatar');

        $this->assertFalse($user->refresh()->hasUploadedAvatar());
    }

    public function test_google_sign_in_keeps_an_uploaded_picture(): void
    {
        $user = User::factory()->create();

        $uploaded = $this->uploadAvatar($user);

        $googleUser = (new SocialiteUser)
            ->setRaw([])
            ->map([
                'id' => 'google-1',
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => 'https://lh3.googleusercontent.com/a/new-avatar',
            ]);

        app(AuthService::class)
            ->findOrCreateFromSocialite($googleUser);

        $this->assertSame($uploaded, $user->refresh()->getRawOriginal('avatar'));
    }

    public function test_google_sign_in_still_replaces_the_default_picture(): void
    {
        $user = User::factory()->create(['avatar' => 'avatars/male-avatar.png']);

        $googleUser = (new SocialiteUser)
            ->setRaw([])
            ->map([
                'id' => 'google-1',
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => 'https://lh3.googleusercontent.com/a/new-avatar',
            ]);

        app(AuthService::class)
            ->findOrCreateFromSocialite($googleUser);

        $this->assertSame('https://lh3.googleusercontent.com/a/new-avatar', $user->refresh()->avatar);
    }
}
