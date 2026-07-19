<?php

namespace Database\Factories;

use App\Models\PublicationChapter;
use App\Models\PublicationIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PublicationChapter>
 */
class PublicationChapterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'publication_issue_id' => PublicationIssue::factory(),
            'title' => fake()->words(3, true),
            'position' => fake()->unique()->numberBetween(1, 10000),
        ];
    }
}
