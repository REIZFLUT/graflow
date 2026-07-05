<?php

namespace Tests\Feature\Publications;

use App\Models\Article;
use App\Models\Publication;
use App\Models\PublicationIssue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationIssueTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_issue(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->post(route('publications.issues.store', $publication), [
                'label' => '07-2026',
            ])
            ->assertRedirect(route('publications.edit', $publication));

        $this->assertDatabaseHas('publication_issues', [
            'publication_id' => $publication->id,
            'label' => '07-2026',
        ]);
    }

    public function test_user_can_update_issue(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create([
            'label' => '06-2026',
        ]);

        $this->actingAs($user)
            ->patch(route('publications.issues.update', [
                'publication' => $publication,
                'issue' => $issue,
            ]), [
                'label' => '07-2026',
            ])
            ->assertRedirect(route('publications.edit', $publication));

        $this->assertDatabaseHas('publication_issues', [
            'id' => $issue->id,
            'label' => '07-2026',
        ]);
    }

    public function test_user_can_delete_issue(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();

        $this->actingAs($user)
            ->delete(route('publications.issues.destroy', [
                'publication' => $publication,
                'issue' => $issue,
            ]))
            ->assertRedirect(route('publications.edit', $publication));

        $this->assertDatabaseMissing('publication_issues', [
            'id' => $issue->id,
        ]);
    }

    public function test_deleting_issue_nullifies_article_assignment(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $issue = PublicationIssue::factory()->for($publication)->create();
        $article = Article::factory()->for($user, 'owner')->create([
            'publication_issue_id' => $issue->id,
        ]);

        $this->actingAs($user)
            ->delete(route('publications.issues.destroy', [
                'publication' => $publication,
                'issue' => $issue,
            ]))
            ->assertRedirect();

        $article->refresh();

        $this->assertNull($article->publication_issue_id);
    }

    public function test_issue_label_must_be_unique_within_publication(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        PublicationIssue::factory()->for($publication)->create(['label' => '07-2026']);

        $this->actingAs($user)
            ->post(route('publications.issues.store', $publication), [
                'label' => '07-2026',
            ])
            ->assertSessionHasErrors('label');
    }

    public function test_user_cannot_manage_issues_on_another_users_publication(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create();

        $this->actingAs($otherUser)
            ->post(route('publications.issues.store', $publication), [
                'label' => '07-2026',
            ])
            ->assertForbidden();
    }

    public function test_issue_must_belong_to_publication_in_route(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $otherPublication = Publication::factory()->for($user, 'owner')->create();
        $issue = PublicationIssue::factory()->for($otherPublication)->create();

        $this->actingAs($user)
            ->patch(route('publications.issues.update', [
                'publication' => $publication,
                'issue' => $issue,
            ]), [
                'label' => '07-2026',
            ])
            ->assertNotFound();
    }
}
