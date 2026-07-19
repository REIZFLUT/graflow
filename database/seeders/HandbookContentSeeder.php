<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleMedia;
use App\Models\PublicationChapter;
use App\Models\User;
use App\Services\ArticleMediaService;
use App\Support\Handbook;
use Database\Seeders\Support\Handbook\Chapters;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * Seeds the complete Graflow user handbook (chapters, articles, screenshots)
 * into the handbook publication resolved via App\Support\Handbook.
 *
 * Re-runnable: chapters and articles are matched by natural keys and their
 * content is refreshed on every run. Screenshot files are read from
 * database/seeders/handbook-images/; missing files are skipped with a warning
 * so the seeder also works without the screenshots present.
 */
class HandbookContentSeeder extends Seeder
{
    public function run(): void
    {
        $issue = Handbook::resolveIssue();

        if ($issue === null) {
            $this->command?->warn('Handbook skipped: no administrator exists to own the handbook publication.');

            return;
        }

        $publication = $issue->publication;
        $owner = User::query()->findOrFail($publication->owner_id);
        $mediaService = app(ArticleMediaService::class);

        foreach (Chapters::all() as $chapterPosition => $chapterDefinition) {
            $chapter = PublicationChapter::query()->updateOrCreate(
                [
                    'publication_issue_id' => $issue->id,
                    'position' => $chapterPosition,
                ],
                [
                    'title' => $chapterDefinition['title'],
                ],
            );

            foreach (array_values($chapterDefinition['articles']) as $index => $articleDefinition) {
                $article = Article::query()->updateOrCreate(
                    [
                        'publication_issue_id' => $issue->id,
                        'title' => $articleDefinition['title'],
                    ],
                    [
                        'owner_id' => $owner->id,
                        'product_manager_id' => $owner->id,
                        'author_id' => $owner->id,
                        'current_assignee_id' => $owner->id,
                        'status' => ArticleStatus::Authoring,
                        'publication_chapter_id' => $chapter->id,
                        'position' => $index + 1,
                        'editor_settings_set_id' => $publication->editor_settings_set_id,
                    ],
                );

                $article->update([
                    'content' => [
                        'type' => 'doc',
                        'content' => $this->resolveImages($articleDefinition['content'], $article, $owner, $mediaService),
                    ],
                ]);
            }
        }

        $this->command?->info('Graflow handbook seeded.');
    }

    /**
     * Replace 'handbookImagePlaceholder' nodes with real 'articleImage' nodes,
     * importing the screenshot file for the article when necessary.
     *
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    private function resolveImages(array $nodes, Article $article, User $owner, ArticleMediaService $mediaService): array
    {
        $resolved = [];

        foreach ($nodes as $node) {
            if (($node['type'] ?? null) !== 'handbookImagePlaceholder') {
                $resolved[] = $node;

                continue;
            }

            $filename = $node['attrs']['filename'];
            $media = $this->resolveMedia($article, $owner, $mediaService, $filename, [
                'alt_text' => $node['attrs']['alt'],
                'copyright' => 'Graflow Screenshot',
                'caption' => $node['attrs']['caption'] ?? null,
            ]);

            if ($media === null) {
                $this->command?->warn("Handbook image skipped (file missing): {$filename}");

                continue;
            }

            $resolved[] = [
                'type' => 'articleImage',
                'attrs' => [
                    'mediaId' => $media->id,
                    'alt' => $media->alt_text,
                    'copyright' => $media->copyright,
                    'caption' => $media->caption,
                    'previewWebpUrl' => route('articles.media.file', [
                        'article' => $media->article_id,
                        'media' => $media->id,
                        'variant' => 'preview-webp',
                    ]),
                    'previewJpegUrl' => route('articles.media.file', [
                        'article' => $media->article_id,
                        'media' => $media->id,
                        'variant' => 'preview-jpeg',
                    ]),
                ],
            ];
        }

        return $resolved;
    }

    /**
     * @param  array{alt_text: string, copyright: string, caption?: string|null}  $metadata
     */
    private function resolveMedia(
        Article $article,
        User $owner,
        ArticleMediaService $mediaService,
        string $filename,
        array $metadata,
    ): ?ArticleMedia {
        $existing = ArticleMedia::query()
            ->where('article_id', $article->id)
            ->where('original_filename', $filename)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $path = database_path('seeders/handbook-images/'.$filename);

        if (! File::exists($path)) {
            return null;
        }

        $file = new UploadedFile(
            $path,
            $filename,
            File::mimeType($path) ?: 'image/png',
            null,
            true,
        );

        return $mediaService->storeForArticle($file, $article, $owner, $metadata);
    }
}
