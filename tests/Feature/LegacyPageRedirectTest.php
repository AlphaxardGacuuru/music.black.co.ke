<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegacyPageRedirectTest extends TestCase
{
    public static function legacyUrls(): array
    {
        return [
            ['/video', '/videos'],
            ['/video/charts', '/videos/charts'],
            ['/audio/create', '/audios/create'],
            ['/audio/album/create', '/audios/album/create'],
            ['/video/show/abc', '/videos/abc/view'],
            ['/audio/edit/abc', '/audios/abc/edit'],
            ['/video/album/edit/abc', '/videos/album/abc/edit'],
            ['/profile/show/@blackmusic', '/profile/@blackmusic/view'],
            ['/chat/show/@fan', '/chat/@fan/view'],
            ['/post/show/abc', '/post/abc/view'],
            ['/post/edit/abc', '/post/abc/edit'],
            ['/story/show/abc', '/story/abc/view'],
            ['/karaoke/show/abc', '/karaoke/abc/view'],
        ];
    }

    #[DataProvider('legacyUrls')]
    public function test_old_page_urls_redirect_to_the_new_ones(string $from, string $to): void
    {
        $this->get($from)
            ->assertStatus(301)
            ->assertRedirect($to);
    }

    public function test_new_page_urls_serve_the_app(): void
    {
        $this->get('/videos/abc/view')->assertOk();
        $this->get('/profile/@blackmusic/view')->assertOk();
    }
}
