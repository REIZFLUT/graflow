<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleCommentThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleCommentThread>
 */
class ArticleCommentThreadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'created_by_id' => User::factory(),
            'anchor_text' => fake()->sentence(),
            'resolved_at' => null,
            'resolved_by_id' => null,
        ];
    }

    public function resolved(?User $resolvedBy = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'resolved_at' => now(),
            'resolved_by_id' => $resolvedBy?->id ?? User::factory(),
        ]);
    }
}
