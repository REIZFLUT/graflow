<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\PublicationEditorFont;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\ArticleCommentThread;
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
        $this->assertSame(2, $this->demoPublications()->count());
        $this->assertSame(4, PublicationIssue::query()->whereIn('publication_id', $this->demoPublicationIds())->count());
        $this->assertSame(15, $this->demoArticles()->count());
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

        $this->assertSame(8, $this->demoChapters()->count());

        foreach (PublicationIssue::query()->whereIn('publication_id', $this->demoPublicationIds())->with('chapters.articles')->get() as $issue) {
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
            $this->demoChapters()
                ->withCount('articles')
                ->get()
                ->where('articles_count', '>=', 2)
                ->count(),
        );

        foreach (ArticleStatus::cases() as $status) {
            $this->assertTrue(
                $this->demoArticles()->where('status', $status)->exists(),
                "No demo article represents the [{$status->value}] workflow state.",
            );
        }

        $articles = $this->demoArticles()
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

        $workflowEventCount = $this->demoArticles()->withCount('workflowEvents')->get()->sum('workflow_events_count');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(9, User::query()->count());
        $this->assertSame(15, $this->demoArticles()->count());
        $this->assertSame(8, $this->demoChapters()->count());
        $this->assertSame(23, ArticleMedia::query()->whereIn('article_id', $this->demoArticles()->pluck('id'))->count());
        $this->assertSame(
            $workflowEventCount,
            $this->demoArticles()->withCount('workflowEvents')->get()->sum('workflow_events_count'),
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Publication>
     */
    private function demoPublications(): \Illuminate\Database\Eloquent\Builder
    {
        return Publication::query()->where('name', '!=', config('handbook.name'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function demoPublicationIds(): \Illuminate\Support\Collection
    {
        return $this->demoPublications()->pluck('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Article>
     */
    private function demoArticles(): \Illuminate\Database\Eloquent\Builder
    {
        return Article::query()->whereIn(
            'publication_issue_id',
            PublicationIssue::query()->whereIn('publication_id', $this->demoPublicationIds())->pluck('id'),
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<PublicationChapter>
     */
    private function demoChapters(): \Illuminate\Database\Eloquent\Builder
    {
        return PublicationChapter::query()->whereIn(
            'publication_issue_id',
            PublicationIssue::query()->whereIn('publication_id', $this->demoPublicationIds())->pluck('id'),
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

    public function test_demo_seeder_seeds_comment_threads_anchored_in_content(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, ArticleCommentThread::query()->count());
        $this->assertSame(8, ArticleComment::query()->count());
        $this->assertSame(1, ArticleCommentThread::query()->whereNotNull('resolved_at')->count());
        $this->assertSame(3, ArticleCommentThread::query()->whereNull('resolved_at')->count());

        $resolvedThread = ArticleCommentThread::query()->whereNotNull('resolved_at')->firstOrFail();
        $this->assertNotNull($resolvedThread->resolved_by_id);

        $threadWithReplies = ArticleCommentThread::query()->withCount('comments')->get()
            ->firstWhere('comments_count', '>=', 2);
        $this->assertNotNull($threadWithReplies, 'Expected at least one comment thread with replies.');

        $threadIds = ArticleCommentThread::query()->pluck('id')->all();

        foreach (ArticleCommentThread::query()->with('article')->get() as $thread) {
            $markThreadIds = $this->commentThreadIdsInContent($thread->article->content ?? []);
            $this->assertContains(
                $thread->id,
                $markThreadIds,
                "Comment thread [{$thread->id}] is not anchored in its article content.",
            );
        }

        foreach (Article::query()->get() as $article) {
            foreach ($this->commentThreadIdsInContent($article->content ?? []) as $markThreadId) {
                $this->assertContains($markThreadId, $threadIds);
            }
        }

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, ArticleCommentThread::query()->count());
        $this->assertSame(8, ArticleComment::query()->count());
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<string>
     */
    private function commentThreadIdsInContent(array $node): array
    {
        $threadIds = [];

        foreach ($node['marks'] ?? [] as $mark) {
            if (is_array($mark) && ($mark['type'] ?? null) === 'comment') {
                $threadId = $mark['attrs']['threadId'] ?? null;

                if (is_string($threadId)) {
                    $threadIds[] = $threadId;
                }
            }
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                $threadIds = array_merge($threadIds, $this->commentThreadIdsInContent($child));
            }
        }

        return array_values(array_unique($threadIds));
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
