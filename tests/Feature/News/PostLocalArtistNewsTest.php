<?php

namespace Tests\Feature\News;

use App\Ai\Agents\LocalArtistNewsWriter;
use App\Jobs\PostLocalArtistNewsJob;
use App\Models\NewsArticle;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use App\Services\AiAgents;
use App\Services\News\LocalArtistNewsPoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostLocalArtistNewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $poster;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.news.poster_email' => 'al@music.black.co.ke',
            'services.news.feed_url' => 'https://news.google.com/rss/search?q=test',
            'services.news.posts_per_run' => 2,
            'ai.default' => 'gemini',
            'ai.providers.gemini.key' => 'test-key',
        ]);

        Http::fake([
            'news.google.com/*' => Http::response(file_get_contents(base_path('tests/Fixtures/news/google-news.xml'))),
        ]);

        $this->poster = User::factory()
            ->create(['email' => 'al@music.black.co.ke']);
    }

    public function test_a_short_story_is_one_post_ending_with_the_source_and_a_short_link(): void
    {
        LocalArtistNewsWriter::fake([
            'Bien has released a new album a week before his Nairobi show.',
            LocalArtistNewsWriter::SKIP,
            'Nviiri will tour five towns this December.',
        ]);

        PostLocalArtistNewsJob::dispatchSync();

        $article = NewsArticle::query()
            ->where('title', 'Bien Drops New Album Ahead Of Nairobi Show')
            ->firstOrFail();

        $post = $article->post;

        $this->assertSame($this->poster->id, $post->user_id);
        $this->assertSame(
            "Bien Drops New Album Ahead Of Nairobi Show\n\nBien has released a new album a week before his Nairobi show.\n\nSource: Mpasho. Read more: ".route('news.show', $article),
            $post->text
        );
        $this->assertSame(0, $post->comments()->count());
    }

    public function test_when_an_admin_switches_ai_off_news_is_posted_without_a_summary(): void
    {
        app(AiAgents::class)->setEnabled(false);

        LocalArtistNewsWriter::fake();

        PostLocalArtistNewsJob::dispatchSync();

        LocalArtistNewsWriter::assertNeverPrompted();

        $post = NewsArticle::query()
            ->where('title', 'Bien Drops New Album Ahead Of Nairobi Show')
            ->firstOrFail()
            ->post;

        $this->assertStringStartsWith("Bien Drops New Album Ahead Of Nairobi Show\n\nSource: Mpasho.", $post->text);
    }

    public function test_stories_not_about_local_artists_are_skipped_but_remembered(): void
    {
        LocalArtistNewsWriter::fake([
            'Bien has a new album.',
            LocalArtistNewsWriter::SKIP,
            'Nviiri is touring.',
        ]);

        PostLocalArtistNewsJob::dispatchSync();

        $this->assertSame(2, Post::count());
        $this->assertDatabaseHas('news_articles', [
            'title' => 'Football Club Signs New Striker',
            'post_id' => null,
        ]);
        $this->assertEqualsCanonicalizing(
            ['Bien Drops New Album Ahead Of Nairobi Show', 'Nviiri Announces Tour Dates & Venues'],
            Post::query()
                ->pluck('text')
                ->map(fn (string $text) => Str::before($text, "\n\n"))
                ->all()
        );
    }

    public function test_handled_stories_are_never_posted_or_sent_to_the_ai_again(): void
    {
        LocalArtistNewsWriter::fake([
            'Bien has a new album.',
            LocalArtistNewsWriter::SKIP,
            'Nviiri is touring.',
        ]);

        PostLocalArtistNewsJob::dispatchSync();
        PostLocalArtistNewsJob::dispatchSync();

        $this->assertSame(2, Post::count());
        $this->assertSame(3, NewsArticle::count());
    }

    public function test_a_long_story_continues_in_comments_that_read_top_to_bottom(): void
    {
        $sentences = collect(range(1, 12))
            ->map(fn (int $n) => "Sentence number {$n} of the story says something about the album and the show.");

        LocalArtistNewsWriter::fake([
            $sentences->implode(' '),
            LocalArtistNewsWriter::SKIP,
            LocalArtistNewsWriter::SKIP,
        ]);

        PostLocalArtistNewsJob::dispatchSync();

        $post = Post::query()
            ->sole();

        // The order the app lists comments in.
        $comments = PostComment::query()
            ->where('post_id', $post->id)
            ->orderBy('id', 'DESC')
            ->pluck('text');

        $parts = $comments->prepend($post->text);

        $this->assertLessThanOrEqual(1 + LocalArtistNewsPoster::MAX_COMMENTS, $parts->count());
        $this->assertGreaterThan(1, $parts->count());
        $parts->each(fn (string $part) => $this->assertLessThanOrEqual(LocalArtistNewsPoster::TEXT_LIMIT, mb_strlen($part)));
        $this->assertStringStartsWith('Bien Drops New Album Ahead Of Nairobi Show', $parts->first());
        $this->assertStringContainsString('Sentence number 1 of', $parts->first());
        $this->assertStringStartsWith('Source: Mpasho. Read more: ', Str::afterLast($parts->last(), "\n\n"));

        // The sentences that made it appear in reading order.
        $numbers = Str::of($parts->implode(' '))
            ->matchAll('/Sentence number (\d+)/')
            ->map(fn (string $n) => (int) $n);

        $this->assertSame($numbers->sort()->values()->all(), $numbers->all());
    }

    public function test_without_the_ai_a_story_is_posted_as_its_headline_and_source(): void
    {
        LocalArtistNewsWriter::fake(fn () => throw new \RuntimeException('AI is down'));

        PostLocalArtistNewsJob::dispatchSync();

        $article = NewsArticle::query()
            ->where('source', 'Mpasho')
            ->firstOrFail();

        $this->assertSame(
            "Bien Drops New Album Ahead Of Nairobi Show\n\nSource: Mpasho. Read more: ".route('news.show', $article),
            $article->post->text
        );
    }

    public function test_stories_from_excluded_sources_are_ignored(): void
    {
        config(['services.news.excluded_sources' => ['mpasho']]);

        LocalArtistNewsWriter::fake([
            LocalArtistNewsWriter::SKIP,
            'Nviiri is touring.',
        ]);

        PostLocalArtistNewsJob::dispatchSync();

        $this->assertDatabaseMissing('news_articles', ['source' => 'Mpasho']);
        $this->assertSame(1, Post::count());
    }

    public function test_nothing_is_posted_without_the_poster_account(): void
    {
        LocalArtistNewsWriter::fake();

        $this->poster->delete();

        PostLocalArtistNewsJob::dispatchSync();

        $this->assertSame(0, Post::count());
        $this->assertSame(0, NewsArticle::count());
        LocalArtistNewsWriter::assertNeverPrompted();
    }

    public function test_the_short_link_redirects_to_the_story(): void
    {
        $article = NewsArticle::create([
            'url' => 'https://www.mpasho.co.ke/entertainment/2026-11-05-bien-new-album',
            'title' => 'Bien Drops New Album',
        ]);

        $this->get(route('news.show', $article))
            ->assertRedirect('https://www.mpasho.co.ke/entertainment/2026-11-05-bien-new-album');
    }
}
