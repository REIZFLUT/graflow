<?php

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\ArticlePdf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticlePdfServeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['article-pdf.disk' => 'local']);
    }

    public function test_owner_can_view_pdf_page_and_download_file(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();
        $pdf = ArticlePdf::factory()
            ->forArticle($article)
            ->create([
                'owner_id' => $user->id,
            ]);

        Storage::disk('local')->put($pdf->file_path, '%PDF-1.4 test');

        $this->actingAs($user)
            ->get(route('articles.pdfs.show', [
                'article' => $article,
                'pdf' => $pdf,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('articles/pdf/show')
                ->where('pdf.id', $pdf->id));

        $this->actingAs($user)
            ->get(route('articles.pdfs.file', [
                'article' => $article,
                'pdf' => $pdf,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_other_user_cannot_download_pdf_file(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = Article::factory()->for($owner, 'owner')->create();
        $pdf = ArticlePdf::factory()
            ->forArticle($article)
            ->create([
                'owner_id' => $owner->id,
            ]);

        Storage::disk('local')->put($pdf->file_path, '%PDF-1.4 test');

        $this->actingAs($otherUser)
            ->get(route('articles.pdfs.file', [
                'article' => $article,
                'pdf' => $pdf,
            ]))
            ->assertForbidden();
    }
}
