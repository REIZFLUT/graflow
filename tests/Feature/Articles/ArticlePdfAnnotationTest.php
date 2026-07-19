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

class ArticlePdfAnnotationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['article-pdf.disk' => 'local']);
    }

    public function test_owner_can_store_annotated_pdf_copy(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();
        $parentPdf = ArticlePdf::factory()
            ->forArticle($article)
            ->create([
                'owner_id' => $user->id,
                'kind' => ArticlePdfKind::Generated,
            ]);

        Storage::disk('local')->put($parentPdf->file_path, '%PDF-1.4 parent');

        $response = $this->actingAs($user)
            ->post(route('articles.pdfs.annotated.store', [
                'article' => $article,
                'pdf' => $parentPdf,
            ]), [
                'file' => UploadedFile::fake()->create('annotated.pdf', 32, 'application/pdf'),
            ]);

        $annotatedPdf = ArticlePdf::query()
            ->where('kind', ArticlePdfKind::Annotated)
            ->first();

        $this->assertNotNull($annotatedPdf);
        $response->assertRedirect(route('articles.pdfs.show', [
            'article' => $article,
            'pdf' => $annotatedPdf,
        ]));

        $this->assertDatabaseHas('article_pdfs', [
            'id' => $annotatedPdf->id,
            'parent_pdf_id' => $parentPdf->id,
            'kind' => ArticlePdfKind::Annotated->value,
        ]);

        Storage::disk('local')->assertExists($annotatedPdf->file_path);
    }

    public function test_product_manager_can_annotate_pdf_of_submitted_article(): void
    {
        $productManager = User::factory()->productManager()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'product_manager_id' => $productManager->id,
        ]);
        $parentPdf = ArticlePdf::factory()
            ->forArticle($article)
            ->create([
                'owner_id' => $productManager->id,
                'kind' => ArticlePdfKind::Generated,
            ]);

        Storage::disk('local')->put($parentPdf->file_path, '%PDF-1.4 parent');

        $this->actingAs($productManager)
            ->post(route('articles.pdfs.annotated.store', [
                'article' => $article,
                'pdf' => $parentPdf,
            ]), [
                'file' => UploadedFile::fake()->create('annotated.pdf', 32, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('article_pdfs', [
            'parent_pdf_id' => $parentPdf->id,
            'kind' => ArticlePdfKind::Annotated->value,
        ]);
    }

    public function test_other_user_cannot_store_annotated_pdf_copy(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->for($owner, 'owner')->create();
        $parentPdf = ArticlePdf::factory()
            ->forArticle($article)
            ->create([
                'owner_id' => $owner->id,
            ]);

        $this->actingAs($otherUser)
            ->post(route('articles.pdfs.annotated.store', [
                'article' => $article,
                'pdf' => $parentPdf,
            ]), [
                'file' => UploadedFile::fake()->create('annotated.pdf', 32, 'application/pdf'),
            ])
            ->assertForbidden();
    }
}
