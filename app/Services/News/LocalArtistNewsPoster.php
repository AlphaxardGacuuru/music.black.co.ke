<?php

namespace App\Services\News;

use App\Ai\Agents\LocalArtistNewsWriter;
use App\Models\NewsArticle;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Posts new local artist news from the house account: a summary written by
 * LocalArtistNewsWriter, split into a post and follow-up comments when it
 * doesn't fit in one post, ending with the source and a link to the story.
 */
class LocalArtistNewsPoster
{
    /**
     * posts.text and post_comments.text are varchar(255).
     */
    public const TEXT_LIMIT = 255;

    /**
     * The most comments one story continues into.
     */
    public const MAX_COMMENTS = 3;

    /**
     * Stories looked at per post wanted, so skipped stories can't make one
     * run prompt the AI about the whole feed.
     */
    protected const CANDIDATES_PER_POST = 3;

    public function __construct(
        protected LocalArtistNewsFeed $feed,
        protected TextChunker $chunker,
    ) {}

    /**
     * Post up to $limit stories that haven't been handled before.
     *
     * @return Collection<int, Post>
     */
    public function run(int $limit): Collection
    {
        $poster = User::housePoster();

        if (! $poster) {
            Log::warning('Local artist news: no user with the poster email.', [
                'email' => config('services.news.poster_email'),
            ]);

            return collect();
        }

        $posts = collect();

        $this->feed
            ->latest()
            ->reject(fn (array $story) => $this->alreadyHandled($story))
            ->take($limit * self::CANDIDATES_PER_POST)
            ->each(function (array $story) use ($poster, $posts, $limit) {
                if ($posts->count() >= $limit) {
                    return false;
                }

                $post = $this->publish($poster, $story);

                if ($post) {
                    $posts->push($post);
                }
            });

        return $posts;
    }

    /**
     * Post one story, or record it as skipped when it isn't about a local
     * artist.
     *
     * @param  array{url: string, title: string, source: ?string, published_at: mixed}  $story
     */
    protected function publish(User $poster, array $story): ?Post
    {
        $summary = $this->summarise($story);

        $article = NewsArticle::create([
            'url' => $story['url'],
            'title' => $story['title'],
            'source' => $story['source'],
            'published_at' => $story['published_at'],
        ]);

        if ($summary === null) {
            return null;
        }

        $parts = $this->parts($story, $summary, route('news.show', $article));

        return DB::transaction(function () use ($poster, $article, $parts) {
            $post = Post::create([
                'user_id' => $poster->id,
                'text' => $parts->first(),
            ]);

            // Comments are listed newest first (by their time-ordered id),
            // so earlier parts get later ids to read top to bottom. One base
            // time, since saving a comment can take longer than the offset.
            $base = now();

            $parts
                ->slice(1)
                ->values()
                ->each(function (string $text, int $index) use ($post, $poster, $base) {
                    $comment = new PostComment([
                        'post_id' => $post->id,
                        'user_id' => $poster->id,
                        'text' => $text,
                    ]);
                    $comment->id = (string) Str::uuid7($base->copy()->subMilliseconds($index));
                    $comment->save();
                });

            $article->update(['post_id' => $post->id]);

            return $post;
        });
    }

    /**
     * The post text followed by its comments: headline and summary, then
     * the source line, which always survives even when the summary is cut.
     *
     * @param  array{title: string, source: ?string}  $story
     * @return Collection<int, string>
     */
    protected function parts(array $story, string $summary, string $link): Collection
    {
        $sourceLine = Str::of($story['source'] ? "Source: {$story['source']}. " : '')
            ->append("Read more: {$link}")
            ->toString();

        $body = $this->chunker
            ->chunk(trim("{$story['title']}\n\n{$summary}"), self::TEXT_LIMIT)
            ->take(1 + self::MAX_COMMENTS);

        $withSource = "{$body->last()}\n\n{$sourceLine}";

        return mb_strlen($withSource) <= self::TEXT_LIMIT
            ? $body->put($body->keys()->last(), $withSource)
            : $body
                ->take(self::MAX_COMMENTS)
                ->push($sourceLine);
    }

    /**
     * The AI-written summary, "" when the AI is unavailable (the post is
     * then just the headline and source), or null to skip the story.
     *
     * @param  array{title: string, source: ?string, published_at: mixed}  $story
     */
    protected function summarise(array $story): ?string
    {
        if (blank(config('ai.providers.'.config('ai.default').'.key'))) {
            return '';
        }

        try {
            $reply = LocalArtistNewsWriter::make()
                ->prompt(collect([
                    "Headline: {$story['title']}",
                    $story['source'] ? "Source: {$story['source']}" : null,
                    $story['published_at'] ? "Published: {$story['published_at']}" : null,
                ])
                    ->filter()
                    ->implode("\n"))
                ->text;
        } catch (Throwable $e) {
            report($e);

            return '';
        }

        $reply = Str::of($reply)
            ->replace("\u{2014}", ', ')
            ->trim()
            ->toString();

        return Str::upper($reply) === LocalArtistNewsWriter::SKIP ? null : $reply;
    }

    /**
     * Whether this story (by link, or by an identical headline from another
     * feed entry) was handled before.
     *
     * @param  array{url: string, title: string}  $story
     */
    protected function alreadyHandled(array $story): bool
    {
        return NewsArticle::query()
            ->where('url_hash', NewsArticle::hashUrl($story['url']))
            ->orWhere('title', $story['title'])
            ->exists();
    }
}
