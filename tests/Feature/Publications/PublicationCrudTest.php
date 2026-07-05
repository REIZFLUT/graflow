<?php

namespace Tests\Feature\Publications;

use App\Models\EditorSettingsSet;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_publications_index(): void
    {
        $user = User::factory()->create();
        Publication::factory()->for($user, 'owner')->create(['name' => 'Energieberater Magazin']);

        $this->actingAs($user)
            ->get(route('publications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('publications/index')
                ->has('publications.data', 1)
                ->where('publications.data.0.name', 'Energieberater Magazin'));
    }

    public function test_user_only_sees_own_publications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Publication::factory()->for($user, 'owner')->create(['name' => 'Mine']);
        Publication::factory()->for($otherUser, 'owner')->create(['name' => 'Theirs']);

        $this->actingAs($user)
            ->get(route('publications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('publications.data', 1)
                ->where('publications.data.0.name', 'Mine'));
    }

    public function test_user_can_create_publication(): void
    {
        $user = User::factory()->create();
        $set = EditorSettingsSet::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->post(route('publications.store'), [
                'name' => 'Energieberater Magazin',
                'editor_settings_set_id' => $set->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('publications', [
            'name' => 'Energieberater Magazin',
            'owner_id' => $user->id,
            'editor_settings_set_id' => $set->id,
        ]);
    }

    public function test_user_can_update_publication(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create([
            'name' => 'Old Name',
        ]);

        $this->actingAs($user)
            ->put(route('publications.update', $publication), [
                'name' => 'New Name',
                'editor_settings_set_id' => $publication->editor_settings_set_id,
            ])
            ->assertRedirect(route('publications.edit', $publication));

        $this->assertDatabaseHas('publications', [
            'id' => $publication->id,
            'name' => 'New Name',
        ]);
    }

    public function test_user_can_assign_different_editor_settings_set_to_publication(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create([
            'name' => 'Magazin',
        ]);
        $newSet = EditorSettingsSet::factory()->for($user, 'owner')->create([
            'name' => 'Roboto kompakt',
        ]);

        $this->actingAs($user)
            ->put(route('publications.update', $publication), [
                'name' => 'Magazin',
                'editor_settings_set_id' => $newSet->id,
            ])
            ->assertRedirect(route('publications.edit', $publication));

        $this->assertDatabaseHas('publications', [
            'id' => $publication->id,
            'editor_settings_set_id' => $newSet->id,
        ]);
    }

    public function test_user_can_delete_publication(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->delete(route('publications.destroy', $publication))
            ->assertRedirect(route('publications.index'));

        $this->assertDatabaseMissing('publications', [
            'id' => $publication->id,
        ]);
    }

    public function test_user_cannot_edit_another_users_publication(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create();

        $this->actingAs($otherUser)
            ->get(route('publications.edit', $publication))
            ->assertForbidden();
    }

    public function test_publication_name_must_be_unique_per_owner(): void
    {
        $user = User::factory()->create();
        $set = EditorSettingsSet::factory()->for($user, 'owner')->create();
        Publication::factory()->for($user, 'owner')->create(['name' => 'Energieberater Magazin']);

        $this->actingAs($user)
            ->post(route('publications.store'), [
                'name' => 'Energieberater Magazin',
                'editor_settings_set_id' => $set->id,
            ])
            ->assertSessionHasErrors('name');
    }
}
