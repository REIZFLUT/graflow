<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnotatedArticlePdfRequest;
use App\Http\Requests\StoreArticlePdfRequest;
use App\Http\Resources\ArticlePdfResource;
use App\Models\Article;
use App\Models\ArticlePdf;
use App\Services\ArticlePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArticlePdfController extends Controller
{
    public function __construct(
        private ArticlePdfService $articlePdfService,
    ) {}

    public function index(Article $article): JsonResponse
    {
        $this->authorize('view', $article);

        $pdfs = $article->pdfs()
            ->latest()
            ->get();

        return response()->json([
            'data' => ArticlePdfResource::collection($pdfs)->resolve(),
        ]);
    }

    public function store(
        StoreArticlePdfRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->authorize('updateContent', $article);

        $pdf = $this->articlePdfService->storeGenerated(
            $article,
            $request->file('file'),
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.pdf_generated'),
        ]);

        return to_route('articles.pdfs.show', [
            'article' => $article,
            'pdf' => $pdf,
        ]);
    }

    public function show(Article $article, ArticlePdf $pdf): Response
    {
        abort_unless($pdf->article_id === $article->id, 404);

        $this->authorize('view', $pdf);

        $article->load([
            'pdfs' => fn ($query) => $query->latest(),
        ]);

        return Inertia::render('articles/pdf/show', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
            ],
            'pdf' => (new ArticlePdfResource($pdf))->resolve(),
            'pdfs' => ArticlePdfResource::collection($article->pdfs)->resolve(),
        ]);
    }

    public function file(Article $article, ArticlePdf $pdf): StreamedResponse
    {
        abort_unless($pdf->article_id === $article->id, 404);

        $this->authorize('view', $pdf);

        $disk = Storage::disk(config('article-pdf.disk'));

        abort_unless($disk->exists($pdf->file_path), 404);

        return $disk->response($pdf->file_path, basename($pdf->file_path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function storeAnnotated(
        StoreAnnotatedArticlePdfRequest $request,
        Article $article,
        ArticlePdf $pdf,
    ): RedirectResponse {
        abort_unless($pdf->article_id === $article->id, 404);

        $this->authorize('update', $pdf);

        $annotatedPdf = $this->articlePdfService->storeAnnotated(
            $pdf,
            $request->file('file'),
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.pdf_annotated_saved'),
        ]);

        return to_route('articles.pdfs.show', [
            'article' => $article,
            'pdf' => $annotatedPdf,
        ]);
    }
}
