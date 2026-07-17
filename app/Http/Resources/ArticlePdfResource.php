<?php

namespace App\Http\Resources;

use App\Models\ArticlePdf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ArticlePdf */
class ArticlePdfResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ArticlePdf $pdf */
        $pdf = $this->resource;

        return [
            'id' => $pdf->id,
            'article_id' => $pdf->article_id,
            'kind' => $pdf->kind->value,
            'parent_pdf_id' => $pdf->parent_pdf_id,
            'article_version_number' => $pdf->article_version_number,
            'title' => $pdf->title,
            'created_at' => $pdf->created_at?->toISOString(),
            'updated_at' => $pdf->updated_at?->toISOString(),
            'file_url' => route('articles.pdfs.file', [
                'article' => $pdf->article_id,
                'pdf' => $pdf->id,
            ]),
            'view_url' => route('articles.pdfs.show', [
                'article' => $pdf->article_id,
                'pdf' => $pdf->id,
            ]),
        ];
    }
}
