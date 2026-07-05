<?php

namespace Database\Factories;

use App\Models\Publication;
use App\Models\PublicationIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PublicationIssue>
 */
class PublicationIssueFactory extends Factory
{
    protected $model = PublicationIssue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'publication_id' => Publication::factory(),
            'label' => fake()->numerify('##-####'),
        ];
    }
}
