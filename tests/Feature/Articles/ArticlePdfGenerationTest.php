<?php

namespace Tests\Feature\Articles;

use App\Enums\ArticlePdfKind;
use App\Models\Article;
use App\Models\ArticlePdf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticlePdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config([
            'article-pdf.disk' => 'local',
            'article-media.disk' => 'local',
        ]);
    }

    public function test_owner_can_store_client_generated_article_pdf(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($user)
            ->post(route('articles.pdfs.store', $article), [
                'file' => UploadedFile::fake()->create('article.pdf', 100, 'application/pdf'),
            ]);

        $pdf = ArticlePdf::query()->first();

        $this->assertNotNull($pdf);
        $response->assertRedirect(route('articles.pdfs.show', [
            'article' => $article,
            'pdf' => $pdf,
        ]));

        $this->assertDatabaseHas('article_pdfs', [
            'article_id' => $article->id,
            'owner_id' => $user->id,
            'kind' => ArticlePdfKind::Generated->value,
        ]);

        Storage::disk('local')->assertExists($pdf->file_path);
    }

    public function test_store_requires_pdf_file(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->post(route('articles.pdfs.store', $article), [])
            ->assertSessionHasErrors('file');
    }

    public function test_product_manager_can_store_pdf_for_submitted_article_without_being_assignee(): void
    {
        $productManager = User::factory()->productManager()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'product_manager_id' => $productManager->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.pdfs.store', $article), [
                'file' => UploadedFile::fake()->create('fahne.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('article_pdfs', [
            'article_id' => $article->id,
            'owner_id' => $productManager->id,
        ]);
    }

    public function test_former_participant_can_store_pdf_of_published_article(): void
    {
        $editor = User::factory()->editor()->create();
        $article = Article::factory()->published()->create();
        $article->participants()->create([
            'user_id' => $editor->id,
            'process_role' => $editor->role->value,
        ]);

        $this->actingAs($editor)
            ->post(route('articles.pdfs.store', $article), [
                'file' => UploadedFile::fake()->create('archiv.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();
    }

    public function test_other_user_cannot_store_article_pdf(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->for($owner, 'owner')->create();

        $this->actingAs($otherUser)
            ->post(route('articles.pdfs.store', $article), [
                'file' => UploadedFile::fake()->create('article.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
    }
}
