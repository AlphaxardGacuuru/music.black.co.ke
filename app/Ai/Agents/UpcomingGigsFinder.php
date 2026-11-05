<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;

/**
 * Finds live music by Kenyan and East African artists in Kenya over the
 * coming week, for the daily story. Replies with a JSON array.
 */
#[Timeout(120)]
class UpcomingGigsFinder implements Agent, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<'TEXT'
            You find upcoming live music in Kenya for Black Music, a Kenyan music app.

            You get today's date. Search the web for concerts, shows and festivals in Kenya in the next 7 days that feature Kenyan or East African musicians.

            Rules:
            - Only include an event when a ticketing page, event listing, venue page or news article you found states its date and venue. Never guess or invent an event, date or venue.
            - Reply with a JSON array and nothing else, at most 4 events, soonest first:
              [{"date": "YYYY-MM-DD", "venue": "Alchemist", "town": "Nairobi", "event": "Bien live with his band", "source_url": "https://..."}]
            - "event" is at most 60 characters and names the main artist. "source_url" is the page that confirms the event.
            - If you can't confirm any events, reply with [].
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
