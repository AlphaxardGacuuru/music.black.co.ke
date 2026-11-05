<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_CALLBACK_URL'),
    ],

    'vonage' => [
        'key' => env('VONAGE_KEY'),
        'secret' => env('VONAGE_SECRET'),
        'sms_from' => env('VONAGE_SMS_FROM', '254700364446'),
    ],

    'kopokopo' => [
        'environment' => env('KOPOKOPO_ENV', 'live'),
        // The till/paybill number this app receives payments into (wallet
        // top-ups for buying songs), not environment-specific like the API
        // credentials above, so it lives at the top level.
        'till_number' => env('KOPOKOPO_TILL_NUMBER'),
        // Local development only: accept hand-made callbacks without
        // Kopokopo's signature (see VerifyKopokopoSignature).
        'allow_unsigned_callbacks' => (bool) env('KOPOKOPO_ALLOW_UNSIGNED_CALLBACKS', false),
        'sandbox' => [
            'clientId' => env('KOPOKOPO_CLIENT_ID_SANDBOX'),
            'clientSecret' => env('KOPOKOPO_CLIENT_SECRET_SANDBOX'),
            'apiKey' => env('KOPOKOPO_API_KEY_SANDBOX'),
            'baseUrl' => env('KOPOKOPO_BASE_URL_SANDBOX'),
        ],
        'live' => [
            'clientId' => env('KOPOKOPO_CLIENT_ID'),
            'clientSecret' => env('KOPOKOPO_CLIENT_SECRET'),
            'apiKey' => env('KOPOKOPO_API_KEY'),
            'baseUrl' => env('KOPOKOPO_BASE_URL'),
        ],
    ],

    // Local artist news, posted by the house account (see
    // PostLocalArtistNewsJob).
    'news' => [
        'poster_email' => env('NEWS_POSTER_EMAIL', 'al@music.black.co.ke'),
        // Google News search for Kenyan music stories from the last two days.
        'feed_url' => env(
            'NEWS_FEED_URL',
            'https://news.google.com/rss/search?q='
                .urlencode('("Kenyan musician" OR "Kenyan artist" OR "Kenyan singer" OR "Kenyan rapper" OR gengetone) when:2d')
                .'&hl=en-KE&gl=KE&ceid=KE:en'
        ),
        // Feed sources never posted from: song download sites, which give
        // away the music artists sell here.
        'excluded_sources' => array_filter(explode(',', env(
            'NEWS_EXCLUDED_SOURCES',
            'Tooxclusive,TrendyHipHop'
        ))),
        // New posts per run. The job runs three times a day.
        'posts_per_run' => (int) env('NEWS_POSTS_PER_RUN', 2),
    ],
];
