<?php

namespace Database\Seeders\Support;

use App\Models\ArticleMedia;
use Illuminate\Support\Str;

class DemoArticleContentBuilder
{
    public function __construct(
        private DemoMediaImporter $mediaImporter,
        private DemoEditorShowcaseBuilder $showcaseBuilder = new DemoEditorShowcaseBuilder,
    ) {}

    /**
     * @param  list<array{type: string, level?: int, text: string, marginal_note?: string|null}>  $sections
     * @param  list<ArticleMedia>  $mediaItems
     * @return array<string, mixed>
     */
    public function build(array $sections, array $mediaItems, bool $withMarginalNotes): array
    {
        $nodes = [];
        $title = $this->extractTitle($sections);
        $bodySections = $this->bodySectionsWithoutTitle($sections);

        $nodes[] = $this->buildTitleHeading($title, $withMarginalNotes);
        $nodes = array_merge($nodes, $this->showcaseBuilder->build($withMarginalNotes));

        $blockIndex = count($nodes);
        $imageIndex = 0;
        $imageInsertAfterBlocks = $this->imageInsertPositions(
            count($bodySections),
            count($mediaItems),
            $blockIndex,
        );

        foreach ($bodySections as $section) {
            $blockIndex++;
            $nodes[] = $this->buildSectionNode($section, $blockIndex, $withMarginalNotes);

            if (in_array($blockIndex, $imageInsertAfterBlocks, true) && isset($mediaItems[$imageIndex])) {
                $nodes[] = $this->buildImageNode($mediaItems[$imageIndex]);
                $imageIndex++;
            }
        }

        while ($imageIndex < count($mediaItems)) {
            $nodes[] = $this->buildImageNode($mediaItems[$imageIndex]);
            $imageIndex++;
        }

        return [
            'type' => 'doc',
            'content' => $nodes,
        ];
    }

    /**
     * @param  list<array{type: string, level?: int, text: string, marginal_note?: string|null}>  $sections
     */
    private function extractTitle(array $sections): string
    {
        foreach ($sections as $section) {
            if (($section['type'] ?? null) === 'heading' && ($section['level'] ?? 2) === 2) {
                return $section['text'];
            }
        }

        return 'Energieberatung';
    }

