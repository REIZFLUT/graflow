<?php

namespace App\Support;

use App\Enums\PublicationEditorFont;
use App\Models\Article;

class ArticleEditorSettingsResolver
{
    /**
     * @return array{font: string, has_marginal_column: bool}
     */
    public function resolve(Article $article): array
    {
        $publication = $article->publicationIssue?->publication;
        $editorSettingsSet = $article->editorSettingsSet
            ?? $publication?->editorSettingsSet;

        if ($editorSettingsSet === null) {
            return $this->defaults();
        }

        return [
            'font' => $editorSettingsSet->font->value,
            'has_marginal_column' => $editorSettingsSet->has_marginal_column,
        ];
    }

    /**
     * @return array{font: string, has_marginal_column: bool}
     */
    public function defaults(): array
    {
        return [
            'font' => PublicationEditorFont::Spectral->value,
            'has_marginal_column' => true,
        ];
    }
}
