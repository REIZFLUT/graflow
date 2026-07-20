<?php

namespace Tests\Feature\Articles;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ArticleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_manager_can_start_correction_edit_content_and_complete(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'owner_id' => $author->id,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
            'title' => 'Originaltitel',
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.start-product-manager-correction', $article), [
                'reason' => 'Kleine sprachliche Anpassungen.',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(ArticleStatus::ProductManagerCorrection, $article->status);
        $this->assertSame($productManager->id, $article->current_assignee_id);
        $this->assertDatabaseHas('article_workflow_events', [
            'article_id' => $article->id,
            'from_status' => ArticleStatus::ManuscriptSubmitted->value,
            'to_status' => ArticleStatus::ProductManagerCorrection->value,
            'actor_id' => $productManager->id,
            'assignee_id' => $productManager->id,
            'reason' => 'Kleine sprachliche Anpassungen.',
        ]);

        $this->actingAs($author)
            ->put(route('articles.update', $article), [
                'title' => 'Autor darf nicht ändern',
                'content' => ['type' => 'doc', 'content' => []],
            ])
            ->assertForbidden();

        $this->actingAs($productManager)
            ->put(route('articles.update', $article), [
                'title' => 'Korrigierter Titel',
                'content' => [
                    'type' => 'doc',
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                ['type' => 'text', 'text' => 'Korrigierter Inhalt'],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame('Korrigierter Titel', $article->title);
        $this->assertDatabaseHas('article_versions', [
            'article_id' => $article->id,
            'title' => 'Korrigierter Titel',
            'created_by_id' => $productManager->id,
            'status' => ArticleStatus::ProductManagerCorrection->value,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.complete-product-manager-correction', $article), [
                'reason' => 'Korrektur abgeschlossen.',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(ArticleStatus::ManuscriptSubmitted, $article->status);
        $this->assertNull($article->current_assignee_id);
        $this->assertDatabaseHas('article_workflow_events', [
            'article_id' => $article->id,
            'from_status' => ArticleStatus::ProductManagerCorrection->value,
            'to_status' => ArticleStatus::ManuscriptSubmitted->value,
            'reason' => 'Korrektur abgeschlossen.',
        ]);

        $this->actingAs($productManager)
            ->put(route('articles.update', $article), [
                'title' => 'Nach Abschluss gesperrt',
                'content' => ['type' => 'doc', 'content' => []],
            ])
            ->assertForbidden();
    }

    public function test_unrelated_product_manager_cannot_start_correction(): void
    {
        $responsible = User::factory()->productManager()->create();
        $otherProductManager = User::factory()->productManager()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'product_manager_id' => $responsible->id,
        ]);

        $this->actingAs($otherProductManager)
            ->post(route('articles.workflow.start-product-manager-correction', $article))
            ->assertForbidden();

        $this->assertSame(ArticleStatus::ManuscriptSubmitted, $article->refresh()->status);
    }

    public function test_product_manager_can_assign_author_and_author_can_submit(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->planned()->create([
            'owner_id' => $author->id,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.assign-author', $article), [
                'assignee_id' => $author->id,
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(ArticleStatus::Authoring, $article->status);
        $this->assertSame($author->id, $article->current_assignee_id);

        $this->actingAs($author)
            ->post(route('articles.workflow.submit-manuscript', $article))
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(ArticleStatus::ManuscriptSubmitted, $article->status);
        $this->assertNull($article->current_assignee_id);
        $this->assertCount(2, $article->workflowEvents);
        $this->assertDatabaseHas('article_participants', [
            'article_id' => $article->id,
            'user_id' => $productManager->id,
        ]);
        $this->assertDatabaseHas('article_participants', [
            'article_id' => $article->id,
            'user_id' => $author->id,
        ]);
    }

    public function test_product_manager_can_complete_editorial_work_and_publish(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $editor = User::factory()->editor()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'owner_id' => $author->id,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.assign-editorial', $article), [
                'assignee_id' => $editor->id,
            ])
            ->assertRedirect();

        $this->actingAs($productManager)
            ->post(route('articles.workflow.mark-ready', $article))
            ->assertRedirect();

        $this->actingAs($productManager)
            ->post(route('articles.workflow.publish', $article))
            ->assertRedirect();

        $article->refresh();
        $this->assertSame(ArticleStatus::Published, $article->status);
        $this->assertNull($article->current_assignee_id);
        $this->assertNotNull($article->published_at);
        $this->assertDatabaseHas('article_participants', [
            'article_id' => $article->id,
            'user_id' => $editor->id,
        ]);
    }

    public function test_editorial_assignee_can_finish_and_return_article_to_product_manager(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $editor = User::factory()->editor()->create();
        $article = Article::factory()->editorialWork()->create([
            'owner_id' => $author->id,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
            'current_assignee_id' => $editor->id,
        ]);

        $this->actingAs($editor)
            ->post(route('articles.workflow.complete-editorial-work', $article), [
                'reason' => 'Lektorat abgeschlossen.',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(ArticleStatus::ManuscriptSubmitted, $article->status);
        $this->assertNull($article->current_assignee_id);
        $this->assertDatabaseHas('article_workflow_events', [
            'article_id' => $article->id,
            'from_status' => ArticleStatus::EditorialWork->value,
            'to_status' => ArticleStatus::ManuscriptSubmitted->value,
            'actor_id' => $editor->id,
            'reason' => 'Lektorat abgeschlossen.',
        ]);

        $this->actingAs($editor)
            ->post(route('articles.workflow.complete-editorial-work', $article))
            ->assertForbidden();
    }

    public function test_unassigned_editor_cannot_finish_editorial_work(): void
    {
        $assignedEditor = User::factory()->editor()->create();
        $otherEditor = User::factory()->editor()->create();
        $article = Article::factory()->editorialWork()->create([
            'current_assignee_id' => $assignedEditor->id,
        ]);

        $this->actingAs($otherEditor)
            ->post(route('articles.workflow.complete-editorial-work', $article))
            ->assertForbidden();

        $this->assertSame(ArticleStatus::EditorialWork, $article->refresh()->status);
        $this->assertSame($assignedEditor->id, $article->current_assignee_id);
    }

    public function test_admin_can_force_any_article_status_including_reopening_published_articles(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->author()->create();
        $editor = User::factory()->editor()->create();

        foreach (ArticleStatus::cases() as $status) {
            $article = Article::factory()->published()->create([
                'owner_id' => $author->id,
                'author_id' => $author->id,
            ]);
            $payload = [
                'status' => $status->value,
                'reason' => 'Administrativer Statuswechsel.',
            ];

            if ($status === ArticleStatus::EditorialWork) {
                $payload['assignee_id'] = $editor->id;
            }

            $this->actingAs($admin)
                ->post(route('articles.workflow.force-status', $article), $payload)
                ->assertRedirect(route('articles.edit', $article));

            $article->refresh();
            $this->assertSame($status, $article->status);
            $this->assertSame(
                match ($status) {
                    ArticleStatus::Authoring, ArticleStatus::Revision => $author->id,
                    ArticleStatus::EditorialWork => $editor->id,
                    ArticleStatus::ProductManagerCorrection => $article->product_manager_id,
                    default => null,
                },
                $article->current_assignee_id,
            );
            $this->assertSame(
                $status === ArticleStatus::Published,
                $article->published_at !== null,
            );
            $this->assertDatabaseHas('article_workflow_events', [
                'article_id' => $article->id,
                'to_status' => $status->value,
                'actor_id' => $admin->id,
                'reason' => 'Administrativer Statuswechsel.',
            ]);
        }
    }

    public function test_non_admin_cannot_force_article_status(): void
    {
        $productManager = User::factory()->productManager()->create();
        $article = Article::factory()->published()->create([
            'product_manager_id' => $productManager->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.force-status', $article), [
                'status' => ArticleStatus::Authoring->value,
            ])
            ->assertForbidden();

        $this->assertSame(ArticleStatus::Published, $article->refresh()->status);
    }

    public function test_admin_must_select_editor_or_lector_for_editorial_status(): void
    {
        $admin = User::factory()->admin()->create();
        $article = Article::factory()->planned()->create();

        $this->actingAs($admin)
            ->post(route('articles.workflow.force-status', $article), [
                'status' => ArticleStatus::EditorialWork->value,
            ])
            ->assertSessionHasErrors('assignee_id');

        $this->assertSame(ArticleStatus::Planned, $article->refresh()->status);
    }

    public function test_unrelated_product_manager_cannot_manage_article_workflow(): void
    {
        $responsible = User::factory()->productManager()->create();
        $otherProductManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->planned()->create([
            'product_manager_id' => $responsible->id,
            'author_id' => $author->id,
        ]);

        $this->actingAs($otherProductManager)
            ->post(route('articles.workflow.assign-author', $article), [
                'assignee_id' => $author->id,
            ])
            ->assertForbidden();

        $this->assertSame(ArticleStatus::Planned, $article->refresh()->status);
    }

    public function test_published_article_cannot_be_mutated_or_deleted(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->published()->create([
            'owner_id' => $author->id,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
        ]);

        $this->actingAs($productManager)
            ->delete(route('articles.destroy', $article))
            ->assertForbidden();

        $this->actingAs($author)
            ->put(route('articles.update', $article), [
                'title' => 'Changed',
                'content' => ['type' => 'doc', 'content' => []],
            ])
            ->assertForbidden();
    }

    public function test_product_manager_can_return_submitted_manuscript_to_author(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'owner_id' => $author->id,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.return-to-author', $article))
            ->assertSessionHasErrors('reason');

        $this->actingAs($productManager)
            ->post(route('articles.workflow.return-to-author', $article), [
                'reason' => 'Bitte Abschnitt 2 kürzen.',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(ArticleStatus::Revision, $article->status);
        $this->assertSame($author->id, $article->current_assignee_id);
        $this->assertDatabaseHas('article_workflow_events', [
            'article_id' => $article->id,
            'from_status' => ArticleStatus::ManuscriptSubmitted->value,
            'to_status' => ArticleStatus::Revision->value,
            'actor_id' => $productManager->id,
            'assignee_id' => $author->id,
            'reason' => 'Bitte Abschnitt 2 kürzen.',
        ]);
    }

    public function test_unrelated_product_manager_cannot_return_manuscript_to_author(): void
    {
        $otherProductManager = User::factory()->productManager()->create();
        $article = Article::factory()->manuscriptSubmitted()->create();

        $this->actingAs($otherProductManager)
            ->post(route('articles.workflow.return-to-author', $article), [
                'reason' => 'Nicht meine Baustelle.',
            ])
            ->assertForbidden();

        $this->assertSame(ArticleStatus::ManuscriptSubmitted, $article->refresh()->status);
    }

    public function test_product_manager_can_unpublish_own_article(): void
    {
        $productManager = User::factory()->productManager()->create();
        $article = Article::factory()->published()->create([
            'product_manager_id' => $productManager->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.unpublish', $article))
            ->assertSessionHasErrors('reason');

        $this->actingAs($productManager)
            ->post(route('articles.workflow.unpublish', $article), [
                'reason' => 'Fehler im Beitrag entdeckt.',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(ArticleStatus::ReadyForPublication, $article->status);
        $this->assertNull($article->published_at);
        $this->assertNull($article->current_assignee_id);
        $this->assertDatabaseHas('article_workflow_events', [
            'article_id' => $article->id,
            'from_status' => ArticleStatus::Published->value,
            'to_status' => ArticleStatus::ReadyForPublication->value,
            'actor_id' => $productManager->id,
            'reason' => 'Fehler im Beitrag entdeckt.',
        ]);
    }

    public function test_only_responsible_product_manager_or_admin_can_unpublish(): void
    {
        $otherProductManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->published()->create([
            'author_id' => $author->id,
            'owner_id' => $author->id,
        ]);

        $this->actingAs($otherProductManager)
            ->post(route('articles.workflow.unpublish', $article), ['reason' => 'Test'])
            ->assertForbidden();

        $this->actingAs($author)
            ->post(route('articles.workflow.unpublish', $article), ['reason' => 'Test'])
            ->assertForbidden();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('articles.workflow.unpublish', $article), [
                'reason' => 'Administrativ zurückgezogen.',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $this->assertSame(ArticleStatus::ReadyForPublication, $article->refresh()->status);
    }

    public function test_product_manager_can_start_correction_from_ready_for_publication(): void
    {
        $productManager = User::factory()->productManager()->create();
        $article = Article::factory()->readyForPublication()->create([
            'product_manager_id' => $productManager->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.start-product-manager-correction', $article), [
                'reason' => 'Tippfehler kurz vor Veröffentlichung.',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(ArticleStatus::ProductManagerCorrection, $article->status);
        $this->assertSame($productManager->id, $article->current_assignee_id);
    }

    public function test_author_revision_request_stays_locked_until_product_manager_assigns_rework(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'owner_id' => $author->id,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
        ]);

        $this->actingAs($author)
            ->post(route('articles.workflow.request-revision', $article))
            ->assertSessionHasErrors('reason');

        $this->actingAs($author)
            ->post(route('articles.workflow.request-revision', $article), [
                'reason' => 'Neue Faktenlage',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(ArticleStatus::RevisionRequested, $article->status);
        $this->assertNull($article->current_assignee_id);

        $this->actingAs($author)
            ->put(route('articles.update', $article), [
                'title' => 'Nicht erlaubt',
                'content' => ['type' => 'doc', 'content' => []],
            ])
            ->assertForbidden();

        $this->actingAs($productManager)
            ->post(route('articles.workflow.assign-author', $article), [
                'assignee_id' => $author->id,
                'reason' => 'Nacharbeit freigegeben',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(ArticleStatus::Revision, $article->status);
        $this->assertSame($author->id, $article->current_assignee_id);
        $this->assertDatabaseHas('article_workflow_events', [
            'article_id' => $article->id,
            'from_status' => ArticleStatus::RevisionRequested->value,
            'to_status' => ArticleStatus::Revision->value,
            'reason' => 'Nacharbeit freigegeben',
        ]);
    }

    public function test_only_editorial_assignee_can_edit_and_product_manager_can_recall(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $editor = User::factory()->editor()->create();
        $article = Article::factory()->editorialWork()->create([
            'owner_id' => $author->id,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
            'current_assignee_id' => $editor->id,
        ]);

        $this->actingAs($author)
            ->put(route('articles.update', $article), [
                'title' => 'Autor darf nicht ändern',
                'content' => ['type' => 'doc', 'content' => []],
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->put(route('articles.update', $article), [
                'title' => 'Lektorierte Fassung',
                'content' => ['type' => 'doc', 'content' => []],
            ])
            ->assertRedirect(route('articles.edit', $article));

        $this->actingAs($productManager)
            ->post(route('articles.workflow.recall', $article), [
                'reason' => 'Bearbeitung zurückgeholt',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(ArticleStatus::ManuscriptSubmitted, $article->status);
        $this->assertNull($article->current_assignee_id);

        $this->actingAs($editor)
            ->put(route('articles.update', $article), [
                'title' => 'Nach Rückholung gesperrt',
                'content' => ['type' => 'doc', 'content' => []],
            ])
            ->assertForbidden();
    }

    public function test_product_manager_can_recall_article_from_every_status(): void
    {
        $recallableStatuses = [
            ArticleStatus::Planned,
            ArticleStatus::Authoring,
            ArticleStatus::ProductManagerCorrection,
            ArticleStatus::RevisionRequested,
            ArticleStatus::Revision,
            ArticleStatus::EditorialWork,
            ArticleStatus::ReadyForPublication,
            ArticleStatus::Published,
        ];

        foreach ($recallableStatuses as $status) {
            $productManager = User::factory()->productManager()->create();
            $author = User::factory()->author()->create();
            $article = Article::factory()->create([
                'owner_id' => $author->id,
                'product_manager_id' => $productManager->id,
                'author_id' => $author->id,
                'status' => $status,
                'current_assignee_id' => $status === ArticleStatus::EditorialWork
                    ? User::factory()->editor()->create()->id
                    : null,
                'published_at' => $status === ArticleStatus::Published ? now() : null,
            ]);

            $this->actingAs($productManager)
                ->post(route('articles.workflow.recall', $article), [
                    'reason' => 'Zurückgeholt aus '.$status->value,
                ])
                ->assertRedirect(route('articles.edit', $article));

            $article->refresh();
            $this->assertSame(ArticleStatus::ManuscriptSubmitted, $article->status, $status->value);
            $this->assertNull($article->current_assignee_id, $status->value);
            $this->assertNull($article->published_at, $status->value);
            $this->assertDatabaseHas('article_workflow_events', [
                'article_id' => $article->id,
                'from_status' => $status->value,
                'to_status' => ArticleStatus::ManuscriptSubmitted->value,
                'actor_id' => $productManager->id,
            ]);
        }
    }

    public function test_product_manager_cannot_recall_already_submitted_manuscript(): void
    {
        $productManager = User::factory()->productManager()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'product_manager_id' => $productManager->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.recall', $article), [
                'reason' => 'Bereits eingereicht.',
            ])
            ->assertForbidden();

        $this->assertSame(ArticleStatus::ManuscriptSubmitted, $article->refresh()->status);
    }

    public function test_product_manager_can_start_correction_from_every_status(): void
    {
        $correctableStatuses = [
            ArticleStatus::Planned,
            ArticleStatus::Authoring,
            ArticleStatus::ManuscriptSubmitted,
            ArticleStatus::RevisionRequested,
            ArticleStatus::Revision,
            ArticleStatus::EditorialWork,
            ArticleStatus::ReadyForPublication,
            ArticleStatus::Published,
        ];

        foreach ($correctableStatuses as $status) {
            $productManager = User::factory()->productManager()->create();
            $author = User::factory()->author()->create();
            $article = Article::factory()->create([
                'owner_id' => $author->id,
                'product_manager_id' => $productManager->id,
                'author_id' => $author->id,
                'status' => $status,
                'current_assignee_id' => $status === ArticleStatus::EditorialWork
                    ? User::factory()->editor()->create()->id
                    : null,
                'published_at' => $status === ArticleStatus::Published ? now() : null,
            ]);

            $this->actingAs($productManager)
                ->post(route('articles.workflow.start-product-manager-correction', $article), [
                    'reason' => 'Eigenkorrektur aus '.$status->value,
                ])
                ->assertRedirect(route('articles.edit', $article));

            $article->refresh();
            $this->assertSame(ArticleStatus::ProductManagerCorrection, $article->status, $status->value);
            $this->assertSame($productManager->id, $article->current_assignee_id, $status->value);
            $this->assertNull($article->published_at, $status->value);
        }
    }

    public function test_product_manager_cannot_start_correction_when_already_in_correction(): void
    {
        $productManager = User::factory()->productManager()->create();
        $article = Article::factory()->productManagerCorrection()->create([
            'product_manager_id' => $productManager->id,
            'current_assignee_id' => $productManager->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.start-product-manager-correction', $article), [
                'reason' => 'Bereits in Korrektur.',
            ])
            ->assertForbidden();

        $this->assertSame(ArticleStatus::ProductManagerCorrection, $article->refresh()->status);
    }

    public function test_involved_people_keep_read_access_but_outsiders_do_not(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $formerEditor = User::factory()->editor()->create();
        $outsider = User::factory()->editor()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'owner_id' => $author->id,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
        ]);
        $article->participants()->create([
            'user_id' => $formerEditor->id,
            'process_role' => $formerEditor->role->value,
        ]);

        $this->actingAs($formerEditor)
            ->get(route('articles.edit', $article))
            ->assertOk();

        $this->actingAs($outsider)
            ->get(route('articles.edit', $article))
            ->assertForbidden();
    }

    public function test_editorial_assignment_rejects_users_with_the_wrong_role(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'owner_id' => $author->id,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.assign-editorial', $article), [
                'assignee_id' => $author->id,
            ])
            ->assertSessionHasErrors('assignee_id');

        $this->assertSame(ArticleStatus::ManuscriptSubmitted, $article->refresh()->status);
    }

    public function test_workflow_events_are_append_only(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->planned()->create([
            'owner_id' => $author->id,
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.assign-author', $article), [
                'assignee_id' => $author->id,
            ])
            ->assertRedirect();

        $event = $article->workflowEvents()->firstOrFail();

        $this->expectException(LogicException::class);
        $event->update(['reason' => 'Manipuliert']);
    }
}
