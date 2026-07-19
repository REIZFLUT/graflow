<?php

namespace App\Support;

class ArticleCharacterCounter
{
    /**
     * @param  array<string, mixed>|null  $content
     */
    public function count(string $title, ?array $content): int
    {
        $text = trim($title)."\n".$this->extractText($content ?? []);

        return preg_match_all('/\p{L}/u', $text) ?: 0;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function extractText(array $node): string
    {
        $text = is_string($node['text'] ?? null) ? $node['text'] : '';
        $children = is_array($node['content'] ?? null) ? $node['content'] : [];

        foreach ($children as $child) {
            if (is_array($child)) {
                $text .= "\n".$this->extractText($child);
            }
        }

        return $text;
    }
}
