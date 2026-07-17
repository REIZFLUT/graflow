<?php

namespace Database\Factories;

use App\Enums\ArticlePdfKind;
use App\Models\Article;
use App\Models\ArticlePdf;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ArticlePdf>
 */
class ArticlePdfFactory extends Factory
{
    protected $model = ArticlePdf::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $article = Article::factory()->create();

        return [
            'article_id' => $article->id,
            'owner_id' => $article->owner_id,
            'file_path' => "articles/{$article->id}/pdfs/".Str::uuid().'.pdf',
            'kind' => ArticlePdfKind::Generated,
            'parent_pdf_id' => null,
            'article_version_number' => 1,
            'title' => 'Test PDF',
        ];
    }

    public function annotated(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => ArticlePdfKind::Annotated,
        ]);
    }

    public function forArticle(Article $article): static
    {
        return $this->state(fn (array $attributes) => [
            'article_id' => $article->id,
            'owner_id' => $article->owner_id,
            'file_path' => "articles/{$article->id}/pdfs/".Str::uuid().'.pdf',
        ]);
    }

    public function forOwner(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $user->id,
        ]);
    }
}
