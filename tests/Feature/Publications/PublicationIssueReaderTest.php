<?php

namespace Tests\Feature\Publications;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Publication;
use App\Models\PublicationIssue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationIssueReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_issue_reader(): void
    {
        $owner = User::factory()->productManager()->create();
        $author = User::factory()->author()->create(['name' => 'Ada Author']);
        $publication = Publication::factory()->for($owner, 'owner')->create([
            'name' => 'Energie Magazin',
        ]);
        $issue = PublicationIssue::factory()->for($publication)->create([
            'label' => '07-2026',
        ]);
        $chapter = $issue->chapters()->create([
            'title' => 'News',
            'position' => 1,
        ]);
        $article = Article::factory()->for($issue, 'publicationIssue')->create([
            'title' => 'Leitartikel',
            'status' => ArticleStatus::Authoring,
            'author_id' => $author->id,
            'publication_chapter_id' => $chapter->id,
            'position' => 1,
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Hello world'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('publications.issues.reader.show', [$publication, $issue]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('publication-issues/reader')
                ->where('publication.name', 'Energie Magazin')
                ->where('issue.label', '07-2026')
                ->has('chapters', 1)
                ->where('chapters.0.title', 'News')
                ->has('articles', 1)
                ->where('articles.0.id', $article->id)
                ->where('articles.0.title', 'Leitartikel')
                ->where('articles.0.status', ArticleStatus::Authoring->value)
                ->where('articles.0.author.name', 'Ada Author')
                ->where('articles.0.publication_chapter_id', $chapter->id)
                ->has('articles.0.content')
                ->has('articles.0.editor_settings.font')
                ->has('articles.0.editor_settings.has_marginal_column')
                ->has('articles.0.media'));
    }

    public function test_contributor_can_view_issue_reader(): void
    {
        $owner = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();

        Article::factory()->for($author, 'owner')->create([
            'publication_issue_id' => $issue->id,
        ]);

        $this->actingAs($author)
            ->get(route('publications.issues.reader.show', [$publication, $issue]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('publication-issues/reader'));
    }

    public function test_admin_can_view_issue_reader(): void
    {
        $owner = User::factory()->productManager()->create();
        $admin = User::factory()->admin()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();

        $this->actingAs($admin)
            ->get(route('publications.issues.reader.show', [$publication, $issue]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('publication-issues/reader'));
    }

    public function test_unrelated_user_cannot_view_issue_reader(): void
    {
        $owner = User::factory()->productManager()->create();
        $stranger = User::factory()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();

        $this->actingAs($stranger)
            ->get(route('publications.issues.reader.show', [$publication, $issue]))
            ->assertForbidden();
    }

    public function test_issue_from_another_publication_returns_not_found(): void
    {
        $owner = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create();
        $otherPublication = Publication::factory()->for($owner, 'owner')->create();
        $otherIssue = PublicationIssue::factory()->for($otherPublication)->create();

        $this->actingAs($owner)
            ->get(route('publications.issues.reader.show', [$publication, $otherIssue]))
            ->assertNotFound();
    }

    public function test_reader_orders_articles_by_chapter_then_position(): void
    {
        $owner = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $laterChapter = $issue->chapters()->create([
            'title' => 'Later',
            'position' => 2,
        ]);
        $firstChapter = $issue->chapters()->create([
            'title' => 'First',
            'position' => 1,
        ]);
        $laterArticle = Article::factory()->for($issue, 'publicationIssue')->create([
            'publication_chapter_id' => $laterChapter->id,
            'position' => 1,
        ]);
        $secondArticle = Article::factory()->for($issue, 'publicationIssue')->create([
            'publication_chapter_id' => $firstChapter->id,
            'position' => 2,
        ]);
        $firstArticle = Article::factory()->for($issue, 'publicationIssue')->create([
            'publication_chapter_id' => $firstChapter->id,
            'position' => 1,
        ]);

        $this->actingAs($owner)
            ->get(route('publications.issues.reader.show', [$publication, $issue]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('articles.0.id', $firstArticle->id)
                ->where('articles.1.id', $secondArticle->id)
                ->where('articles.2.id', $laterArticle->id));
    }

    public function test_publications_index_includes_issues_for_reader_modal(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create([
            'name' => 'Magazin',
        ]);
        PublicationIssue::factory()->for($publication)->create(['label' => '01-2026']);
        PublicationIssue::factory()->for($publication)->create(['label' => '02-2026']);

        $this->actingAs($user)
            ->get(route('publications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('publications/index')
                ->has('publications.data', 1)
                ->has('publications.data.0.issues', 2)
                ->where('publications.data.0.issues.0.label', '02-2026')
                ->where('publications.data.0.issues.1.label', '01-2026'));
    }
}
