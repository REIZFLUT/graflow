<?php

namespace Tests\Feature\Publications;

use App\Models\Article;
use App\Models\EditorSettingsSet;
use App\Models\Publication;
use App\Models\PublicationIssue;
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
        $user = User::factory()->editor()->create();
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
        $user = User::factory()->editor()->create();
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
        $user = User::factory()->editor()->create();
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

    public function test_contributor_sees_publication_they_write_for(): void
    {
        $owner = User::factory()->create(['name' => 'Product Manager']);
        $author = User::factory()->create();
        $unrelatedOwner = User::factory()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create(['name' => 'Shared Mag']);
        $unrelated = Publication::factory()->for($unrelatedOwner, 'owner')->create(['name' => 'Unrelated']);
        $issue = PublicationIssue::factory()->for($publication)->create();

        Article::factory()->for($author, 'owner')->create([
            'publication_issue_id' => $issue->id,
        ]);

        $this->actingAs($author)
            ->get(route('publications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('publications.data', 1)
                ->where('publications.data.0.name', 'Shared Mag')
                ->where('publications.data.0.can_edit', false)
                ->where('publications.data.0.owner.name', 'Product Manager')
                ->where('publications.data.0.owner_id', $owner->id));

        $this->assertDatabaseHas('publications', [
            'id' => $unrelated->id,
            'name' => 'Unrelated',
        ]);
    }

    public function test_contributor_can_view_publication_readonly(): void
    {
        $owner = User::factory()->create(['name' => 'Product Manager']);
        $author = User::factory()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create(['name' => 'Shared Mag']);
        $issue = PublicationIssue::factory()->for($publication)->create(['label' => '07-2026']);

        Article::factory()->for($author, 'owner')->create([
            'publication_issue_id' => $issue->id,
        ]);

        $this->actingAs($author)
            ->get(route('publications.edit', $publication))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('publications/edit')
                ->where('publication.name', 'Shared Mag')
                ->where('publication.can_edit', false)
                ->where('publication.owner.name', 'Product Manager')
                ->has('editorSettingsSets', 0));
    }

    public function test_contributor_cannot_update_or_delete_publication(): void
    {
        $owner = User::factory()->create();
        $author = User::factory()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create([
            'name' => 'Shared Mag',
        ]);
        $issue = PublicationIssue::factory()->for($publication)->create();

        Article::factory()->for($author, 'owner')->create([
            'publication_issue_id' => $issue->id,
        ]);

        $this->actingAs($author)
            ->put(route('publications.update', $publication), [
                'name' => 'Hacked Name',
                'editor_settings_set_id' => $publication->editor_settings_set_id,
            ])
            ->assertForbidden();

        $this->actingAs($author)
            ->delete(route('publications.destroy', $publication))
            ->assertForbidden();

        $this->assertDatabaseHas('publications', [
            'id' => $publication->id,
            'name' => 'Shared Mag',
        ]);
    }

    public function test_publication_name_must_be_unique_per_owner(): void
    {
        $user = User::factory()->editor()->create();
        $set = EditorSettingsSet::factory()->for($user, 'owner')->create();
        Publication::factory()->for($user, 'owner')->create(['name' => 'Energieberater Magazin']);

        $this->actingAs($user)
            ->post(route('publications.store'), [
                'name' => 'Energieberater Magazin',
                'editor_settings_set_id' => $set->id,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_author_can_create_publication_without_editor_settings_set(): void
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author)
            ->post(route('publications.store'), [
                'name' => 'Autoren Publikation',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('publications', [
            'name' => 'Autoren Publikation',
            'owner_id' => $author->id,
            'editor_settings_set_id' => null,
        ]);
    }

    public function test_author_cannot_assign_editor_settings_set_when_creating_publication(): void
    {
        $editor = User::factory()->editor()->create();
        $author = User::factory()->author()->create();
        $set = EditorSettingsSet::factory()->for($editor, 'owner')->create();

        $this->actingAs($author)
            ->post(route('publications.store'), [
                'name' => 'Autoren Publikation',
                'editor_settings_set_id' => $set->id,
            ])
            ->assertSessionHasErrors('editor_settings_set_id');
    }
}
