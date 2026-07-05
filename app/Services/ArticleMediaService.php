<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use RuntimeException;

class ArticleMediaService
{
    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver);
    }

    /**
     * @param  array{alt_text: string, copyright: string, caption?: string|null}  $metadata
     */
    public function storeStaging(
        UploadedFile $file,
        User $user,
        string $stagingToken,
        array $metadata,
    ): ArticleMedia {
        $mediaId = (string) Str::uuid();
        $basePath = "articles/staging/{$stagingToken}";

        return $this->storeMedia($file, $user, $basePath, $metadata, $mediaId, null, $stagingToken);
    }

    /**
     * @param  array{alt_text: string, copyright: string, caption?: string|null}  $metadata
     */
    public function storeForArticle(
        UploadedFile $file,
        Article $article,
        User $user,
        array $metadata,
    ): ArticleMedia {
        $mediaId = (string) Str::uuid();
        $basePath = "articles/{$article->id}";

        return $this->storeMedia($file, $user, $basePath, $metadata, $mediaId, $article->id, null);
    }

    /**
     * @param  array{alt_text: string, copyright: string, caption?: string|null}  $metadata
     */
    private function storeMedia(
        UploadedFile $file,
        User $user,
        string $basePath,
        array $metadata,
        string $mediaId,
        ?int $articleId,
        ?string $stagingToken,
    ): ArticleMedia {
        $disk = Storage::disk(config('article-media.disk'));
        $extension = $file->guessExtension() ?? 'bin';
        $originalPath = "{$basePath}/original/{$mediaId}.{$extension}";
        $previewWebpPath = "{$basePath}/preview/{$mediaId}.webp";
        $previewJpegPath = "{$basePath}/preview/{$mediaId}.jpg";

        $disk->putFileAs("{$basePath}/original", $file, "{$mediaId}.{$extension}");

        $image = $this->imageManager->read($disk->path($originalPath));
        $width = $image->width();
        $height = $image->height();

        $preview = $this->scalePreview($image);
        $disk->put($previewWebpPath, (string) $preview->toWebp(config('article-media.preview_webp_quality')));
        $disk->put($previewJpegPath, (string) $preview->toJpeg(config('article-media.preview_jpeg_quality')));

        return ArticleMedia::query()->create([
            'id' => $mediaId,
            'article_id' => $articleId,
            'owner_id' => $user->id,
            'staging_token' => $stagingToken,
            'original_path' => $originalPath,
            'preview_webp_path' => $previewWebpPath,
            'preview_jpeg_path' => $previewJpegPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'width' => $width,
            'height' => $height,
            'file_size' => $file->getSize() ?: 0,
            'alt_text' => $metadata['alt_text'],
            'copyright' => $metadata['copyright'],
            'caption' => $metadata['caption'] ?? null,
        ]);
    }

    private function scalePreview(ImageInterface $image): ImageInterface
    {
        $maxWidth = config('article-media.preview_max_width');

        if ($image->width() <= $maxWidth) {
            return $image;
        }

        return $image->scale(width: $maxWidth);
    }

    public function claimStagingMedia(Article $article, ?string $stagingToken, User $user): void
    {
        if ($stagingToken === null || $stagingToken === '') {
            return;
        }

        $disk = Storage::disk(config('article-media.disk'));
        $stagingBase = "articles/staging/{$stagingToken}";
        $articleBase = "articles/{$article->id}";

        $mediaItems = ArticleMedia::query()
            ->where('staging_token', $stagingToken)
            ->where('owner_id', $user->id)
            ->whereNull('article_id')
            ->get();

        foreach ($mediaItems as $media) {
            $newOriginalPath = str_replace($stagingBase, $articleBase, $media->original_path);
            $newPreviewWebpPath = str_replace($stagingBase, $articleBase, $media->preview_webp_path);
            $newPreviewJpegPath = str_replace($stagingBase, $articleBase, $media->preview_jpeg_path);

            $this->moveFile($disk, $media->original_path, $newOriginalPath);
            $this->moveFile($disk, $media->preview_webp_path, $newPreviewWebpPath);
            $this->moveFile($disk, $media->preview_jpeg_path, $newPreviewJpegPath);

            $media->update([
                'article_id' => $article->id,
                'staging_token' => null,
                'original_path' => $newOriginalPath,
                'preview_webp_path' => $newPreviewWebpPath,
                'preview_jpeg_path' => $newPreviewJpegPath,
            ]);
        }

        if ($disk->exists($stagingBase)) {
            $disk->deleteDirectory($stagingBase);
        }
    }

    /**
     * @param  array{alt_text?: string, copyright?: string, caption?: string|null}  $metadata
     */
    public function updateMetadata(ArticleMedia $media, array $metadata): ArticleMedia
    {
        $updates = [];

        if (isset($metadata['alt_text'])) {
            $updates['alt_text'] = $metadata['alt_text'];
        }

        if (isset($metadata['copyright'])) {
            $updates['copyright'] = $metadata['copyright'];
        }

        if (array_key_exists('caption', $metadata)) {
            $updates['caption'] = $metadata['caption'];
        }

        $media->update($updates);

        return $media->fresh();
    }

    public function delete(ArticleMedia $media): void
    {
        $disk = Storage::disk(config('article-media.disk'));

        foreach ([$media->original_path, $media->preview_webp_path, $media->preview_jpeg_path] as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $media->delete();
    }

    public function isReferencedInContent(ArticleMedia $media, Article $article): bool
    {
        $content = $article->content;

        if (! is_array($content)) {
            return false;
        }

        return $this->contentContainsMediaId($content, $media->id);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function contentContainsMediaId(array $node, string $mediaId): bool
    {
        if (
            ($node['type'] ?? null) === 'articleImage'
            && ($node['attrs']['mediaId'] ?? null) === $mediaId
        ) {
            return true;
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child) && $this->contentContainsMediaId($child, $mediaId)) {
                return true;
            }
        }

        return false;
    }

    public function pruneExpiredStaging(): int
    {
        $cutoff = now()->subHours(config('article-media.staging_ttl_hours'));
        $disk = Storage::disk(config('article-media.disk'));
        $count = 0;

        $expired = ArticleMedia::query()
            ->whereNull('article_id')
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($expired as $media) {
            $stagingBase = $media->staging_token
                ? "articles/staging/{$media->staging_token}"
                : null;

            $this->delete($media);
            $count++;

            if ($stagingBase && $disk->exists($stagingBase) && empty($disk->allFiles($stagingBase))) {
                $disk->deleteDirectory($stagingBase);
            }
        }

        return $count;
    }

    public function resolveFilePath(ArticleMedia $media, string $variant): string
    {
        return match ($variant) {
            'original' => $media->original_path,
            'preview-webp' => $media->preview_webp_path,
            'preview-jpeg' => $media->preview_jpeg_path,
            default => throw new RuntimeException("Unknown media variant: {$variant}"),
        };
    }

    /**
     * @param  \Illuminate\Contracts\Filesystem\Filesystem  $disk
     */
    private function moveFile($disk, string $from, string $to): void
    {
        $directory = dirname($to);

        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        if ($disk->exists($from)) {
            $disk->move($from, $to);
        }
    }
}
