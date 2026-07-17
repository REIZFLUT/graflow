<?php

namespace App\Services;

use App\Enums\ArticlePdfKind;
use App\Models\Article;
use App\Models\ArticlePdf;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ArticlePdfService
{
    public function storeGenerated(
        Article $article,
        UploadedFile $pdfFile,
        User $user,
    ): ArticlePdf {
        $pdfId = (string) Str::uuid();
        $filePath = "articles/{$article->id}/pdfs/{$pdfId}.pdf";
        $disk = Storage::disk(config('article-pdf.disk'));

        if (! $disk->put($filePath, $pdfFile->get())) {
            throw new RuntimeException('Failed to store generated article PDF.');
        }

        $latestVersion = $article->versions()->max('version_number');

        return ArticlePdf::query()->create([
            'id' => $pdfId,
            'article_id' => $article->id,
            'owner_id' => $user->id,
            'file_path' => $filePath,
            'kind' => ArticlePdfKind::Generated,
            'parent_pdf_id' => null,
            'article_version_number' => is_numeric($latestVersion) ? (int) $latestVersion : null,
            'title' => __('articles.pdf.generated_title', [
                'title' => $article->title,
                'date' => now()->format('d.m.Y H:i'),
            ]),
        ]);
    }

    public function storeAnnotated(
        ArticlePdf $parentPdf,
        UploadedFile|string $pdfContent,
        User $user,
    ): ArticlePdf {
        $parentPdf->loadMissing('article');

        $pdfId = (string) Str::uuid();
        $filePath = "articles/{$parentPdf->article_id}/pdfs/{$pdfId}.pdf";
        $disk = Storage::disk(config('article-pdf.disk'));

        $bytes = is_string($pdfContent)
            ? $pdfContent
            : $pdfContent->get();

        if (! $disk->put($filePath, $bytes)) {
            throw new RuntimeException('Failed to store annotated article PDF.');
        }

        return ArticlePdf::query()->create([
            'id' => $pdfId,
            'article_id' => $parentPdf->article_id,
            'owner_id' => $user->id,
            'file_path' => $filePath,
            'kind' => ArticlePdfKind::Annotated,
            'parent_pdf_id' => $parentPdf->id,
            'article_version_number' => $parentPdf->article_version_number,
            'title' => __('articles.pdf.annotated_title', [
                'title' => $parentPdf->article->title,
                'date' => now()->format('d.m.Y H:i'),
            ]),
        ]);
    }

    public function delete(ArticlePdf $articlePdf): void
    {
        $disk = Storage::disk(config('article-pdf.disk'));

        if ($disk->exists($articlePdf->file_path)) {
            $disk->delete($articlePdf->file_path);
        }

        $articlePdf->delete();
    }
}
