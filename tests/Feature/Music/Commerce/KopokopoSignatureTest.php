<?php

namespace Tests\Feature\Music\Commerce;

use App\Models\MPESATransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kopokopo's callbacks credit wallets (which buy songs) and confirm
 * payouts, so only callbacks Kopokopo signed are accepted.
 */
class KopokopoSignatureTest extends TestCase
{
    use CreatesSongs, RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function callbacks(): array
    {
        return [
            'STK result' => ['/api/mpesa-transactions'],
            'old STK result URL' => ['/api/kopokopo'],
            'transfer result' => ['/api/kopokopo-transfers'],
        ];
    }

    #[DataProvider('callbacks')]
    public function test_unsigned_callbacks_are_rejected(string $uri): void
    {
        $fan = User::factory()->create(['phone' => '0712345678']);

        $this->postJson($uri, $this->kopokopoCallback($fan, 1000))->assertUnauthorized();

        $this->assertSame(0, MPESATransaction::count());
        $this->assertSame(0, $fan->fresh()->balance());
    }

    #[DataProvider('callbacks')]
    public function test_callbacks_signed_with_the_wrong_key_are_rejected(string $uri): void
    {
        $fan = User::factory()->create(['phone' => '0712345678']);
        $payload = $this->kopokopoCallback($fan, 1000);
        $body = json_encode($payload);

        $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_KOPOKOPO_SIGNATURE' => hash_hmac('sha256', $body, 'someone-elses-key'),
        ], $body)->assertUnauthorized();

        $this->assertSame(0, MPESATransaction::count());
    }

    public function test_signed_payment_callbacks_are_accepted(): void
    {
        $fan = User::factory()->create(['phone' => '0712345678']);

        $this->postKopokopoCallback('/api/mpesa-transactions', $this->kopokopoCallback($fan, 50))->assertOk();

        $this->assertSame(50, $fan->fresh()->balance());
    }

    public function test_the_unsigned_dev_switch_only_works_locally(): void
    {
        config(['services.kopokopo.allow_unsigned_callbacks' => true]);
        $fan = User::factory()->create(['phone' => '0712345678']);

        // Tests run as "testing", not "local", so the switch is ignored.
        $this->postJson('/api/mpesa-transactions', $this->kopokopoCallback($fan, 50))->assertUnauthorized();
    }
}
