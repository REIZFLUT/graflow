<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Enums\PublicationEditorFont;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleMediaResource;
use App\Models\Article;
use App\Services\ArticleMediaService;
use App\Services\ArticleVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function __construct(
        private ArticleVersionService $articleVersionService,
        private ArticleMediaService $articleMediaService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Article::class);

        $articles = Article::query()
            ->where('owner_id', auth()->id())
            ->with('publicationIssue.publication')
            ->latest()
            ->paginate(15);

        return Inertia::render('articles/index', [
            'articles' => $articles,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Article::class);

        return Inertia::render('articles/create', [
            'editorSettings' => $this->defaultEditorSettings(),
        ]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $this->authorize('create', Article::class);

        $article = DB::transaction(function () use ($request) {
            $article = Article::query()->create([
                'title' => $request->validated('title'),
                'content' => $request->validated('content'),
                'owner_id' => $request->user()->id,
                'status' => ArticleStatus::Draft,
            ]);

            $this->articleVersionService->snapshot($article, $request->user());

            $this->articleMediaService->claimStagingMedia(
                $article,
                $request->validated('staging_token'),
                $request->user(),
            );

            return $article;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.created'),
        ]);

        return to_route('articles.edit', $article);
    }

    public function edit(Article $article): Response
    {
        $this->authorize('view', $article);

        $article->load([
            'editorSettingsSet',
            'publicationIssue.publication.editorSettingsSet',
            'versions' => fn ($query) => $query
                ->with('createdBy:id,name')
                ->orderByDesc('version_number'),
            'media' => fn ($query) => $query->latest(),
        ]);

        return Inertia::render('articles/edit', [
            'article' => array_merge($article->toArray(), [
                'media' => ArticleMediaResource::collection($article->media)->resolve(),
            ]),
            'editorSettings' => $this->editorSettingsForArticle($article),
        ]);
    }

    /**
     * @return array{font: string, has_marginal_column: bool}
     */
    private function defaultEditorSettings(): array
    {
        return [
            'font' => PublicationEditorFont::Spectral->value,
            'has_marginal_column' => true,
        ];
    }

    /**
     * @return array{font: string, has_marginal_column: bool}
     */
    private function editorSettingsForArticle(Article $article): array
    {
        $publication = $article->publicationIssue?->publication;
        $editorSettingsSet = $article->editorSettingsSet
            ?? $publication?->editorSettingsSet;

        if ($editorSettingsSet === null) {
            return $this->defaultEditorSettings();
        }

        return [
            'font' => $editorSettingsSet->font->value,
            'has_marginal_column' => $editorSettingsSet->has_marginal_column,
        ];
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);

        DB::transaction(function () use ($request, $article) {
            $article->update($request->validated());

            $this->articleVersionService->snapshot($article, $request->user());
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.saved'),
        ]);

        return to_route('articles.edit', $article);
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        $article->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.deleted'),
        ]);

        return to_route('articles.index');
    }
}
