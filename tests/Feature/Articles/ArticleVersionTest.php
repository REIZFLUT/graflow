<?php

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\ArticleVersion;
use App\Models\User;
use App\Services\ArticleVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleVersionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function tipTapContent(string $text = 'Hello'): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => $text],
                    ],
                ],
            ],
        ];
    }

    public function test_store_creates_first_version(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('articles.store'), [
                'title' => 'Versioned Article',
                'content' => $this->tipTapContent(),
            ]);

        $article = Article::query()->first();

        $this->assertNotNull($article);
        $this->assertSame(1, $article->versions()->count());

        $version = $article->versions()->first();

        $this->assertSame(1, $version->version_number);
        $this->assertSame('Versioned Article', $version->title);
        $this->assertSame($user->id, $version->created_by_id);
    }

    public function test_update_creates_new_version(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create([
            'title' => 'Original',
            'content' => $this->tipTapContent('Original'),
        ]);

        app(ArticleVersionService::class)->snapshot($article, $user);

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => 'Second Save',
                'content' => $this->tipTapContent('Second'),
                'status' => 'draft',
            ]);

        $article->refresh();

        $this->assertSame(2, $article->versions()->count());
        $this->assertSame('Second Save', $article->title);

        $latestVersion = $article->versions()->orderByDesc('version_number')->first();

        $this->assertSame(2, $latestVersion->version_number);
        $this->assertSame('Second Save', $latestVersion->title);
    }

    public function test_restore_copies_version_and_creates_new_version(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create([
            'title' => 'Version One',
            'content' => $this->tipTapContent('Version One'),
        ]);

        $firstVersion = app(ArticleVersionService::class)->snapshot($article, $user);

        $article->update([
            'title' => 'Version Two',
            'content' => $this->tipTapContent('Version Two'),
        ]);

        app(ArticleVersionService::class)->snapshot($article, $user);

        $this->actingAs($user)
            ->post(route('articles.versions.restore', [
                'article' => $article,
                'version' => $firstVersion,
            ]))
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();

        $this->assertSame('Version One', $article->title);
        $this->assertSame('Version One', $article->content['content'][0]['content'][0]['text']);
        $this->assertSame(3, $article->versions()->count());

        $latestVersion = ArticleVersion::query()
            ->where('article_id', $article->id)
            ->orderByDesc('version_number')
            ->first();

        $this->assertSame(3, $latestVersion->version_number);
        $this->assertSame('Version One', $latestVersion->title);
    }

    public function test_snapshot_records_current_article_status(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create([
            'title' => 'Draft Stage',
            'content' => $this->tipTapContent('Draft'),
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => 'Draft Stage',
                'content' => $this->tipTapContent('Draft'),
                'status' => 'draft',
            ]);

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => 'Published Stage',
                'content' => $this->tipTapContent('Published'),
                'status' => 'published',
            ]);

        $article->refresh();

        $lastDraft = $article->versions()
            ->where('status', 'draft')
            ->orderByDesc('version_number')
            ->first();

        $latestPublished = $article->versions()
            ->where('status', 'published')
            ->orderByDesc('version_number')
            ->first();

        $this->assertNotNull($lastDraft);
        $this->assertSame('Draft Stage', $lastDraft->title);
        $this->assertNotNull($latestPublished);
        $this->assertSame('Published Stage', $latestPublished->title);
        $this->assertTrue($latestPublished->version_number > $lastDraft->version_number);
    }

    public function test_restore_rejects_version_from_other_article(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();
        $otherArticle = Article::factory()->for($user, 'owner')->create();
        $foreignVersion = ArticleVersion::factory()->for($otherArticle)->create();

        $this->actingAs($user)
            ->post(route('articles.versions.restore', [
                'article' => $article,
                'version' => $foreignVersion,
            ]))
            ->assertNotFound();
    }
}
