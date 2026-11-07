<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\AiAgents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAiAgentsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()
            ->create(['email' => config('admin.email')]);
    }

    public function test_the_agents_are_on_until_an_admin_turns_them_off(): void
    {
        config(['ai.default' => 'gemini', 'ai.providers.gemini.key' => 'test-key']);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/ai-agents')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.configured', true);
    }

    public function test_an_admin_can_turn_the_agents_off_and_back_on(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/ai-agents', ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('message', 'AI agents turned off')
            ->assertJsonPath('data.enabled', false);

        $this->assertFalse(app(AiAgents::class)->enabled());
        $this->assertFalse(app(AiAgents::class)->available());

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/ai-agents', ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.enabled', true);

        $this->assertTrue(app(AiAgents::class)->enabled());
    }

    public function test_the_switch_needs_a_boolean(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->putJson('/api/admin/ai-agents', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('enabled');
    }

    public function test_non_admins_cannot_use_the_switch(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->putJson('/api/admin/ai-agents', ['enabled' => false])
            ->assertForbidden();

        $this->assertTrue(app(AiAgents::class)->enabled());
    }
}
