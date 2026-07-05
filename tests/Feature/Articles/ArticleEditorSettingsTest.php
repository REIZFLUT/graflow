<?php

namespace Tests\Feature\Articles;

use App\Enums\PublicationEditorFont;
use App\Models\Article;
use App\Models\EditorSettingsSet;
use App\Models\Publication;
use App\Models\PublicationIssue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleEditorSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_edit_uses_publication_editor_settings_set(): void
    {
        $user = User::factory()->create();
        $set = EditorSettingsSet::factory()->for($user, 'owner')->create([
            'font' => PublicationEditorFont::Roboto,
            'has_marginal_column' => false,
        ]);
        $publication = Publication::factory()
            ->for($user, 'owner')
            ->for($set, 'editorSettingsSet')
            ->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $article = Article::factory()->for($user, 'owner')->create([
            'publication_issue_id' => $issue->id,
        ]);

        $this->actingAs($user)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('articles/edit')
                ->where('editorSettings.font', PublicationEditorFont::Roboto->value)
                ->where('editorSettings.has_marginal_column', false));
    }

    public function test_article_edit_without_publication_uses_default_editor_settings(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create([
            'publication_issue_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('articles/edit')
                ->where('editorSettings.font', PublicationEditorFont::Spectral->value)
                ->where('editorSettings.has_marginal_column', true));
    }

    public function test_article_create_uses_default_editor_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('articles.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('articles/create')
                ->where('editorSettings.font', PublicationEditorFont::Spectral->value)
                ->where('editorSettings.has_marginal_column', true));
    }

    public function test_article_edit_uses_article_override_over_publication_set(): void
    {
        $user = User::factory()->create();
        $publicationSet = EditorSettingsSet::factory()->for($user, 'owner')->create([
            'font' => PublicationEditorFont::Spectral,
            'has_marginal_column' => true,
        ]);
        $overrideSet = EditorSettingsSet::factory()->for($user, 'owner')->create([
            'font' => PublicationEditorFont::Roboto,
            'has_marginal_column' => false,
        ]);
        $publication = Publication::factory()
            ->for($user, 'owner')
            ->for($publicationSet, 'editorSettingsSet')
            ->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $article = Article::factory()->for($user, 'owner')->create([
            'publication_issue_id' => $issue->id,
            'editor_settings_set_id' => $overrideSet->id,
        ]);

        $this->actingAs($user)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('articles/edit')
                ->where('editorSettings.font', PublicationEditorFont::Roboto->value)
                ->where('editorSettings.has_marginal_column', false));
    }

    public function test_article_edit_falls_back_to_publication_set_when_override_removed(): void
    {
        $user = User::factory()->create();
        $publicationSet = EditorSettingsSet::factory()->for($user, 'owner')->create([
            'font' => PublicationEditorFont::Roboto,
            'has_marginal_column' => false,
        ]);
        $overrideSet = EditorSettingsSet::factory()->for($user, 'owner')->create([
            'font' => PublicationEditorFont::Spectral,
            'has_marginal_column' => true,
        ]);
        $publication = Publication::factory()
            ->for($user, 'owner')
            ->for($publicationSet, 'editorSettingsSet')
            ->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $article = Article::factory()->for($user, 'owner')->create([
            'publication_issue_id' => $issue->id,
            'editor_settings_set_id' => $overrideSet->id,
        ]);

        $article->update(['editor_settings_set_id' => null]);

        $this->actingAs($user)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('articles/edit')
                ->where('editorSettings.font', PublicationEditorFont::Roboto->value)
                ->where('editorSettings.has_marginal_column', false));
    }
}
