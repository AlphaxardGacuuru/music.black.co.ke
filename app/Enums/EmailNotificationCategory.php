<?php

namespace App\Enums;

enum EmailNotificationCategory: string
{
    case REFERRAL_SIGNED_UP = 'referral-signed-up';

    public function settingsKey(): string
    {
        return match ($this) {
            self::REFERRAL_SIGNED_UP => 'referralSignupNotification',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::REFERRAL_SIGNED_UP => 'referral signup',
        };
    }
}
