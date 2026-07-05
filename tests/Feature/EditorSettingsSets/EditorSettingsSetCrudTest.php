<?php

namespace Tests\Feature\EditorSettingsSets;

use App\Enums\PublicationEditorFont;
use App\Models\Article;
use App\Models\EditorSettingsSet;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorSettingsSetCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_editor_settings_sets_index(): void
    {
        $user = User::factory()->create();
        EditorSettingsSet::factory()->for($user, 'owner')->create([
            'name' => 'Magazin Serif',
        ]);

        $this->actingAs($user)
            ->get(route('editor-settings-sets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('editor-settings-sets/index')
                ->has('editorSettingsSets.data', 1)
                ->where('editorSettingsSets.data.0.name', 'Magazin Serif'));
    }

    public function test_user_can_create_editor_settings_set(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('editor-settings-sets.store'), [
                'name' => 'Roboto kompakt',
                'font' => PublicationEditorFont::Roboto->value,
                'has_marginal_column' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('editor_settings_sets', [
            'name' => 'Roboto kompakt',
            'font' => PublicationEditorFont::Roboto->value,
            'has_marginal_column' => false,
            'owner_id' => $user->id,
        ]);
    }

    public function test_user_can_update_editor_settings_set(): void
    {
        $user = User::factory()->create();
        $set = EditorSettingsSet::factory()->for($user, 'owner')->create([
            'name' => 'Alt',
        ]);

        $this->actingAs($user)
            ->put(route('editor-settings-sets.update', $set), [
                'name' => 'Neu',
                'font' => PublicationEditorFont::Roboto->value,
                'has_marginal_column' => true,
            ])
            ->assertRedirect(route('editor-settings-sets.edit', $set));

        $this->assertDatabaseHas('editor_settings_sets', [
            'id' => $set->id,
            'name' => 'Neu',
            'font' => PublicationEditorFont::Roboto->value,
            'has_marginal_column' => true,
        ]);
    }

    public function test_user_cannot_delete_editor_settings_set_in_use(): void
    {
        $user = User::factory()->create();
        $set = EditorSettingsSet::factory()->for($user, 'owner')->create();
        Publication::factory()
            ->for($user, 'owner')
            ->for($set, 'editorSettingsSet')
            ->create();

        $this->actingAs($user)
            ->delete(route('editor-settings-sets.destroy', $set))
            ->assertRedirect(route('editor-settings-sets.edit', $set));

        $this->assertDatabaseHas('editor_settings_sets', [
            'id' => $set->id,
        ]);
    }

    public function test_user_cannot_delete_editor_settings_set_assigned_to_articles(): void
    {
        $user = User::factory()->create();
        $set = EditorSettingsSet::factory()->for($user, 'owner')->create();
        Article::factory()->for($user, 'owner')->create([
            'editor_settings_set_id' => $set->id,
        ]);

        $this->actingAs($user)
            ->delete(route('editor-settings-sets.destroy', $set))
            ->assertRedirect(route('editor-settings-sets.edit', $set));

        $this->assertDatabaseHas('editor_settings_sets', [
            'id' => $set->id,
        ]);
    }

    public function test_user_can_delete_unused_editor_settings_set(): void
    {
        $user = User::factory()->create();
        $set = EditorSettingsSet::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->delete(route('editor-settings-sets.destroy', $set))
            ->assertRedirect(route('editor-settings-sets.index'));

        $this->assertDatabaseMissing('editor_settings_sets', [
            'id' => $set->id,
        ]);
    }
}
