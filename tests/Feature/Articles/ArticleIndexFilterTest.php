<?php

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\PublicationIssue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ArticleIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_search_filters_articles_by_title(): void
    {
        $admin = $this->admin();

        $match = Article::factory()->create(['title' => 'Alpha Investigation']);
        Article::factory()->create(['title' => 'Beta Chronicle']);

        $this->actingAs($admin)
            ->get(route('articles.index', ['search' => 'Alpha']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data', fn ($data) => collect($data)->count() === 1
                    && collect($data)->first()['id'] === $match->id)
                ->where('filters.search', 'Alpha'));
    }

    public function test_publication_id_filter_returns_only_matching_articles(): void
    {
        $admin = $this->admin();

        $issueA = PublicationIssue::factory()->create();
        $issueB = PublicationIssue::factory()->create();

        $match = Article::factory()->create(['publication_issue_id' => $issueA->id]);
        Article::factory()->create(['publication_issue_id' => $issueB->id]);

        $this->actingAs($admin)
            ->get(route('articles.index', ['publication_id' => $issueA->publication_id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data', fn ($data) => collect($data)->pluck('id')->all() === [$match->id])
                ->where('filters.publication_id', $issueA->publication_id));
    }

    public function test_issue_id_filter_returns_only_matching_articles(): void
    {
        $admin = $this->admin();

        $issueA = PublicationIssue::factory()->create();
        $issueB = PublicationIssue::factory()->create();

        $match = Article::factory()->create(['publication_issue_id' => $issueA->id]);
        Article::factory()->create(['publication_issue_id' => $issueB->id]);

        $this->actingAs($admin)
            ->get(route('articles.index', ['issue_id' => $issueA->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data', fn ($data) => collect($data)->pluck('id')->all() === [$match->id])
                ->where('filters.issue_id', $issueA->id));
    }

    public function test_author_id_filter_returns_only_matching_articles(): void
    {
        $admin = $this->admin();

        $authorA = User::factory()->author()->create();
        $authorB = User::factory()->author()->create();

        $match = Article::factory()->create(['author_id' => $authorA->id]);
        Article::factory()->create(['author_id' => $authorB->id]);

        $this->actingAs($admin)
            ->get(route('articles.index', ['author_id' => $authorA->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data', fn ($data) => collect($data)->pluck('id')->all() === [$match->id])
                ->where('filters.author_id', $authorA->id));
    }

    public function test_index_hides_published_articles_by_default(): void
    {
        $admin = $this->admin();

        $active = Article::factory()->create();
        Article::factory()->published()->create();

        $this->actingAs($admin)
            ->get(route('articles.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data', fn ($data) => collect($data)->pluck('id')->all() === [$active->id])
                ->where('filters.archived', false));
    }

    public function test_archived_filter_returns_only_published_articles(): void
    {
        $admin = $this->admin();

        Article::factory()->create();
        $published = Article::factory()->published()->create();

        $this->actingAs($admin)
            ->get(route('articles.index', ['archived' => 1]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data', fn ($data) => collect($data)->pluck('id')->all() === [$published->id])
                ->where('filters.archived', true));
    }

    public function test_archived_filter_combines_with_author_filter(): void
    {
        $admin = $this->admin();

        $authorA = User::factory()->author()->create();
        $authorB = User::factory()->author()->create();

        $match = Article::factory()->published()->create(['author_id' => $authorA->id]);
        Article::factory()->published()->create(['author_id' => $authorB->id]);
        Article::factory()->create(['author_id' => $authorA->id]);

        $this->actingAs($admin)
            ->get(route('articles.index', ['archived' => 1, 'author_id' => $authorA->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data', fn ($data) => collect($data)->pluck('id')->all() === [$match->id])
                ->where('filters.archived', true)
                ->where('filters.author_id', $authorA->id));
    }

    public function test_title_sort_orders_articles_ascending_and_descending(): void
    {
        $admin = $this->admin();

        $charlie = Article::factory()->create(['title' => 'Charlie']);
        $alpha = Article::factory()->create(['title' => 'Alpha']);
        $bravo = Article::factory()->create(['title' => 'Bravo']);

        $this->actingAs($admin)
            ->get(route('articles.index', ['sort' => 'title', 'direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data', fn ($data) => collect($data)->pluck('id')->all() === [
                    $alpha->id,
                    $bravo->id,
                    $charlie->id,
                ])
                ->where('filters.sort', 'title')
                ->where('filters.direction', 'asc'));

        $this->actingAs($admin)
            ->get(route('articles.index', ['sort' => 'title', 'direction' => 'desc']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data', fn ($data) => collect($data)->pluck('id')->all() === [
                    $charlie->id,
                    $bravo->id,
                    $alpha->id,
                ])
                ->where('filters.direction', 'desc'));
    }

    public function test_filter_options_expose_publications_issues_and_authors(): void
    {
        $admin = $this->admin();

        $author = User::factory()->author()->create();
        $issue = PublicationIssue::factory()->create();

        Article::factory()->create([
            'publication_issue_id' => $issue->id,
            'author_id' => $author->id,
        ]);

        $this->actingAs($admin)
            ->get(route('articles.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('filterOptions.publications', 1, fn (AssertableInertia $publication) => $publication
                    ->where('id', $issue->publication_id)
                    ->hasAll(['id', 'name']))
                ->has('filterOptions.issues', 1, fn (AssertableInertia $issueOption) => $issueOption
                    ->where('id', $issue->id)
                    ->where('publication_id', $issue->publication_id)
                    ->hasAll(['id', 'label', 'publication_id']))
                ->has('filterOptions.authors', 1, fn (AssertableInertia $authorOption) => $authorOption
                    ->where('id', $author->id)
                    ->hasAll(['id', 'name'])));
    }
}
