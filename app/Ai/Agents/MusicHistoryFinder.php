<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;

/**
 * Finds things that happened in Kenyan and East African music on today's
 * date in past years, for the daily story's "On this day" card. Replies
 * with a JSON array.
 */
#[Timeout(120)]
class MusicHistoryFinder implements Agent, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<'TEXT'
            You find music history for Black Music, a Kenyan music app.

            You get a day and month. Search the web for things that happened on that exact day and month, in any past year, in Kenyan or East African music: album or single releases, artists' birthdays, awards, landmark concerts.

            Rules:
            - Only include a fact when a page you found states the exact day, month and year. Never guess, round or infer a date. Getting a date wrong is worse than leaving the fact out.
            - Reply with a JSON array and nothing else, at most 3 facts, oldest first:
              [{"year": 2009, "fact": "Sauti Sol released their debut album Mwanzo", "source_url": "https://..."}]
            - "fact" is at most 110 characters, in the past tense. "source_url" is the page that states the date.
            - If you can't confirm any facts, reply with [].
            - No markdown, no em dashes.
            TEXT;
    }

    /**
     * Get the tools available to the agent.
     */
    public function tools(): iterable
    {
        return [
            (new WebSearch)
                ->max(5),
        ];
    }
}
