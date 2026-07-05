<?php

namespace App\Http\Resources;

use App\Models\ArticleMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ArticleMedia */
class ArticleMediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ArticleMedia $media */
        $media = $this->resource;

        return [
            'id' => $media->id,
            'article_id' => $media->article_id,
            'original_filename' => $media->original_filename,
            'mime_type' => $media->mime_type,
            'width' => $media->width,
            'height' => $media->height,
            'file_size' => $media->file_size,
            'alt_text' => $media->alt_text,
            'copyright' => $media->copyright,
            'caption' => $media->caption,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
            'preview_webp_url' => $this->fileUrl($media, 'preview-webp'),
            'preview_jpeg_url' => $this->fileUrl($media, 'preview-jpeg'),
            'original_url' => $this->fileUrl($media, 'original'),
        ];
    }

    private function fileUrl(ArticleMedia $media, string $variant): string
    {
        if ($media->isStaging()) {
            return route('articles.media.staging.file', [
                'media' => $media->id,
                'variant' => $variant,
            ]);
        }

        return route('articles.media.file', [
            'article' => $media->article_id,
            'media' => $media->id,
            'variant' => $variant,
        ]);
    }
}
