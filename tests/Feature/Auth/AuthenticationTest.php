<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_log_in_with_email_and_password()
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $response = $this->postJson(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'web',
        ]);

        $response->assertOk()->assertJsonPath('message', 'Logged in');
        $this->assertNotEmpty($response->json('data'));
        $this->assertCount(1, $user->tokens()->get());
    }

    public function test_users_cannot_log_in_with_a_wrong_password()
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->postJson(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'web',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_google_accounts_cannot_log_in_with_their_unhashed_placeholder_password()
    {
        $user = User::factory()->create();
        $user->forceFill(['password' => 'plain-random-string'])->saveQuietly();

        $this->postJson(route('login.store'), [
            'email' => $user->email,
            'password' => 'plain-random-string',
            'device_name' => 'web',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest('web');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Logged Out']);
    }

    public function test_users_can_logout_with_a_bearer_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $response = $this->withToken($token)->post(route('logout'));

        $response->assertStatus(200);
        $this->assertCount(0, $user->tokens()->get());
    }
}
