<?php

namespace Database\Seeders\Support\Handbook;

/**
 * Builders for the TipTap/ProseMirror node arrays used by the handbook seeder.
 * Image nodes are emitted as 'handbookImagePlaceholder' and replaced with real
 * 'articleImage' nodes by the seeder once the media file has been imported.
 */
class Nodes
{
    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return array<string, mixed>
     */
    public static function doc(array $nodes): array
    {
        return [
            'type' => 'doc',
            'content' => $nodes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function h2(string $text): array
    {
        return self::heading($text, 2);
    }

    /**
     * @return array<string, mixed>
     */
    public static function h3(string $text): array
    {
        return self::heading($text, 3);
    }

    /**
     * @return array<string, mixed>
     */
    private static function heading(string $text, int $level): array
    {
        return [
            'type' => 'heading',
            'attrs' => ['level' => $level],
            'content' => [
                ['type' => 'text', 'text' => $text],
            ],
        ];
    }

    /**
     * Paragraph from a mix of plain strings and pre-built text nodes (see b()/i()).
     *
     * @param  string|array<string, mixed>  ...$parts
     * @return array<string, mixed>
     */
    public static function p(string|array ...$parts): array
    {
        return [
            'type' => 'paragraph',
            'content' => self::inline(...$parts),
        ];
    }

    /**
     * @param  string|array<string, mixed>  ...$parts
     * @return list<array<string, mixed>>
     */
    public static function inline(string|array ...$parts): array
    {
        return array_values(array_map(
            fn (string|array $part) => is_string($part)
                ? ['type' => 'text', 'text' => $part]
                : $part,
            $parts,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public static function b(string $text): array
    {
        return [
            'type' => 'text',
            'text' => $text,
            'marks' => [['type' => 'bold']],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function i(string $text): array
    {
        return [
            'type' => 'text',
            'text' => $text,
            'marks' => [['type' => 'italic']],
        ];
    }

    /**
     * @param  list<string|array<string, mixed>|list<string|array<string, mixed>>>  $items
     * @return array<string, mixed>
     */
    public static function bullets(array $items): array
    {
        return self::list('bulletList', $items);
    }

    /**
     * @param  list<string|array<string, mixed>|list<string|array<string, mixed>>>  $items
     * @return array<string, mixed>
     */
    public static function steps(array $items): array
    {
        return self::list('orderedList', $items);
    }

    /**
     * @param  list<string|array<string, mixed>|list<string|array<string, mixed>>>  $items
     * @return array<string, mixed>
     */
    private static function list(string $type, array $items): array
    {
        return [
            'type' => $type,
            'content' => array_values(array_map(
                function (string|array $item): array {
                    $parts = is_string($item) || isset($item['type']) ? [$item] : $item;

                    return [
                        'type' => 'listItem',
                        'content' => [self::p(...$parts)],
                    ];
                },
                $items,
            )),
        ];
    }

    /**
     * @param  string|array<string, mixed>  ...$parts
     * @return array<string, mixed>
     */
    public static function info(string|array ...$parts): array
    {
        return [
            'type' => 'infoBox',
            'content' => [self::p(...$parts)],
        ];
    }

    /**
     * @param  list<string>  $header
     * @param  list<list<string>>  $rows
     * @return array<string, mixed>
     */
    public static function table(array $header, array $rows): array
    {
        $headerRow = [
            'type' => 'tableRow',
            'content' => array_values(array_map(
                fn (string $text) => self::cell('tableHeader', $text),
                $header,
            )),
        ];

        $bodyRows = array_values(array_map(
            fn (array $row) => [
                'type' => 'tableRow',
                'content' => array_values(array_map(
                    fn (string $text) => self::cell('tableCell', $text),
                    $row,
                )),
            ],
            $rows,
        ));

        return [
            'type' => 'table',
            'content' => [$headerRow, ...$bodyRows],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function cell(string $type, string $text): array
    {
        return [
            'type' => $type,
            'content' => [self::p($text)],
        ];
    }

    /**
     * Placeholder resolved to an 'articleImage' node by HandbookContentSeeder.
     *
     * @return array<string, mixed>
     */
    public static function img(string $filename, string $alt, ?string $caption = null): array
    {
        return [
            'type' => 'handbookImagePlaceholder',
            'attrs' => [
                'filename' => $filename,
                'alt' => $alt,
                'caption' => $caption,
            ],
        ];
    }
}
