<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
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
            'owner_id' => User::factory()->author(),
            'product_manager_id' => User::factory()->productManager(),
            'author_id' => fn (array $attributes): int => $attributes['owner_id'],
            'current_assignee_id' => fn (array $attributes): int => $attributes['author_id'],
            'status' => ArticleStatus::Authoring,
            'position' => fake()->numberBetween(1, 100),
        ];
    }

    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Planned,
            'current_assignee_id' => null,
        ]);
    }

    public function authoring(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Authoring,
        ]);
    }

    public function manuscriptSubmitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::ManuscriptSubmitted,
            'current_assignee_id' => null,
        ]);
    }

    public function productManagerCorrection(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::ProductManagerCorrection,
            'current_assignee_id' => $attributes['product_manager_id'] ?? User::factory()->productManager(),
        ]);
    }

    public function revisionRequested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::RevisionRequested,
            'current_assignee_id' => null,
        ]);
    }

    public function revision(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Revision,
        ]);
    }

    public function editorialWork(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::EditorialWork,
            'current_assignee_id' => User::factory()->editor(),
        ]);
    }

    public function readyForPublication(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::ReadyForPublication,
            'current_assignee_id' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Published,
            'current_assignee_id' => null,
            'published_at' => now(),
        ]);
    }
}
