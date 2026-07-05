<?php

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_edit_another_users_article(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->for($owner, 'owner')->create();

        $this->actingAs($otherUser)
            ->get(route('articles.edit', $article))
            ->assertForbidden();
    }

    public function test_user_cannot_update_another_users_article(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->for($owner, 'owner')->create();

        $this->actingAs($otherUser)
            ->put(route('articles.update', $article), [
                'title' => 'Hacked',
                'content' => [
                    'type' => 'doc',
                    'content' => [],
                ],
                'status' => 'draft',
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_restore_version_on_another_users_article(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->for($owner, 'owner')->create();
        $version = $article->versions()->create([
            'version_number' => 1,
            'title' => $article->title,
            'content' => $article->content,
            'created_by_id' => $owner->id,
            'created_at' => now(),
        ]);

        $this->actingAs($otherUser)
            ->post(route('articles.versions.restore', [
                'article' => $article,
                'version' => $version,
            ]))
            ->assertForbidden();
    }
}
