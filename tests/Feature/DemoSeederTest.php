<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\PublicationEditorFont;
use App\Models\Article;
use App\Models\ArticleMedia;
use App\Models\Publication;
use App\Models\PublicationChapter;
use App\Models\PublicationIssue;
use App\Models\User;
use App\Services\ArticleMediaService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Support\DemoArticleContentBuilder;
use Database\Seeders\Support\DemoMediaImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ini_set('memory_limit', '512M');
    }

    public function test_demo_seeder_creates_expected_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(9, User::query()->count());
        $this->assertSame(2, Publication::query()->count());
        $this->assertSame(4, PublicationIssue::query()->count());
        $this->assertSame(15, Article::query()->count());
        $this->assertDatabaseHas('users', [
            'email' => 'lector@example.com',
            'role' => 'lector',
        ]);

        $book = Publication::query()->where('name', 'GEG Baupraxis Handbuch')->firstOrFail();
        $magazine = Publication::query()->where('name', 'GEG Baupraxis')->firstOrFail();

        $bookSettings = $book->editorSettingsSet;
        $magazineSettings = $magazine->editorSettingsSet;

        $this->assertNotNull($bookSettings);
        $this->assertNotNull($magazineSettings);
        $this->assertSame(PublicationEditorFont::Spectral, $bookSettings->font);
        $this->assertTrue($bookSettings->has_marginal_column);
        $this->assertSame(PublicationEditorFont::Roboto, $magazineSettings->font);
        $this->assertFalse($magazineSettings->has_marginal_column);

        $this->assertSame(
            'productmanager@example.com',
            $book->owner->email,
        );
    }

    public function test_demo_seeder_demonstrates_chapter_planning_and_workflow_states(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(8, PublicationChapter::query()->count());

        foreach (PublicationIssue::query()->with('chapters.articles')->get() as $issue) {
            $this->assertCount(2, $issue->chapters);
            $this->assertSame([1, 2], $issue->chapters->pluck('position')->all());

            foreach ($issue->chapters as $chapter) {
                $this->assertNotEmpty($chapter->articles);
                $this->assertSame(
                    range(1, $chapter->articles->count()),
                    $chapter->articles->pluck('position')->all(),
                );
            }
        }

        $this->assertGreaterThanOrEqual(
            6,
            PublicationChapter::query()
                ->withCount('articles')
                ->get()
                ->where('articles_count', '>=', 2)
                ->count(),
        );

        foreach (ArticleStatus::cases() as $status) {
            $this->assertTrue(
                Article::query()->where('status', $status)->exists(),
                "No demo article represents the [{$status->value}] workflow state.",
            );
        }

        $articles = Article::query()
            ->with(['author', 'currentAssignee', 'participants', 'workflowEvents'])
            ->get();

        foreach ($articles as $article) {
            $this->assertNotNull($article->product_manager_id);
            $this->assertNotNull($article->author_id);
            $this->assertNotNull($article->publication_chapter_id);
            $this->assertGreaterThan(0, $article->position);
            $this->assertNotNull($article->submission_deadline);
            $this->assertGreaterThan(0, $article->target_character_count);

            if ($article->status === ArticleStatus::Published) {
                $this->assertNotNull($article->published_at);
            } else {
                $this->assertNull($article->published_at);
            }

            if (in_array($article->status, [ArticleStatus::Authoring, ArticleStatus::Revision], true)) {
                $this->assertSame($article->author_id, $article->current_assignee_id);
            } elseif ($article->status === ArticleStatus::ProductManagerCorrection) {
                $this->assertSame($article->product_manager_id, $article->current_assignee_id);
            } elseif ($article->status === ArticleStatus::EditorialWork) {
                $this->assertNotNull($article->currentAssignee);
                $this->assertContains($article->currentAssignee->role->value, ['editor', 'lector']);
            } else {
                $this->assertNull($article->current_assignee_id);
            }

            $participantIds = $article->participants->pluck('user_id');
            $this->assertContains($article->product_manager_id, $participantIds);
            $this->assertContains($article->author_id, $participantIds);

            foreach ($article->workflowEvents as $event) {
                $this->assertNotSame('', $event->reason);

                if ($event->assignee_id !== null) {
                    $this->assertContains($event->assignee_id, $participantIds);
                }
            }

            $this->assertNotEmpty($article->workflowEvents);
            $this->assertSame(
                $article->status,
                $article->workflowEvents->sortBy('created_at')->last()->to_status,
            );
        }

        $workflowEventCount = Article::query()->withCount('workflowEvents')->get()->sum('workflow_events_count');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(9, User::query()->count());
        $this->assertSame(15, Article::query()->count());
        $this->assertSame(8, PublicationChapter::query()->count());
        $this->assertSame(23, ArticleMedia::query()->count());
        $this->assertSame(
            $workflowEventCount,
            Article::query()->withCount('workflowEvents')->get()->sum('workflow_events_count'),
        );
    }

    public function test_demo_seeder_creates_correct_image_counts_per_publication_type(): void
    {
        $this->seed(DatabaseSeeder::class);

        $contentBuilder = new DemoArticleContentBuilder(
            new DemoMediaImporter(app(ArticleMediaService::class)),
        );

        $book = Publication::query()->where('name', 'GEG Baupraxis Handbuch')->firstOrFail();
        $magazine = Publication::query()->where('name', 'GEG Baupraxis')->firstOrFail();

        $bookIssueIds = $book->issues()->pluck('id');
        $magazineIssueIds = $magazine->issues()->pluck('id');

        $bookArticles = Article::query()->whereIn('publication_issue_id', $bookIssueIds)->get();
        $magazineArticles = Article::query()->whereIn('publication_issue_id', $magazineIssueIds)->get();

        $this->assertCount(7, $bookArticles);
        $this->assertCount(8, $magazineArticles);

        foreach ($bookArticles as $article) {
            $content = $article->content ?? [];
            $this->assertSame(1, $contentBuilder->countArticleImages($content));
            $this->assertSame(1, ArticleMedia::query()->where('article_id', $article->id)->count());
            $this->assertTrue($contentBuilder->hasMarginalNotes($content));
        }

        foreach ($magazineArticles as $article) {
            $content = $article->content ?? [];
            $this->assertSame(2, $contentBuilder->countArticleImages($content));
            $this->assertSame(2, ArticleMedia::query()->where('article_id', $article->id)->count());
            $this->assertFalse($contentBuilder->hasMarginalNotes($content));
        }
    }

    public function test_demo_seeder_articles_have_substantial_word_count(): void
    {
        $this->seed(DatabaseSeeder::class);

        $contentBuilder = new DemoArticleContentBuilder(
            new DemoMediaImporter(app(ArticleMediaService::class)),
        );

        $article = Article::query()->orderBy('id')->firstOrFail();
        $content = $article->content ?? [];

        $this->assertGreaterThanOrEqual(900, $contentBuilder->countWords($content));
    }

    public function test_demo_seeder_creates_five_authors_with_three_articles_each(): void
    {
        $this->seed(DatabaseSeeder::class);

        $authors = User::query()->where('role', 'author')->orderBy('email')->get();

        $this->assertCount(5, $authors);

        foreach ($authors as $author) {
            $this->assertSame(3, Article::query()->where('owner_id', $author->id)->count());
        }
    }

    public function test_demo_seeder_articles_include_all_editor_features(): void
    {
        $this->seed(DatabaseSeeder::class);

        $contentBuilder = new DemoArticleContentBuilder(
            new DemoMediaImporter(app(ArticleMediaService::class)),
        );

        $book = Publication::query()->where('name', 'GEG Baupraxis Handbuch')->firstOrFail();
        $magazine = Publication::query()->where('name', 'GEG Baupraxis')->firstOrFail();

        $bookIssueIds = $book->issues()->pluck('id');
        $magazineIssueIds = $magazine->issues()->pluck('id');

        foreach (Article::query()->whereIn('publication_issue_id', $bookIssueIds)->get() as $article) {
            $content = $article->content ?? [];
            $this->assertTrue(
                $contentBuilder->hasAllEditorFeatures($content, true),
                "Book article [{$article->title}] is missing editor features.",
            );
            $this->assertSame(1, $contentBuilder->countArticleImages($content));
        }

        foreach (Article::query()->whereIn('publication_issue_id', $magazineIssueIds)->get() as $article) {
            $content = $article->content ?? [];
            $this->assertTrue(
                $contentBuilder->hasAllEditorFeatures($content, false),
                "Magazine article [{$article->title}] is missing editor features.",
            );
            $this->assertSame(2, $contentBuilder->countArticleImages($content));
        }
    }
}