    /**
     * @param  list<array{type: string, level?: int, text: string, marginal_note?: string|null}>  $sections
     * @return list<array{type: string, level?: int, text: string, marginal_note?: string|null}>
     */
    private function bodySectionsWithoutTitle(array $sections): array
    {
        $skippedTitle = false;

        return array_values(array_filter(
            $sections,
            function (array $section) use (&$skippedTitle): bool {
                if (
                    ! $skippedTitle
                    && ($section['type'] ?? null) === 'heading'
                    && ($section['level'] ?? 2) === 2
                ) {
                    $skippedTitle = true;

                    return false;
                }

                return true;
            },
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTitleHeading(string $title, bool $withMarginalNotes): array
    {
        return [
            'type' => 'heading',
            'attrs' => [
                'level' => 2,
                'id' => 'article-title-'.Str::slug(Str::limit($title, 50, '')),
                'marginalNote' => $withMarginalNotes ? 'Kernbotschaft des Beitrags' : null,
            ],
            'content' => [
                ['type' => 'text', 'text' => $title],
            ],
        ];
    }

    /**
     * @param  array{type: string, level?: int, text: string, marginal_note?: string|null}  $section
     * @return array<string, mixed>
     */
    private function buildSectionNode(array $section, int $blockIndex, bool $withMarginalNotes): array
    {
        $marginalNote = $withMarginalNotes ? ($section['marginal_note'] ?? null) : null;

        if ($section['type'] === 'heading') {
            return [
                'type' => 'heading',
                'attrs' => [
                    'level' => $section['level'] ?? 2,
                    'id' => 'block-'.$blockIndex.'-'.Str::slug(Str::limit($section['text'], 40, '')),
                    'marginalNote' => $marginalNote,
                ],
                'content' => [
                    ['type' => 'text', 'text' => $section['text']],
                ],
            ];
        }

        return [
            'type' => 'paragraph',
            'attrs' => [
                'id' => 'block-'.$blockIndex.'-'.Str::slug(Str::limit($section['text'], 40, '')),
                'marginalNote' => $marginalNote,
            ],
            'content' => [
                ['type' => 'text', 'text' => $section['text']],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildImageNode(ArticleMedia $media): array
    {
        $urls = $this->mediaImporter->previewUrls($media);

        return [
            'type' => 'articleImage',
            'attrs' => [
                'mediaId' => $media->id,
                'alt' => $media->alt_text,
                'copyright' => $media->copyright,
                'caption' => $media->caption,
                'previewWebpUrl' => $urls['previewWebpUrl'],
                'previewJpegUrl' => $urls['previewJpegUrl'],
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function imageInsertPositions(int $bodySectionCount, int $imageCount, int $offset): array
    {
        if ($imageCount === 0) {
            return [];
        }

        if ($imageCount === 1) {
            return [max($offset + 1, $offset + (int) floor($bodySectionCount / 3))];
        }

        return [
            max($offset + 1, $offset + (int) floor($bodySectionCount / 4)),
            max($offset + 2, $offset + (int) floor($bodySectionCount * 3 / 4)),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function countWords(array $content): int
    {
        return $this->countWordsInNode($content);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function countWordsInNode(array $node): int
    {
        $count = 0;

        if (($node['type'] ?? null) === 'text' && isset($node['text'])) {
            $words = preg_split('/\s+/u', trim((string) $node['text']), -1, PREG_SPLIT_NO_EMPTY);

            return is_array($words) ? count($words) : 0;
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                $count += $this->countWordsInNode($child);
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function hasMarginalNotes(array $content): bool
    {
        return $this->nodeHasMarginalNote($content);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function nodeHasMarginalNote(array $node): bool
    {
        $marginalNote = $node['attrs']['marginalNote'] ?? null;

        if (is_string($marginalNote) && trim($marginalNote) !== '') {
            return true;
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child) && $this->nodeHasMarginalNote($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function countArticleImages(array $content): int
    {
        return $this->countImagesInNode($content);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function countImagesInNode(array $node): int
    {
        $count = ($node['type'] ?? null) === 'articleImage' ? 1 : 0;

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                $count += $this->countImagesInNode($child);
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function hasAllEditorFeatures(array $content, bool $expectsMarginalNotes): bool
    {
        $requiredNodeTypes = [
            'heading',
            'paragraph',
            'inlineMath',
            'blockMath',
            'bulletList',
            'orderedList',
            'blockquote',
            'infoBox',
            'table',
            'articleImage',
        ];

        foreach ($requiredNodeTypes as $type) {
            if (! $this->nodeTypeExists($content, $type)) {
                return false;
            }
        }

        $requiredMarks = ['bold', 'italic', 'subscript', 'superscript', 'footnote', 'characterFormat'];

        foreach ($requiredMarks as $mark) {
            if (! $this->markTypeExists($content, $mark)) {
                return false;
            }
        }

        if (! $this->paragraphFormatExists($content, 'autorenkommentar')) {
            return false;
        }

        if ($expectsMarginalNotes && ! $this->hasMarginalNotes($content)) {
            return false;
        }

        if (! $expectsMarginalNotes && $this->hasMarginalNotes($content)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function nodeTypeExists(array $node, string $type): bool
    {
        if (($node['type'] ?? null) === $type) {
            return true;
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child) && $this->nodeTypeExists($child, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function markTypeExists(array $node, string $markType): bool
    {
        foreach ($node['marks'] ?? [] as $mark) {
            if (is_array($mark) && ($mark['type'] ?? null) === $markType) {
                return true;
            }
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child) && $this->markTypeExists($child, $markType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function paragraphFormatExists(array $node, string $format): bool
    {
        if (
            ($node['type'] ?? null) === 'paragraph'
            && ($node['attrs']['paragraphFormat'] ?? null) === $format
        ) {
            return true;
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child) && $this->paragraphFormatExists($child, $format)) {
                return true;
            }
        }

        return false;
    }
}
