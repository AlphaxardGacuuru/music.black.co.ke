<?php

namespace Tests\Feature\Listeners;

use App\Listeners\SendNewUserSignupNotifications;
use App\Models\User;
use App\Notifications\AdminNewUserSignupNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendNewUserSignupNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email' => config('admin.email')]);
    }

    public function test_the_admin_is_notified_when_a_user_signs_up(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $newUser = User::factory()->create();

        (new SendNewUserSignupNotifications)->handle(new Registered($newUser));

        Notification::assertSentTo($admin, AdminNewUserSignupNotification::class);
        Notification::assertNothingSentTo($newUser);
    }

    public function test_the_admin_is_not_notified_when_they_sign_up_themselves(): void
    {
        Notification::fake();

        $admin = $this->admin();

        (new SendNewUserSignupNotifications)->handle(new Registered($admin));

        Notification::assertNothingSentTo($admin);
    }
}
