<?php

namespace Tests\Feature\Articles;

use App\Enums\PublicationEditorFont;
use App\Models\Article;
use App\Models\ArticleVersion;
use App\Models\EditorSettingsSet;
use App\Models\Publication;
use App\Models\PublicationCategory;
use App\Models\PublicationChapter;
use App\Models\PublicationIssue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_manager_can_view_article_metadata_page(): void
    {
        $user = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($user, 'owner')->create([
            'name' => 'Energieberater Magazin',
        ]);
        $issue = PublicationIssue::factory()->for($publication)->create([
            'label' => '07-2026',
        ]);
        $laterChapter = PublicationChapter::factory()->for($issue)->create([
            'title' => 'Later',
            'position' => 2,
        ]);
        $firstChapter = PublicationChapter::factory()->for($issue)->create([
            'title' => 'First',
            'position' => 1,
        ]);
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
            'publication_issue_id' => $issue->id,
            'publication_chapter_id' => $laterChapter->id,
            'position' => 4,
        ]);

        $this->actingAs($user)
            ->get(route('articles.metadata.edit', $article))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('articles/metadata')
                ->where('article.id', $article->id)
                ->where('article.publication_issue_id', $issue->id)
                ->where('article.publication_chapter.id', $laterChapter->id)
                ->where('article.position', 4)
                ->has('publications', 1)
                ->where('publications.0.name', 'Energieberater Magazin')
                ->where('publications.0.issues.0.chapters.0.id', $firstChapter->id)
                ->where('publications.0.issues.0.chapters.1.id', $laterChapter->id));
    }

    public function test_product_manager_can_assign_article_to_issue(): void
    {
        $user = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create([
            'label' => '07-2026',
        ]);
        $chapter = PublicationChapter::factory()->for($issue)->create();
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
            'publication_issue_id' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => $issue->id,
                'publication_chapter_id' => $chapter->id,
                'position' => 2,
            ])
            ->assertRedirect(route('articles.metadata.edit', $article));

        $article->refresh();

        $this->assertSame($issue->id, $article->publication_issue_id);
        $this->assertSame($chapter->id, $article->publication_chapter_id);
        $this->assertSame(2, $article->position);
    }

    public function test_product_manager_can_clear_article_assignment(): void
    {
        $user = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $chapter = PublicationChapter::factory()->for($issue)->create();
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
            'publication_issue_id' => $issue->id,
            'publication_chapter_id' => $chapter->id,
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => null,
            ])
            ->assertRedirect();

        $article->refresh();

        $this->assertNull($article->publication_issue_id);
        $this->assertNull($article->publication_chapter_id);
    }

    public function test_product_manager_cannot_assign_foreign_issue(): void
    {
        $user = User::factory()->productManager()->create();
        $otherUser = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($otherUser, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => $issue->id,
            ])
            ->assertSessionHasErrors('publication_issue_id');
    }

    public function test_chapter_must_belong_to_selected_issue(): void
    {
        $user = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $otherIssue = PublicationIssue::factory()->for($publication)->create();
        $otherChapter = PublicationChapter::factory()->for($otherIssue)->create();
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => $issue->id,
                'publication_chapter_id' => $otherChapter->id,
                'position' => 1,
            ])
            ->assertSessionHasErrors('publication_chapter_id');
    }

    public function test_position_is_required_and_positive_when_article_is_assigned_to_issue(): void
    {
        $user = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => $issue->id,
            ])
            ->assertSessionHasErrors('position');

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => $issue->id,
                'position' => 0,
            ])
            ->assertSessionHasErrors('position');
    }

    public function test_user_sees_assigned_publication_from_another_owner_in_metadata_form(): void
    {
        $author = User::factory()->author()->create();
        $productManager = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($productManager, 'owner')->create([
            'name' => 'Energieberater Magazin',
        ]);
        $issue = PublicationIssue::factory()->for($publication)->create([
            'label' => '07-2026',
        ]);
        $article = Article::factory()->for($author, 'owner')->create([
            'publication_issue_id' => $issue->id,
        ]);

        $this->actingAs($author)
            ->get(route('articles.metadata.edit', $article))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('articles/metadata')
                ->where('article.publication_issue_id', $issue->id)
                ->has('publications', 1)
                ->where('publications.0.id', $publication->id)
                ->where('publications.0.name', 'Energieberater Magazin')
                ->where('publications.0.issues.0.id', $issue->id));
    }

    public function test_user_can_save_metadata_for_article_assigned_to_foreign_publication(): void
    {
        $author = User::factory()->author()->create();
        $productManager = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($productManager, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create([
            'label' => '07-2026',
        ]);
        $category = PublicationCategory::factory()->for($publication)->create([
            'name' => 'Technik',
        ]);
        $article = Article::factory()->for($author, 'owner')->create([
            'product_manager_id' => $productManager->id,
            'publication_issue_id' => $issue->id,
        ]);

        $this->actingAs($productManager)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => $issue->id,
                'position' => 1,
                'publication_category_ids' => [$category->id],
            ])
            ->assertRedirect(route('articles.metadata.edit', $article));

        $article->refresh();

        $this->assertSame($issue->id, $article->publication_issue_id);
        $this->assertEqualsCanonicalizing(
            [$category->id],
            $article->publicationCategories()->pluck('publication_categories.id')->all(),
        );
    }

    public function test_product_manager_metadata_update_does_not_create_article_version(): void
    {
        $user = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
        ]);

        ArticleVersion::query()->create([
            'article_id' => $article->id,
            'version_number' => 1,
            'title' => $article->title,
            'content' => $article->content,
            'created_by_id' => $user->id,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => $issue->id,
                'position' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(1, ArticleVersion::query()->where('article_id', $article->id)->count());
    }

    public function test_user_cannot_update_metadata_for_another_users_article(): void
    {
        $owner = User::factory()->productManager()->create();
        $otherUser = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $article = Article::factory()->create([
            'product_manager_id' => $owner->id,
        ]);

        $this->actingAs($otherUser)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => $issue->id,
            ])
            ->assertForbidden();
    }

    public function test_product_manager_can_assign_categories_to_article(): void
    {
        $user = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $categoryA = PublicationCategory::factory()->for($publication)->create([
            'name' => 'Technik',
        ]);
        $categoryB = PublicationCategory::factory()->for($publication)->create([
            'name' => 'Markt',
        ]);
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
            'publication_issue_id' => $issue->id,
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => $issue->id,
                'position' => 1,
                'publication_category_ids' => [$categoryA->id, $categoryB->id],
            ])
            ->assertRedirect(route('articles.metadata.edit', $article));

        $article->refresh();

        $this->assertEqualsCanonicalizing(
            [$categoryA->id, $categoryB->id],
            $article->publicationCategories()->pluck('publication_categories.id')->all(),
        );
    }

    public function test_product_manager_cannot_assign_categories_without_publication_issue(): void
    {
        $user = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $category = PublicationCategory::factory()->for($publication)->create();
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => null,
                'publication_category_ids' => [$category->id],
            ])
            ->assertSessionHasErrors('publication_category_ids');
    }

    public function test_product_manager_cannot_assign_foreign_category(): void
    {
        $user = User::factory()->productManager()->create();
        $otherUser = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $foreignPublication = Publication::factory()->for($otherUser, 'owner')->create();
        $foreignCategory = PublicationCategory::factory()->for($foreignPublication)->create();
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
            'publication_issue_id' => $issue->id,
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'publication_issue_id' => $issue->id,
                'publication_category_ids' => [$foreignCategory->id],
            ])
            ->assertSessionHasErrors('publication_category_ids.0');
    }

    public function test_product_manager_can_assign_editor_settings_set_override(): void
    {
        $user = User::factory()->productManager()->create();
        $set = EditorSettingsSet::factory()->for($user, 'owner')->create([
            'font' => PublicationEditorFont::Roboto,
            'has_marginal_column' => false,
        ]);
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
            'editor_settings_set_id' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'editor_settings_set_id' => $set->id,
            ])
            ->assertRedirect(route('articles.metadata.edit', $article));

        $article->refresh();

        $this->assertSame($set->id, $article->editor_settings_set_id);
    }

    public function test_product_manager_can_clear_editor_settings_set_override(): void
    {
        $user = User::factory()->productManager()->create();
        $set = EditorSettingsSet::factory()->for($user, 'owner')->create();
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
            'editor_settings_set_id' => $set->id,
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'editor_settings_set_id' => null,
            ])
            ->assertRedirect(route('articles.metadata.edit', $article));

        $article->refresh();

        $this->assertNull($article->editor_settings_set_id);
    }

    public function test_product_manager_cannot_assign_foreign_editor_settings_set(): void
    {
        $user = User::factory()->productManager()->create();
        $otherUser = User::factory()->productManager()->create();
        $foreignSet = EditorSettingsSet::factory()->for($otherUser, 'owner')->create();
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'editor_settings_set_id' => $foreignSet->id,
            ])
            ->assertSessionHasErrors('editor_settings_set_id');
    }

    public function test_editor_settings_set_override_does_not_create_article_version(): void
    {
        $user = User::factory()->productManager()->create();
        $set = EditorSettingsSet::factory()->for($user, 'owner')->create();
        $article = Article::factory()->create([
            'product_manager_id' => $user->id,
        ]);

        ArticleVersion::query()->create([
            'article_id' => $article->id,
            'version_number' => 1,
            'title' => $article->title,
            'content' => $article->content,
            'created_by_id' => $user->id,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('articles.metadata.update', $article), [
                'editor_settings_set_id' => $set->id,
            ])
            ->assertRedirect();

        $this->assertSame(1, ArticleVersion::query()->where('article_id', $article->id)->count());
    }

    public function test_author_cannot_assign_editor_settings_set_override(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $set = EditorSettingsSet::factory()->for($productManager, 'owner')->create();
        $article = Article::factory()->for($author, 'owner')->create([
            'product_manager_id' => $productManager->id,
            'editor_settings_set_id' => null,
        ]);

        $this->actingAs($author)
            ->patch(route('articles.metadata.update', $article), [
                'editor_settings_set_id' => $set->id,
            ])
            ->assertForbidden();

        $article->refresh();

        $this->assertNull($article->editor_settings_set_id);
    }
}
