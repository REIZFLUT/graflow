<?php

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\ArticleCommentThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ArticleCommentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function contentWithComment(string $threadId, string $text = 'Hello world'): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $text,
                            'marks' => [
                                [
                                    'type' => 'comment',
                                    'attrs' => ['threadId' => $threadId],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_participant_can_create_comment_thread(): void
    {
        $author = User::factory()->author()->create();
        $article = Article::factory()->for($author, 'owner')->create();
        $threadId = (string) Str::uuid();

        $this->actingAs($author)
            ->post(route('articles.comments.store', $article), [
                'id' => $threadId,
                'body' => 'Please rephrase this sentence.',
                'anchor_text' => 'Hello world',
                'content' => $this->contentWithComment($threadId),
            ])
            ->assertRedirect(route('articles.edit', $article));

        $thread = ArticleCommentThread::query()->find($threadId);

        $this->assertNotNull($thread);
        $this->assertSame($article->id, $thread->article_id);
        $this->assertSame($author->id, $thread->created_by_id);
        $this->assertSame('Hello world', $thread->anchor_text);
        $this->assertNull($thread->resolved_at);
        $this->assertSame(1, $thread->comments()->count());
        $this->assertSame(
            'Please rephrase this sentence.',
            $thread->comments()->first()->body,
        );

        $article->refresh();
        $this->assertSame(
            $threadId,
            $article->content['content'][0]['content'][0]['marks'][0]['attrs']['threadId'],
        );

        // Comment creation must not create a version snapshot.
        $this->assertSame(0, $article->versions()->count());
    }

    public function test_non_participant_cannot_comment(): void
    {
        $article = Article::factory()->create();
        $stranger = User::factory()->author()->create();
        $threadId = (string) Str::uuid();

        $this->actingAs($stranger)
            ->post(route('articles.comments.store', $article), [
                'id' => $threadId,
                'body' => 'I should not be allowed.',
                'anchor_text' => 'Hello',
                'content' => $this->contentWithComment($threadId),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('article_comment_threads', 0);
    }

    public function test_participant_can_reply_to_thread(): void
    {
        $author = User::factory()->author()->create();
        $article = Article::factory()->for($author, 'owner')->create();
        $thread = ArticleCommentThread::factory()
            ->for($article)
            ->for($author, 'createdBy')
            ->create();
        ArticleComment::factory()->for($thread, 'thread')->for($author)->create();

        $this->actingAs($author)
            ->post(route('articles.comments.reply', [$article, $thread]), [
                'body' => 'Good point, fixed.',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $this->assertSame(2, $thread->comments()->count());
    }

    public function test_reply_rejects_thread_from_other_article(): void
    {
        $author = User::factory()->author()->create();
        $article = Article::factory()->for($author, 'owner')->create();
        $otherArticle = Article::factory()->for($author, 'owner')->create();
        $foreignThread = ArticleCommentThread::factory()
            ->for($otherArticle)
            ->for($author, 'createdBy')
            ->create();

        $this->actingAs($author)
            ->post(route('articles.comments.reply', [$article, $foreignThread]), [
                'body' => 'Wrong article.',
            ])
            ->assertNotFound();
    }

    public function test_participant_can_resolve_and_reopen_thread(): void
    {
        $author = User::factory()->author()->create();
        $article = Article::factory()->for($author, 'owner')->create();
        $thread = ArticleCommentThread::factory()
            ->for($article)
            ->for($author, 'createdBy')
            ->create();

        $this->actingAs($author)
            ->patch(route('articles.comments.resolve', [$article, $thread]))
            ->assertRedirect(route('articles.edit', $article));

        $thread->refresh();
        $this->assertNotNull($thread->resolved_at);
        $this->assertSame($author->id, $thread->resolved_by_id);

        $this->actingAs($author)
            ->patch(route('articles.comments.reopen', [$article, $thread]))
            ->assertRedirect(route('articles.edit', $article));

        $thread->refresh();
        $this->assertNull($thread->resolved_at);
        $this->assertNull($thread->resolved_by_id);
    }

    public function test_edit_page_includes_comment_threads_prop(): void
    {
        $author = User::factory()->author()->create(['name' => 'Ada Author']);
        $article = Article::factory()->for($author, 'owner')->create();
        $thread = ArticleCommentThread::factory()
            ->for($article)
            ->for($author, 'createdBy')
            ->create(['anchor_text' => 'referenced text']);
        ArticleComment::factory()
            ->for($thread, 'thread')
            ->for($author)
            ->create(['body' => 'First remark']);

        $this->actingAs($author)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('capabilities.comment', true)
                ->has('commentThreads', 1)
                ->where('commentThreads.0.id', $thread->id)
                ->where('commentThreads.0.anchor_text', 'referenced text')
                ->where('commentThreads.0.created_by.name', 'Ada Author')
                ->has('commentThreads.0.comments', 1)
                ->where('commentThreads.0.comments.0.body', 'First remark'));
    }
}
