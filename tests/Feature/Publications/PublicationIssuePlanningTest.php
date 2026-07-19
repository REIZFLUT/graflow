<?php

namespace Tests\Feature\Publications;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Publication;
use App\Models\PublicationIssue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationIssuePlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_manager_can_create_chapter_and_plan_article(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $publication = Publication::factory()->for($productManager, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();

        $this->actingAs($productManager)
            ->post(route('publications.issues.chapters.store', [$publication, $issue]), [
                'title' => 'News',
                'position' => 1,
            ])
            ->assertRedirect();

        $chapter = $issue->chapters()->firstOrFail();

        $this->actingAs($productManager)
            ->post(route('publications.issues.planning.articles.store', [$publication, $issue]), [
                'title' => 'Planned article',
                'author_id' => $author->id,
                'publication_chapter_id' => $chapter->id,
                'position' => 3,
                'submission_deadline' => now()->addWeek()->toDateString(),
                'target_character_count' => 5000,
            ])
            ->assertRedirect(route('publications.issues.planning.show', [$publication, $issue]));

        $this->assertDatabaseHas('articles', [
            'title' => 'Planned article',
            'status' => ArticleStatus::Planned->value,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
            'publication_issue_id' => $issue->id,
            'publication_chapter_id' => $chapter->id,
            'position' => 3,
            'target_character_count' => 5000,
        ]);
    }

    public function test_author_cannot_plan_article(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $publication = Publication::factory()->for($productManager, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();

        $this->actingAs($author)
            ->post(route('publications.issues.planning.articles.store', [$publication, $issue]), [
                'title' => 'Unauthorized article',
                'author_id' => $author->id,
                'target_character_count' => 1000,
            ])
            ->assertForbidden();
    }

    public function test_article_cannot_be_planned_in_a_chapter_from_another_issue(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $publication = Publication::factory()->for($productManager, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $otherIssue = PublicationIssue::factory()->for($publication)->create();
        $otherChapter = $otherIssue->chapters()->create([
            'title' => 'Falsches Kapitel',
            'position' => 1,
        ]);

        $this->actingAs($productManager)
            ->post(route('publications.issues.planning.articles.store', [$publication, $issue]), [
                'title' => 'Artikel',
                'author_id' => $author->id,
                'publication_chapter_id' => $otherChapter->id,
                'position' => 1,
                'submission_deadline' => now()->addWeek()->toDateString(),
                'target_character_count' => 1000,
            ])
            ->assertSessionHasErrors('publication_chapter_id');

        $this->assertDatabaseMissing('articles', ['title' => 'Artikel']);
    }

    public function test_planned_article_position_is_required_and_positive(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $publication = Publication::factory()->for($productManager, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $articleData = [
            'title' => 'Artikel',
            'author_id' => $author->id,
            'submission_deadline' => now()->addWeek()->toDateString(),
            'target_character_count' => 1000,
        ];

        $this->actingAs($productManager)
            ->post(
                route('publications.issues.planning.articles.store', [$publication, $issue]),
                $articleData,
            )
            ->assertSessionHasErrors('position');

        $this->actingAs($productManager)
            ->post(
                route('publications.issues.planning.articles.store', [$publication, $issue]),
                [...$articleData, 'position' => 0],
            )
            ->assertSessionHasErrors('position');
    }

    public function test_planning_page_orders_articles_by_chapter_position_then_article_position(): void
    {
        $productManager = User::factory()->productManager()->create();
        $publication = Publication::factory()->for($productManager, 'owner')->create();
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

        $this->actingAs($productManager)
            ->get(route('publications.issues.planning.show', [$publication, $issue]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('issue.articles.0.id', $firstArticle->id)
                ->where('issue.articles.1.id', $secondArticle->id)
                ->where('issue.articles.2.id', $laterArticle->id));
    }

    public function test_issue_and_chapter_article_relationships_order_by_position_then_id(): void
    {
        $publication = Publication::factory()->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $chapter = $issue->chapters()->create([
            'title' => 'Chapter',
            'position' => 1,
        ]);
        $lastArticle = Article::factory()->for($issue, 'publicationIssue')->create([
            'publication_chapter_id' => $chapter->id,
            'position' => 2,
        ]);
        $firstArticle = Article::factory()->for($issue, 'publicationIssue')->create([
            'publication_chapter_id' => $chapter->id,
            'position' => 1,
        ]);

        $this->assertSame(
            [$firstArticle->id, $lastArticle->id],
            $issue->articles()->pluck('id')->all(),
        );
        $this->assertSame(
            [$firstArticle->id, $lastArticle->id],
            $chapter->articles()->pluck('id')->all(),
        );
    }
}
