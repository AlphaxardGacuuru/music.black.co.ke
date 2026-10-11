<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WelcomeNotificationTest extends TestCase
{
	use RefreshDatabase;

	#[Test]
	public function it_welcomes_the_user_by_username(): void
	{
		$user = User::factory()->create(['username' => '@newfan']);

		$mail = (new WelcomeNotification)->toMail($user);

		$this->assertSame('Welcome to Black Music!', $mail->subject);
		$this->assertSame('Welcome @newfan to Black Music', $mail->greeting);
	}
}
