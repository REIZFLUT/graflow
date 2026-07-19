<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleParticipant>
 */
class ArticleParticipantFactory extends Factory
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
            'user_id' => User::factory(),
            'process_role' => fake()->randomElement([
                'product_manager',
                'author',
                'editor',
                'lector',
            ]),
        ];
    }
}
