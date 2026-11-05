<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;

/**
 * Turns a news headline into a short post in its own words, after looking
 * the story up on the web. Replies "SKIP" when the story isn't about a
 * local artist.
 */
#[Timeout(120)]
class LocalArtistNewsWriter implements Agent, HasTools
{
    use Promptable;

    /**
     * The reply that means "don't post this story".
     */
    public const SKIP = 'SKIP';

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<'TEXT'
            You write short news updates for Black Music, a Kenyan music app, about Kenyan and East African musicians.

            You get a news headline and the outlet that published it. Search the web for the story and read the coverage, then write a summary of it in your own words.

            Rules:
            - If the story is not mainly about a Kenyan or East African musician, singer, rapper, DJ, producer, band or music event, reply with exactly SKIP and nothing else.
            - Write 2 to 4 short paragraphs, at most 700 characters in total.
            - Never copy sentences from the coverage. Use your own words.
            - Only state facts the coverage supports. If you can't find the story, write one sentence restating what the headline says.
            - Do not repeat the headline and do not name or link the source; those are added for you.
            - Plain text only: no markdown, hashtags, emoji or em dashes.
            TEXT;
    }

    /**
     * Get the tools available to the agent.
     */
    public function tools(): iterable
    {
        return [
            (new WebSearch)
                ->max(3),
        ];
    }
}
