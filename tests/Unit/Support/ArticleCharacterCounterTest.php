<?php

namespace Tests\Unit\Support;

use App\Support\ArticleCharacterCounter;
use PHPUnit\Framework\TestCase;

class ArticleCharacterCounterTest extends TestCase
{
    public function test_it_counts_unicode_letters_in_title_and_nested_tiptap_content(): void
    {
        $counter = new ArticleCharacterCounter;
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Über Öl 123!'],
                    ],
                ],
            ],
        ];

        $this->assertSame(10, $counter->count('Grün', $content));
    }

    public function test_it_handles_empty_content(): void
    {
        $this->assertSame(0, (new ArticleCharacterCounter)->count('', null));
    }
}
