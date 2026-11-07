<?php

namespace Tests\Feature\Stories;

use App\Ai\Agents\MusicHistoryFinder;
use App\Ai\Agents\UpcomingGigsFinder;
use App\Jobs\PostDailyStoryJob;
use App\Models\BoughtAudio;
use App\Models\BoughtVideo;
use App\Models\NewsArticle;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use App\Services\AiAgents;
use App\Services\Stories\StoryCardRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostDailyStoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $poster;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        config([
            'services.news.poster_email' => 'al@music.black.co.ke',
            'ai.default' => 'gemini',
            'ai.providers.gemini.key' => 'test-key',
        ]);

        $this->travelTo(now()->setDate(2026, 11, 6)->setTime(7, 0));

        // Source pages: real ones load, invented ones don't.
        Http::fake([
            'example.com/missing*' => Http::response('', 404),
            '*' => Http::response('ok'),
        ]);

        $this->poster = User::factory()
            ->create(['email' => 'al@music.black.co.ke']);
    }

    public function test_the_story_has_a_card_for_each_kind_of_content(): void
    {
        $this->postedNews('Bien Drops New Album Ahead Of Nairobi Show', 'Mpasho');
        $this->postedNews('Nviiri Announces Tour Dates', 'Capital FM');
        BoughtVideo::factory()
            ->create();
        BoughtAudio::factory()
            ->create();

        UpcomingGigsFinder::fake([json_encode([
            ['date' => '2026-11-07', 'venue' => 'Alchemist', 'town' => 'Nairobi', 'event' => 'Bien live', 'source_url' => 'https://example.com/bien'],
        ])]);
        MusicHistoryFinder::fake([json_encode([
            ['year' => 2009, 'fact' => 'Sauti Sol released Mwanzo', 'source_url' => 'https://example.com/mwanzo'],
        ])]);

        PostDailyStoryJob::dispatchSync();

        $story = Story::query()
            ->sole();

        $this->assertSame($this->poster->id, $story->user_id);
        // 2 headlines, a trending video and audio, gigs, on this day.
        $this->assertCount(6, $story->media);

        collect($story->media)
            ->each(function (array $segment) {
                $this->assertStringStartsWith('stories/daily-', $segment['image']);
                Storage::disk('public')->assertExists($segment['image']);

                [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($segment['image']));
                $this->assertSame([StoryCardRenderer::WIDTH, StoryCardRenderer::HEIGHT], [$width, $height]);
            });
    }

    public function test_only_news_posted_in_the_last_day_makes_a_headline_card(): void
    {
        $this->postedNews('Fresh story', 'Mpasho');
        $this->travel(-2)->days();
        $this->postedNews('Old story', 'Mpasho');
        $this->travelBack();
        $this->travelTo(now()->setDate(2026, 11, 6)->setTime(7, 0));
        NewsArticle::create(['url' => 'https://example.com/skipped', 'title' => 'Skipped story']);

        UpcomingGigsFinder::fake(['[]']);
        MusicHistoryFinder::fake(['[]']);

        PostDailyStoryJob::dispatchSync();

        $this->assertCount(1, Story::query()->sole()->media);
    }

    public function test_research_without_a_working_source_or_outside_the_window_is_left_out(): void
    {
        UpcomingGigsFinder::fake(["Here you go:\n```json\n".json_encode([
            // Invented source
            ['date' => '2026-11-07', 'venue' => 'KICC', 'event' => 'Made up gig', 'source_url' => 'https://example.com/missing'],
            // Already happened
            ['date' => '2026-11-01', 'venue' => 'KICC', 'event' => 'Last week', 'source_url' => 'https://example.com/past'],
            // No date at all
            ['venue' => 'KICC', 'event' => 'Undated', 'source_url' => 'https://example.com/undated'],
        ])."\n```"]);
        MusicHistoryFinder::fake([json_encode([
            ['year' => 2009, 'fact' => 'No source'],
            ['year' => 2031, 'fact' => 'In the future', 'source_url' => 'https://example.com/future'],
        ])]);

        PostDailyStoryJob::dispatchSync();

        $this->assertSame(0, Story::count());
    }

    public function test_a_failing_ai_only_costs_its_own_card(): void
    {
        $this->postedNews('Bien Drops New Album', 'Mpasho');

        UpcomingGigsFinder::fake(fn () => throw new \RuntimeException('AI is down'));
        MusicHistoryFinder::fake(['not json at all']);

        PostDailyStoryJob::dispatchSync();

        $this->assertCount(1, Story::query()->sole()->media);
    }

    public function test_without_an_ai_key_the_story_has_headlines_and_trending_only(): void
    {
        config(['ai.providers.gemini.key' => null]);

        $this->postedNews('Bien Drops New Album', 'Mpasho');

        UpcomingGigsFinder::fake();
        MusicHistoryFinder::fake();

        PostDailyStoryJob::dispatchSync();

        $this->assertCount(1, Story::query()->sole()->media);
        UpcomingGigsFinder::assertNeverPrompted();
        MusicHistoryFinder::assertNeverPrompted();
    }

    public function test_when_an_admin_switches_ai_off_the_story_has_headlines_and_trending_only(): void
    {
        app(AiAgents::class)->setEnabled(false);

        $this->postedNews('Bien Drops New Album', 'Mpasho');

        UpcomingGigsFinder::fake();
        MusicHistoryFinder::fake();

        PostDailyStoryJob::dispatchSync();

        $this->assertCount(1, Story::query()->sole()->media);
        UpcomingGigsFinder::assertNeverPrompted();
        MusicHistoryFinder::assertNeverPrompted();
    }

    public function test_nothing_is_posted_when_there_is_nothing_to_show(): void
    {
        UpcomingGigsFinder::fake(['[]']);
        MusicHistoryFinder::fake(['[]']);

        PostDailyStoryJob::dispatchSync();

        $this->assertSame(0, Story::count());
    }

    public function test_nothing_is_posted_without_the_poster_account(): void
    {
        $this->postedNews('Bien Drops New Album', 'Mpasho');
        $this->poster->delete();

        UpcomingGigsFinder::fake();
        MusicHistoryFinder::fake();

        PostDailyStoryJob::dispatchSync();

        $this->assertSame(0, Story::count());
        UpcomingGigsFinder::assertNeverPrompted();
    }

    protected function postedNews(string $title, string $source): NewsArticle
    {
        return NewsArticle::create([
            'url' => 'https://example.com/'.Str::slug($title),
            'title' => $title,
            'source' => $source,
            'post_id' => Post::factory()
                ->create(['user_id' => $this->poster->id])
                ->id,
        ]);
    }
}
