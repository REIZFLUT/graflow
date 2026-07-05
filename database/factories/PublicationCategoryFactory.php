<?php

namespace Database\Factories;

use App\Models\Publication;
use App\Models\PublicationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PublicationCategory>
 */
class PublicationCategoryFactory extends Factory
{
    protected $model = PublicationCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'publication_id' => Publication::factory(),
            'name' => fake()->unique()->word(),
        ];
    }
}
