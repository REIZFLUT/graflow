<?php

namespace Database\Seeders\Support;

use App\Models\Article;
use App\Models\ArticleMedia;
use App\Models\User;
use App\Services\ArticleMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class DemoMediaImporter
{
    public function __construct(
        private ArticleMediaService $mediaService,
    ) {}

    /**
     * @param  array{alt_text: string, copyright: string, caption?: string|null}  $metadata
     */
    public function import(Article $article, User $owner, string $filename, array $metadata): ArticleMedia
    {
        $path = database_path('seeders/demo-article-images/'.$filename);

        if (! File::exists($path)) {
            throw new \RuntimeException("Demo image not found: {$path}");
        }

        $mimeType = File::mimeType($path) ?: 'image/jpeg';

        $file = new UploadedFile(
            $path,
            $filename,
            $mimeType,
            null,
            true,
        );

        return $this->mediaService->storeForArticle($file, $article, $owner, $metadata);
    }

    public function previewUrls(ArticleMedia $media): array
    {
        return [
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
        ];
    }

    /**
     * @return list<string>
     */
    public function availableImageFilenames(): array
    {
        $directory = database_path('seeders/demo-article-images');

        return collect(File::files($directory))
            ->map(fn (\SplFileInfo $file) => $file->getFilename())
            ->sort()
            ->values()
            ->all();
    }
}
