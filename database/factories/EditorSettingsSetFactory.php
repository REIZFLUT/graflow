<?php

namespace Database\Factories;

use App\Enums\PublicationEditorFont;
use App\Models\EditorSettingsSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditorSettingsSet>
 */
class EditorSettingsSetFactory extends Factory
{
    protected $model = EditorSettingsSet::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'font' => PublicationEditorFont::Spectral,
            'has_marginal_column' => true,
            'owner_id' => User::factory(),
        ];
    }
}
