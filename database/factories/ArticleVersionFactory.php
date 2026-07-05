<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleVersion>
 */
class ArticleVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'version_number' => 1,
            'title' => fake()->sentence(),
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => fake()->paragraph()],
                        ],
                    ],
                ],
            ],
            'created_by_id' => User::factory(),
            'created_at' => now(),
        ];
    }
}
