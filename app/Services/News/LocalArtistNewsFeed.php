<?php

namespace App\Services\News;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use SimpleXMLElement;

/**
 * Reads the local artist news feed (a Google News RSS search, see
 * services.news.feed_url).
 */
class LocalArtistNewsFeed
{
    /**
     * The feed's stories, newest first, leaving out excluded sources (see
     * services.news.excluded_sources).
     *
     * @return Collection<int, array{url: string, title: string, source: ?string, published_at: ?Carbon}>
     */
    public function latest(): Collection
    {
        $body = Http::timeout(20)
            ->withUserAgent('Mozilla/5.0 (compatible; BlackMusicBot/1.0; +https://music.black.co.ke)')
            ->get(config('services.news.feed_url'))
            ->throw()
            ->body();

        $feed = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($feed === false || ! isset($feed->channel->item)) {
            return collect();
        }

        // Every <item> shares one key, so keep them as a list.
        return collect(iterator_to_array($feed->channel->item, false))
            ->map(fn (SimpleXMLElement $item) => $this->story($item))
            ->filter(fn (array $story) => $story['url'] !== '' && $story['title'] !== '')
            ->reject(fn (array $story) => $this->isExcluded($story['source']))
            ->sortByDesc(fn (array $story) => $story['published_at']?->timestamp ?? 0)
            ->values();
    }

    protected function isExcluded(?string $source): bool
    {
        return collect(config('services.news.excluded_sources'))
            ->contains(fn (string $excluded) => strcasecmp(trim($excluded), (string) $source) === 0);
    }

    /**
     * @return array{url: string, title: string, source: ?string, published_at: ?Carbon}
     */
    protected function story(SimpleXMLElement $item): array
    {
        $source = Str::of((string) $item->source)
            ->trim()
            ->toString();

        return [
            'url' => trim((string) $item->link),
            // Google News appends " - <source>" to every headline.
            'title' => Str::of(html_entity_decode((string) $item->title, ENT_QUOTES | ENT_HTML5))
                ->when($source !== '', fn ($title) => $title->beforeLast(' - '.$source))
                ->squish()
                ->limit(250, '')
                ->toString(),
            'source' => $source !== '' ? $source : null,
            'published_at' => rescue(
                fn () => Carbon::parse((string) $item->pubDate),
                report: false,
            ),
        ];
    }
}
