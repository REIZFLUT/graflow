<?php

namespace Tests\Feature\Articles;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleWorkflowEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ArticleEditorWorkflowPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_manager_in_correction_receives_edit_and_complete_actions(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->productManagerCorrection()->create([
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
            'owner_id' => $author->id,
            'current_assignee_id' => $productManager->id,
        ]);

        $this->actingAs($productManager)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('capabilities.update_content', true)
                ->where('capabilities.manage_workflow', true)
                ->where('allowedActions', ['complete_product_manager_correction']));
    }

    public function test_workflow_manager_receives_role_filtered_assignment_options(): void
    {
        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create(['name' => 'Ada Author']);
        $editor = User::factory()->editor()->create(['name' => 'Emil Editor']);
        $lector = User::factory()->lector()->create(['name' => 'Lena Lector']);
        User::factory()->productManager()->create(['name' => 'Other Manager']);
        $article = Article::factory()->planned()->create([
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
            'owner_id' => $author->id,
        ]);

        $this->actingAs($productManager)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('capabilities.manage_workflow', true)
                ->has('authors', 1)
                ->where('authors.0', [
                    'id' => $author->id,
                    'name' => 'Ada Author',
                    'role' => 'author',
                ])
                ->has('editorialStaff', 2)
                ->where('editorialStaff.0.id', $editor->id)
                ->where('editorialStaff.0.role', 'editor')
                ->where('editorialStaff.1.id', $lector->id)
                ->where('editorialStaff.1.role', 'lector'));
    }

    public function test_non_manager_does_not_receive_assignment_options(): void
    {
        $author = User::factory()->author()->create();
        $article = Article::factory()->authoring()->create([
            'owner_id' => $author->id,
            'author_id' => $author->id,
            'current_assignee_id' => $author->id,
        ]);

        $this->actingAs($author)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('capabilities.manage_workflow', false)
                ->where('capabilities.update_content', true)
                ->where('article.current_assignee.name', $author->name)
                ->missing('authors')
                ->missing('editorialStaff'));
    }

    public function test_editorial_assignee_receives_finish_action(): void
    {
        $editor = User::factory()->editor()->create();
        $article = Article::factory()->editorialWork()->create([
            'current_assignee_id' => $editor->id,
        ]);

        $this->actingAs($editor)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('capabilities.complete_editorial_work', true)
                ->where('allowedActions', ['complete_editorial_work']));
    }

    public function test_admin_receives_force_status_action_for_published_article(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->editor()->create();
        $article = Article::factory()->published()->create();

        $this->actingAs($admin)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('capabilities.force_status', true)
                ->where('allowedActions', ['force_status'])
                ->has('editorialStaff', 1));
    }

    public function test_editor_receives_workflow_events_with_reason(): void
    {
        $productManager = User::factory()->productManager()->create(['name' => 'Pat Manager']);
        $author = User::factory()->author()->create(['name' => 'Ada Author']);
        $article = Article::factory()->revisionRequested()->create([
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
            'owner_id' => $author->id,
            'current_assignee_id' => $author->id,
        ]);

        ArticleWorkflowEvent::factory()->create([
            'article_id' => $article->id,
            'from_status' => null,
            'to_status' => ArticleStatus::Planned,
            'actor_id' => $productManager->id,
            'assignee_id' => null,
            'reason' => null,
            'created_at' => now()->subDays(2),
        ]);

        ArticleWorkflowEvent::factory()->create([
            'article_id' => $article->id,
            'from_status' => ArticleStatus::ManuscriptSubmitted,
            'to_status' => ArticleStatus::RevisionRequested,
            'actor_id' => $productManager->id,
            'assignee_id' => $author->id,
            'reason' => 'Bitte Quellenangaben ergänzen.',
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($author)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('workflowEvents', 2)
                ->where('workflowEvents.0.to_status', ArticleStatus::Planned->value)
                ->where('workflowEvents.0.reason', null)
                ->where('workflowEvents.0.actor.name', 'Pat Manager')
                ->where('workflowEvents.1.from_status', ArticleStatus::ManuscriptSubmitted->value)
                ->where('workflowEvents.1.to_status', ArticleStatus::RevisionRequested->value)
                ->where('workflowEvents.1.reason', 'Bitte Quellenangaben ergänzen.')
                ->where('workflowEvents.1.actor.name', 'Pat Manager')
                ->where('workflowEvents.1.assignee.name', 'Ada Author'));
    }
}
