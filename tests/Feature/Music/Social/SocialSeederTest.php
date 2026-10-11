<?php

namespace Tests\Feature\Music\Social;

use App\Models\Chat;
use App\Models\Poll;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use Database\Seeders\ChatSeeder;
use Database\Seeders\PollSeeder;
use Database\Seeders\PostSeeder;
use Database\Seeders\StorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_seeders_run(): void
    {
        User::factory()->black()->create();
        User::factory()->count(4)->create();

        $this->seed([PostSeeder::class, PollSeeder::class, StorySeeder::class, ChatSeeder::class]);

        $this->assertSame(12, Post::count());
        $this->assertSame(5, Poll::count());
        $this->assertSame(11, Story::count());
        $this->assertSame(5, Chat::count());
    }
}
