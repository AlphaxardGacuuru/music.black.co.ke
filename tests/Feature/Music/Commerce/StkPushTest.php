<?php

namespace Tests\Feature\Music\Commerce;

use App\Http\Services\MPESATransactionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class StkPushTest extends TestCase
{
    use RefreshDatabase;

    public function test_stk_push_requires_authentication(): void
    {
        $this->postJson('/api/stk-push', ['amount' => 20])->assertUnauthorized();
    }

    public function test_stk_push_returns_the_old_success_message(): void
    {
        $this->mock(MPESATransactionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('stkPush')->once()->andReturn(['success', 'Request Sent to Your Phone', ['status' => 'success']]);
        });

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/stk-push', ['amount' => 20])
            ->assertOk()
            ->assertExactJson(['message' => 'Request sent to your phone']);
    }

    public function test_stk_push_failure_returns_400_with_the_response(): void
    {
        $this->mock(MPESATransactionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('stkPush')->once()->andReturn(['error', 'Request Failed', ['status' => 'error', 'data' => 'Invalid phone']]);
        });

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/stk-push', ['amount' => 20])
            ->assertStatus(400)
            ->assertExactJson(['message' => ['status' => 'error', 'data' => 'Invalid phone']]);
    }
}
