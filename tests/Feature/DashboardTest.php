<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Publication;
use App\Models\PublicationIssue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->editor()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('stats', fn ($stats) => $stats
                ->has('articles')
                ->has('publications')
                ->has('editorSettingsSets')
            )
        );
    }

    public function test_author_dashboard_does_not_include_editor_settings_stats()
    {
        $author = User::factory()->author()->create();
        $this->actingAs($author);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('stats', fn ($stats) => $stats
                ->has('articles')
                ->has('publications')
                ->missing('editorSettingsSets')
            )
        );
    }

    public function test_author_dashboard_only_counts_own_articles_and_contributed_publications(): void
    {
        $author = User::factory()->author()->create();
        $otherAuthor = User::factory()->author()->create();
        $editor = User::factory()->editor()->create();

        Article::factory()->count(2)->for($author, 'owner')->create();
        Article::factory()->count(3)->for($otherAuthor, 'owner')->create();

        $contributedPublication = Publication::factory()->for($editor, 'owner')->create();
        $otherPublication = Publication::factory()->for($editor, 'owner')->create();

        $contributedIssue = PublicationIssue::factory()
            ->for($contributedPublication)
            ->create();
        PublicationIssue::factory()
            ->for($otherPublication)
            ->create();

        Article::factory()
            ->for($author, 'owner')
            ->for($contributedIssue, 'publicationIssue')
            ->create();

        Article::factory()
            ->for($otherAuthor, 'owner')
            ->for($otherPublication->issues()->first(), 'publicationIssue')
            ->create();

        $this->actingAs($author)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('stats.articles', 3)
                ->where('stats.publications', 1)
                ->missing('stats.editorSettingsSets')
            );
    }
}
