<?php

namespace Tests\Feature\Publications;

use App\Models\Article;
use App\Models\Publication;
use App\Models\PublicationCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_category(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->post(route('publications.categories.store', $publication), [
                'name' => 'Markt & Politik',
            ])
            ->assertRedirect(route('publications.edit', $publication));

        $this->assertDatabaseHas('publication_categories', [
            'publication_id' => $publication->id,
            'name' => 'Markt & Politik',
        ]);
    }

    public function test_user_can_update_category(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $category = PublicationCategory::factory()->for($publication)->create([
            'name' => 'Technik',
        ]);

        $this->actingAs($user)
            ->patch(route('publications.categories.update', [
                'publication' => $publication,
                'category' => $category,
            ]), [
                'name' => 'Technologie',
            ])
            ->assertRedirect(route('publications.edit', $publication));

        $this->assertDatabaseHas('publication_categories', [
            'id' => $category->id,
            'name' => 'Technologie',
        ]);
    }

    public function test_user_can_delete_category(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $category = PublicationCategory::factory()->for($publication)->create();

        $this->actingAs($user)
            ->delete(route('publications.categories.destroy', [
                'publication' => $publication,
                'category' => $category,
            ]))
            ->assertRedirect(route('publications.edit', $publication));

        $this->assertDatabaseMissing('publication_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_deleting_category_detaches_articles(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $category = PublicationCategory::factory()->for($publication)->create();
        $article = Article::factory()->for($user, 'owner')->create();
        $article->publicationCategories()->attach($category);

        $this->actingAs($user)
            ->delete(route('publications.categories.destroy', [
                'publication' => $publication,
                'category' => $category,
            ]))
            ->assertRedirect();

        $this->assertDatabaseMissing('article_publication_category', [
            'article_id' => $article->id,
            'publication_category_id' => $category->id,
        ]);
    }

    public function test_category_name_must_be_unique_within_publication(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        PublicationCategory::factory()->for($publication)->create(['name' => 'Technik']);

        $this->actingAs($user)
            ->post(route('publications.categories.store', $publication), [
                'name' => 'Technik',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_user_cannot_manage_categories_on_another_users_publication(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $publication = Publication::factory()->for($owner, 'owner')->create();

        $this->actingAs($otherUser)
            ->post(route('publications.categories.store', $publication), [
                'name' => 'Technik',
            ])
            ->assertForbidden();
    }

    public function test_category_must_belong_to_publication_in_route(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'owner')->create();
        $otherPublication = Publication::factory()->for($user, 'owner')->create();
        $category = PublicationCategory::factory()->for($otherPublication)->create();

        $this->actingAs($user)
            ->patch(route('publications.categories.update', [
                'publication' => $publication,
                'category' => $category,
            ]), [
                'name' => 'Technik',
            ])
            ->assertNotFound();
    }
}
