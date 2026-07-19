<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Publication;
use App\Models\User;
use App\Services\ArticleMediaService;
use App\Support\Handbook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HandbookTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_is_accessible_to_non_admin_users(): void
    {
        User::factory()->admin()->create();
        $author = User::factory()->author()->create();

        $this->actingAs($author)
            ->get(route('handbook.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('handbook/reader')
                ->where('canManage', false)
                ->has('articles')
                ->has('chapters')
            );
    }

    public function test_reader_marks_admin_as_manager(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('handbook.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('handbook/reader')
                ->where('canManage', true)
                ->where('title', Handbook::name())
            );
    }

    public function test_reader_disables_marginal_column_but_keeps_serif_font(): void
    {
        $admin = User::factory()->admin()->create();
        $issue = Handbook::resolveIssue();

        Article::factory()->create([
            'title' => 'Kapitelübersicht',
            'publication_issue_id' => $issue->id,
        ]);

        $this->actingAs($admin)
            ->get(route('handbook.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('handbook/reader')
                ->where('articles.0.editor_settings.has_marginal_column', false)
                ->where('articles.0.editor_settings.font', 'spectral')
            );
    }

    public function test_handbook_publication_is_hidden_from_non_admin_publication_list(): void
    {
        User::factory()->admin()->create();
        $author = User::factory()->author()->create();

        Handbook::resolveIssue();

        $this->actingAs($author)
            ->get(route('publications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('publications/index')
                ->where('publications.data', fn ($publications) => collect($publications)
                    ->doesntContain('name', Handbook::name()))
            );
    }

    public function test_admin_can_create_a_handbook_article(): void
    {
        $admin = User::factory()->admin()->create();
        $issue = Handbook::resolveIssue();

        $response = $this->actingAs($admin)
            ->post(route('handbook.articles.store'), [
                'title' => 'Erste Schritte',
            ]);

        $article = Article::query()->where('title', 'Erste Schritte')->firstOrFail();

        $response->assertRedirect(route('articles.edit', $article));

        $this->assertSame($issue->id, $article->publication_issue_id);
        $this->assertSame($admin->id, $article->owner_id);
        $this->assertSame($admin->id, $article->current_assignee_id);
        $this->assertSame(ArticleStatus::Authoring, $article->status);
    }

    public function test_admin_can_create_a_handbook_article_within_a_chapter(): void
    {
        $admin = User::factory()->admin()->create();
        $issue = Handbook::resolveIssue();
        $chapter = $issue->chapters()->create([
            'title' => 'Einführung',
            'position' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('handbook.articles.store'), [
                'title' => 'Überblick',
                'publication_chapter_id' => $chapter->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('articles', [
            'title' => 'Überblick',
            'publication_issue_id' => $issue->id,
            'publication_chapter_id' => $chapter->id,
        ]);
    }

    public function test_non_admin_cannot_create_a_handbook_article(): void
    {
        User::factory()->admin()->create();
        $author = User::factory()->author()->create();

        $this->actingAs($author)
            ->post(route('handbook.articles.store'), [
                'title' => 'Nicht erlaubt',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('articles', ['title' => 'Nicht erlaubt']);
    }

    public function test_creating_an_article_requires_a_title(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('handbook.show'))
            ->post(route('handbook.articles.store'), [])
            ->assertSessionHasErrors('title');
    }

    public function test_resolve_issue_creates_publication_owned_by_admin_idempotently(): void
    {
        $admin = User::factory()->admin()->create();

        $first = Handbook::resolveIssue();
        $second = Handbook::resolveIssue();

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Publication::query()->where('name', Handbook::name())->count());
        $this->assertSame($admin->id, $first->publication->owner_id);
    }

    public function test_resolve_issue_returns_null_without_an_admin(): void
    {
        User::factory()->author()->create();

        $this->assertNull(Handbook::resolveIssue());
    }

    public function test_handbook_article_media_is_viewable_by_every_user(): void
    {
        Storage::fake((string) config('article-media.disk'));

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('handbook.articles.store'), ['title' => 'Artikel mit Screenshot'])
            ->assertRedirect();

        $article = Article::query()->where('title', 'Artikel mit Screenshot')->firstOrFail();

        $media = app(ArticleMediaService::class)->storeForArticle(
            UploadedFile::fake()->image('screenshot.png', 640, 480),
            $article,
            $admin,
            ['alt_text' => 'Screenshot', 'copyright' => 'Graflow'],
        );

        $reader = User::factory()->author()->create();

        $this->actingAs($reader)
            ->get(route('articles.media.file', [
                'article' => $article->id,
                'media' => $media->id,
                'variant' => 'preview-webp',
            ]))
            ->assertOk();
    }

    public function test_regular_article_media_stays_hidden_from_uninvolved_users(): void
    {
        Storage::fake((string) config('article-media.disk'));

        $article = Article::factory()->create();
        $owner = $article->owner;

        $media = app(ArticleMediaService::class)->storeForArticle(
            UploadedFile::fake()->image('photo.png', 640, 480),
            $article,
            $owner,
            ['alt_text' => 'Foto', 'copyright' => 'Test'],
        );

        $stranger = User::factory()->author()->create();

        $this->actingAs($stranger)
            ->get(route('articles.media.file', [
                'article' => $article->id,
                'media' => $media->id,
                'variant' => 'preview-webp',
            ]))
            ->assertForbidden();
    }
}
