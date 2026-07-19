<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleWorkflowEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleWorkflowEvent>
 */
class ArticleWorkflowEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'from_status' => ArticleStatus::Planned,
            'to_status' => ArticleStatus::Authoring,
            'actor_id' => User::factory()->productManager(),
            'assignee_id' => User::factory()->author(),
            'reason' => null,
            'created_at' => now(),
        ];
    }

    public function transition(?ArticleStatus $fromStatus, ?ArticleStatus $toStatus): static
    {
        return $this->state(fn (array $attributes) => [
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
        ]);
    }
}
