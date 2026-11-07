<?php

namespace App\Services;

use App\Models\Setting;

/**
 * The admin's on/off switch for the AI agents (news summaries and the daily
 * story's research), kept in the settings table so it can be flipped
 * without a deploy. On until an admin turns it off.
 */
class AiAgents
{
    public const SETTING_KEY = 'ai_agents_enabled';

    /**
     * Whether an admin has left the AI agents switched on.
     */
    public function enabled(): bool
    {
        // Through the model, so the JSON cast turns the stored value back
        // into a bool (a raw "false" string would read as true).
        return (bool) (Setting::query()
            ->where('key', self::SETTING_KEY)
            ->first()
            ?->value ?? true);
    }

    public function setEnabled(bool $enabled): void
    {
        Setting::query()
            ->updateOrCreate(
                ['key' => self::SETTING_KEY],
                ['value' => $enabled],
            );
    }

    /**
     * Whether the agents may run right now: switched on, and an API key is
     * configured for the default provider.
     */
    public function available(): bool
    {
        return $this->enabled()
            && filled(config('ai.providers.'.config('ai.default').'.key'));
    }
}
