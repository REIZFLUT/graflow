<?php

namespace Database\Factories;

use App\Models\EditorSettingsSet;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    protected $model = Publication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'owner_id' => User::factory(),
        ];
    }

    /**
     * @return static
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Publication $publication): void {
            if ($publication->editor_settings_set_id !== null) {
                return;
            }

            $set = EditorSettingsSet::factory()
                ->for($publication->owner, 'owner')
                ->create();

            $publication->update([
                'editor_settings_set_id' => $set->id,
            ]);
        });
    }
}
