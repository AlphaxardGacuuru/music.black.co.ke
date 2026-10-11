<?php

namespace Tests\Feature;

use App\Enums\EmailNotificationCategory;
use App\Models\User;
use App\Notifications\ReferralSignupNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_email_preferences_to_enabled(): void
    {
        $user = User::factory()->create(['settings' => null]);

        $this->assertTrue($user->wantsEmail(EmailNotificationCategory::REFERRAL_SIGNED_UP));
    }

    public function test_notification_skips_mail_channel_once_the_user_opts_out(): void
    {
        $user = User::factory()->create([
            'settings' => ['referralSignupNotification' => false],
        ]);

        $channels = (new ReferralSignupNotification(User::factory()->create()))->via($user);

        $this->assertNotContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_notification_includes_mail_channel_by_default(): void
    {
        $user = User::factory()->create(['settings' => []]);

        $channels = (new ReferralSignupNotification(User::factory()->create()))->via($user);

        $this->assertContains('mail', $channels);
    }

    public function test_unsubscribe_link_turns_off_the_linked_category(): void
    {
        $user = User::factory()->create([
            'settings' => ['referralSignupNotification' => true],
        ]);

        $url = $user->unsubscribeUrlFor(EmailNotificationCategory::REFERRAL_SIGNED_UP);

        $this->get($url)->assertOk();

        $this->assertFalse($user->fresh()->wantsEmail(EmailNotificationCategory::REFERRAL_SIGNED_UP));
    }

    public function test_unsubscribe_link_rejects_a_tampered_signature(): void
    {
        $user = User::factory()->create();

        $url = URL::signedRoute('unsubscribe.show', [
            'user' => $user->id,
            'category' => EmailNotificationCategory::REFERRAL_SIGNED_UP->value,
        ]);

        $this->get($url . '&signature=tampered')->assertForbidden();

        $this->assertTrue($user->fresh()->wantsEmail(EmailNotificationCategory::REFERRAL_SIGNED_UP));
    }

    public function test_unsubscribe_link_rejects_an_unknown_category(): void
    {
        $user = User::factory()->create();

        $url = URL::signedRoute('unsubscribe.show', [
            'user' => $user->id,
            'category' => 'not-a-real-category',
        ]);

        $this->get($url)->assertNotFound();
    }

    public function test_user_can_update_their_email_notification_preferences(): void
    {
        $user = User::factory()->create([
            'settings' => [
                'installOnboardedAt' => '2026-01-01T00:00:00Z',
                'referralSignupNotification' => true,
            ],
        ]);

        $this->actingAs($user)
            ->patchJson('/settings/notifications', [
                'referralSignupNotification' => false,
            ])
            ->assertOk();

        $user->refresh();
        $this->assertFalse($user->wantsEmail(EmailNotificationCategory::REFERRAL_SIGNED_UP));
        // Unrelated settings keys survive the update.
        $this->assertSame('2026-01-01T00:00:00Z', $user->settings->installOnboardedAt);
    }
}
