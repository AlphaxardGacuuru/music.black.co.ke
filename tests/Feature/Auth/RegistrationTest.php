<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_new_users_can_register_with_name_and_email(): void
    {
        Event::fake([Registered::class]);
        Notification::fake();

        $response = $this->postJson(route('register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'device_name' => 'web',
        ]);

        $response->assertOk()->assertJsonPath('message', 'Registered Successfully');
        $this->assertNotEmpty($response->json('data'));

        $user = User::where('email', 'jane@example.com')->sole();
        $this->assertSame('Jane Doe', $user->name);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertCount(1, $user->tokens()->get());

        Event::assertDispatched(Registered::class, fn(Registered $event) => $event->user->is($user));
        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_registration_requires_a_unique_email_and_confirmed_password(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson(route('register.store'), [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
            'device_name' => 'web',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    }
}
