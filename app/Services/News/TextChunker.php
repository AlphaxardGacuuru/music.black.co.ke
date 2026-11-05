<?php

namespace App\Services\News;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Splits long text into parts that fit a length limit, for posting a long
 * story as a post followed by comments.
 */
class TextChunker
{
    /**
     * Split text into parts of at most $limit characters. Parts break after
     * a sentence when one ends past half the limit, otherwise between words.
     * A single word longer than the limit is cut.
     *
     * @return Collection<int, string>
     */
    public function chunk(string $text, int $limit): Collection
    {
        // Each token is a word with the whitespace that follows it.
        $tokens = preg_split('/(?<=\s)(?=\S)/u', trim($text)) ?: [];

        return collect($tokens)
            ->reduce(function (Collection $parts, string $token) use ($limit) {
                $current = $parts->pop() ?? '';

                if (mb_strlen(rtrim($current.$token)) <= $limit) {
                    return $parts->push($current.$token);
                }

                $breakAt = $this->lastSentenceEnd($current, intdiv($limit, 2));

                return $parts
                    ->push(mb_substr($current, 0, $breakAt))
                    ->merge($this->hardSplit(mb_substr($current, $breakAt).$token, $limit));
            }, collect())
            ->map(fn (string $part) => trim($part))
            ->filter()
            ->values();
    }

    /**
     * Where to break $text: just after its last sentence end at or past
     * $minimum characters, or at its end when there is none.
     */
    protected function lastSentenceEnd(string $text, int $minimum): int
    {
        preg_match_all('/[.!?]["\')]?\s+|\n+/u', $text, $matches, PREG_OFFSET_CAPTURE);

        return collect($matches[0])
            // Offsets are in bytes; count characters instead.
            ->map(fn (array $match) => mb_strlen(substr($text, 0, $match[1] + strlen($match[0]))))
            ->filter(fn (int $end) => $end >= $minimum)
            ->last() ?? mb_strlen($text);
    }

    /**
     * Cut text that is still too long (one very long word) into $limit parts.
     *
     * @return array<int, string>
     */
    protected function hardSplit(string $text, int $limit): array
    {
        // Untrimmed, so the last piece keeps the space before the next word.
        return mb_strlen(rtrim($text)) <= $limit
            ? [$text]
            : Str::of($text)
                ->split($limit)
                ->all();
    }
}
