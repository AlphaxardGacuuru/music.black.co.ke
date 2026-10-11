<?php

namespace Tests\Feature\Music\Social;

use App\Models\Search;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_current_users_searches(): void
    {
        $user = User::factory()->create();
        Search::factory()->count(2)->create(['user_id' => $user->id]);
        Search::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/search')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.username', $user->username)
            ->assertJsonStructure([['id', 'username', 'keyword', 'created_at', 'updated_at']]);
    }

    public function test_guests_get_no_searches_and_cannot_store(): void
    {
        Search::factory()->create();

        $this->getJson('/api/search')->assertOk()->assertExactJson([]);
        $this->postJson('/api/search', ['keyword' => 'x'])->assertUnauthorized();
    }

    public function test_user_can_store_and_delete_search(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/search', ['keyword' => 'kenya'])->assertOk();
        $search = Search::firstWhere('keyword', 'kenya');
        $this->assertSame($user->id, $search->user_id);

        $this->deleteJson("/api/search/{$search->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Search deleted');
        $this->assertModelMissing($search);
    }
}
