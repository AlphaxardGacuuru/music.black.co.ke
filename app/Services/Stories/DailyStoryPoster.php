<?php

namespace App\Services\Stories;

use App\Ai\Agents\MusicHistoryFinder;
use App\Ai\Agents\UpcomingGigsFinder;
use App\Models\Audio;
use App\Models\BoughtAudio;
use App\Models\BoughtVideo;
use App\Models\NewsArticle;
use App\Models\Story;
use App\Models\User;
use App\Services\AiAgents;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Posts the house account's daily story: cards for the latest local music
 * headlines, what's trending on Black Music, gigs this week and music
 * history on today's date. Cards with nothing to show are left out.
 */
class DailyStoryPoster
{
    /**
     * The story viewer's segment limit.
     */
    public const MAX_SEGMENTS = 10;

    public const MAX_HEADLINES = 3;

    public function __construct(
        protected StoryCardRenderer $cards,
        protected AiAgents $ai,
    ) {}

    public function run(): ?Story
    {
        $poster = User::housePoster();

        if (! $poster) {
            Log::warning('Daily story: no user with the poster email.', [
                'email' => config('services.news.poster_email'),
            ]);

            return null;
        }

        $segments = collect([
            fn () => $this->headlineCards(),
            fn () => $this->trendingCards(),
            fn () => $this->gigsCard(),
            fn () => $this->historyCard(),
        ])
            // One card failing (an AI or image error) shouldn't cost the rest.
            ->flatMap(fn (callable $cards) => rescue(fn () => collect($cards())->filter()->all(), []))
            ->take(self::MAX_SEGMENTS)
            ->values();

        if ($segments->isEmpty()) {
            return null;
        }

        return Story::create([
            'user_id' => $poster->id,
            'text' => 'Your daily Black Music: local headlines, what\'s trending, gigs this week and today in music history.',
            'media' => $segments
                ->map(fn (string $path) => ['image' => $path])
                ->all(),
        ]);
    }

    /**
     * The latest stories the news job posted in the last day.
     *
     * @return Collection<int, string>
     */
    protected function headlineCards(): Collection
    {
        return NewsArticle::query()
            ->whereNotNull('post_id')
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->take(self::MAX_HEADLINES)
            ->get()
            ->map(fn (NewsArticle $article) => $this->cards->headline(
                'Local music news',
                $article->title,
                collect([$article->source, 'Read the full story in your Black Music feed.'])
                    ->filter()
                    ->implode('. '),
            ));
    }

    /**
     * The best selling video and audio of the past week.
     *
     * @return Collection<int, string>
     */
    protected function trendingCards(): Collection
    {
        return collect([
            [Video::class, 'Watch it on Black Music for KES '.BoughtVideo::PRICE],
            [Audio::class, 'Get it on Black Music for KES '.BoughtAudio::PRICE],
        ])
            ->map(fn (array $song) => [$this->bestSeller($song[0]), $song[1]])
            ->filter(fn (array $song) => $song[0] !== null)
            ->map(fn (array $song) => $this->cards->song(
                'Trending on Black Music',
                Str::limit($song[0]->name, 60),
                $song[0]->user->username,
                $this->localThumbnail($song[0]),
                $song[1],
            ))
            ->values();
    }

    /**
     * @param  class-string<Video|Audio>  $model
     */
    protected function bestSeller(string $model): Video|Audio|null
    {
        return $model::query()
            ->with('user')
            ->withCount(['bought as recent_sales' => fn ($query) => $query->where('created_at', '>=', now()->subWeek())])
            ->having('recent_sales', '>', 0)
            ->orderByDesc('recent_sales')
            ->first();
    }

    /**
     * The thumbnail's path on the public disk, or null when it's remote.
     */
    protected function localThumbnail(Model $song): ?string
    {
        $thumbnail = $song->getRawOriginal('thumbnail');

        return $thumbnail && ! Str::startsWith($thumbnail, ['http://', 'https://']) ? $thumbnail : null;
    }

    protected function gigsCard(): ?string
    {
        $today = today();

        $gigs = $this->research(UpcomingGigsFinder::class, 'Today is '.$today->format('l j F Y').'.')
            ->filter(fn (array $gig) => filled($gig['event'] ?? null) && filled($gig['venue'] ?? null))
            // Carbon::parse('') would be "now", so a missing date stays null.
            ->map(fn (array $gig) => [...$gig, 'date' => filled($gig['date'] ?? null) && is_string($gig['date'])
                ? rescue(fn () => Carbon::parse($gig['date']), report: false)
                : null])
            ->filter(fn (array $gig) => $gig['date']?->between($today, $today->copy()->addDays(7)))
            ->sortBy('date')
            ->take(4);

        return $gigs->isEmpty() ? null : $this->cards->list(
            'Gigs this week',
            'Where to catch live music',
            $gigs->map(fn (array $gig) => [
                'label' => collect([$gig['date']->format('D j M'), $gig['venue'], $gig['town'] ?? null])
                    ->filter()
                    ->implode(', '),
                'text' => Str::limit($gig['event'], 80),
            ]),
        );
    }

    protected function historyCard(): ?string
    {
        $today = today();

        $facts = $this->research(MusicHistoryFinder::class, 'The date is '.$today->format('j F').'.')
            ->filter(fn (array $fact) => filled($fact['fact'] ?? null)
                && is_numeric($fact['year'] ?? null)
                && $fact['year'] >= 1950
                && $fact['year'] < $today->year)
            ->sortBy('year')
            ->take(3);

        return $facts->isEmpty() ? null : $this->cards->list(
            'On this day',
            $today->format('j F').' in East African music',
            $facts->map(fn (array $fact) => [
                'label' => (string) $fact['year'],
                'text' => Str::limit($fact['fact'], 120),
            ]),
        );
    }

    /**
     * Ask a research agent for its JSON list, keeping only items whose
     * source page actually loads (a cheap check against invented facts).
     * Empty when the AI isn't configured, is switched off by an admin, or
     * replies with something else.
     *
     * @param  class-string<UpcomingGigsFinder|MusicHistoryFinder>  $agent
     * @return Collection<int, array<string, mixed>>
     */
    protected function research(string $agent, string $prompt): Collection
    {
        if (! $this->ai->available()) {
            return collect();
        }

        try {
            $reply = $agent::make()
                ->prompt($prompt)
                ->text;
        } catch (Throwable $e) {
            report($e);

            return collect();
        }

        $json = Str::of($reply)
            ->after('[')
            ->beforeLast(']')
            ->prepend('[')
            ->append(']')
            ->toString();

        return collect(json_decode($json, true) ?: [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => collect($item)
                ->map(fn ($value) => is_string($value) ? Str::of($value)->replace("\u{2014}", ', ')->squish()->toString() : $value)
                ->all())
            ->filter(fn (array $item) => $this->sourceLoads($item['source_url'] ?? null))
            ->values();
    }

    protected function sourceLoads(mixed $url): bool
    {
        if (! is_string($url) || ! Str::startsWith($url, ['http://', 'https://'])) {
            return false;
        }

        return rescue(
            fn () => Http::timeout(10)
                ->withUserAgent('Mozilla/5.0 (compatible; BlackMusicBot/1.0; +https://music.black.co.ke)')
                ->get($url)
                ->successful(),
            false,
            report: false,
        );
    }
}
