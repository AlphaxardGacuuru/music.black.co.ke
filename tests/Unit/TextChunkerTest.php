<?php

namespace Tests\Unit;

use App\Services\News\TextChunker;
use PHPUnit\Framework\TestCase;

class TextChunkerTest extends TestCase
{
    public function test_short_text_is_one_part(): void
    {
        $this->assertSame(['Hello there.'], (new TextChunker)
            ->chunk('  Hello there.  ', 50)
            ->all());
    }

    public function test_parts_fit_the_limit_and_keep_every_word(): void
    {
        $text = str_repeat('Kenyan music is having a big year with new albums. ', 20);

        $parts = (new TextChunker)
            ->chunk($text, 100);

        $parts->each(fn (string $part) => $this->assertLessThanOrEqual(100, mb_strlen($part)));
        $this->assertSame(
            preg_split('/\s+/', trim($text)),
            preg_split('/\s+/', $parts->implode(' '))
        );
    }

    public function test_parts_break_after_a_sentence_when_one_ends_past_half_the_limit(): void
    {
        $parts = (new TextChunker)
            ->chunk('One two three four five six. Seven eight nine ten eleven twelve thirteen.', 40);

        $this->assertSame('One two three four five six.', $parts->first());
    }

    public function test_a_word_longer_than_the_limit_is_cut(): void
    {
        $parts = (new TextChunker)
            ->chunk('See '.str_repeat('x', 25).' now', 10);

        $parts->each(fn (string $part) => $this->assertLessThanOrEqual(10, mb_strlen($part)));
        $this->assertSame('See '.str_repeat('x', 25).' now', preg_replace('/(?<=x) (?=x)/', '', $parts->implode(' ')));
    }

    public function test_multibyte_text_is_counted_in_characters(): void
    {
        $parts = (new TextChunker)
            ->chunk(str_repeat('Ñ ', 30), 10);

        $parts->each(fn (string $part) => $this->assertLessThanOrEqual(10, mb_strlen($part)));
        $this->assertSame(30, mb_substr_count($parts->implode(' '), 'Ñ'));
    }
}
