<?php

namespace Tests\Feature;

use App\Events\KopokopoTransferInitiated;
use App\Models\MPESATransaction;
use App\Models\User;
use App\Notifications\AdminNewUserSignupNotification;
use App\Notifications\KopokopoTransferInitiatedNotification;
use App\Notifications\MpesaTransactionCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PushActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    /**
     * The web push payload a notification would send $notifiable.
     *
     * @return array<string, mixed>
     */
    private function push(object $notification, User $notifiable): array
    {
        return $notification
            ->toWebPush($notifiable, $notification)
            ->toArray();
    }

    private function admin(): User
    {
        return User::factory()->create(['email' => config('admin.email')]);
    }

    public function test_the_admin_can_verify_a_signup_from_the_push(): void
    {
        $admin = $this->admin();
        $newUser = User::factory()->create();

        $push = $this->push(new AdminNewUserSignupNotification($newUser), $admin);

        $this->assertSame(['verify', 'users'], array_column($push['actions'], 'action'));
        $this->assertSame('/admin/users', $push['data']['actions']['users']['url']);
        $this->assertSame('/admin/users', $push['data']['url']);

        $this->postJson($push['data']['actions']['verify']['post'])
            ->assertOk()
            ->assertJson(['message' => "{$newUser->name} verified"]);

        $this->assertTrue((bool) $newUser->fresh()->verified);
    }

    public function test_verify_is_refused_for_anyone_but_the_admin(): void
    {
        $newUser = User::factory()->create();

        $push = $this->push(new AdminNewUserSignupNotification($newUser), User::factory()->create());

        $this->postJson($push['data']['actions']['verify']['post'])
            ->assertForbidden();

        $this->assertFalse((bool) $newUser->fresh()->verified);
    }

    public function test_a_push_action_needs_a_valid_unexpired_signature(): void
    {
        $admin = $this->admin();
        $newUser = User::factory()->create();

        $this->postJson("/api/push-actions/{$admin->id}/users/{$newUser->id}/verify")
            ->assertForbidden();

        $expired = URL::temporarySignedRoute(
            'push-actions.users.verify',
            now()->subMinute(),
            ['user' => $admin->id, 'subject' => $newUser->id],
            absolute: false
        );

        $this->postJson($expired)
            ->assertForbidden();

        $this->assertFalse((bool) $newUser->fresh()->verified);
    }

    public function test_a_signed_link_only_works_for_the_user_it_was_sent_to(): void
    {
        $admin = $this->admin();
        $someoneElse = User::factory()->create();
        $newUser = User::factory()->create();

        $link = URL::temporarySignedRoute(
            'push-actions.users.verify',
            now()->addDay(),
            ['user' => $someoneElse->id, 'subject' => $newUser->id],
            absolute: false
        );

        $this->postJson($link)
            ->assertForbidden();

        // Swapping the admin into someone else's link breaks the signature.
        $this->postJson(str_replace("/push-actions/{$someoneElse->id}/", "/push-actions/{$admin->id}/", $link))
            ->assertForbidden();

        $this->assertFalse((bool) $newUser->fresh()->verified);
    }

    public function test_a_payment_push_opens_the_library(): void
    {
        $user = User::factory()->create();
        $transaction = MPESATransaction::factory()->create(['user_id' => $user->id, 'amount' => 500]);

        $push = $this->push(new MpesaTransactionCreatedNotification($transaction), $user);

        $this->assertSame(['library'], array_column($push['actions'], 'action'));
        $this->assertSame('/library', $push['data']['actions']['library']['url']);
    }

    public function test_a_transfer_push_opens_payouts_for_musicians_and_notifications_for_everyone_else(): void
    {
        $event = new KopokopoTransferInitiated('254700123456', 1000.0, 'Jane', 'A gift');

        $musician = $this->push(new KopokopoTransferInitiatedNotification($event), User::factory()->musician()->create());
        $fan = $this->push(new KopokopoTransferInitiatedNotification($event), User::factory()->create());

        $this->assertSame(['payouts'], array_column($musician['actions'], 'action'));
        $this->assertSame('/settings/payouts', $musician['data']['actions']['payouts']['url']);
        $this->assertSame(['notifications'], array_column($fan['actions'], 'action'));
        $this->assertSame('/notifications', $fan['data']['actions']['notifications']['url']);
    }

    public function test_every_web_push_notification_has_a_button(): void
    {
        collect(glob(app_path('Notifications/*.php')))
            ->map(fn (string $path) => file_get_contents($path))
            ->filter(fn (string $source) => str_contains($source, 'function toWebPush'))
            ->each(fn (string $source) => $this->assertMatchesRegularExpression('/->(openAction|signedAction)\(/', $source));
    }
}
