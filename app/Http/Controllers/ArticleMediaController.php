<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArticleMediaRequest;
use App\Http\Requests\UpdateArticleMediaRequest;
use App\Http\Resources\ArticleMediaResource;
use App\Models\Article;
use App\Models\ArticleMedia;
use App\Services\ArticleMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArticleMediaController extends Controller
{
    public function __construct(
        private ArticleMediaService $articleMediaService,
    ) {}

    public function indexStaging(Request $request): JsonResponse
    {
        $stagingToken = $request->query('staging_token');

        abort_unless(is_string($stagingToken) && $stagingToken !== '', 422);

        $media = ArticleMedia::query()
            ->where('staging_token', $stagingToken)
            ->where('owner_id', $request->user()->id)
            ->whereNull('article_id')
            ->latest()
            ->get();

        return response()->json([
            'data' => ArticleMediaResource::collection($media),
        ]);
    }

    public function storeStaging(StoreArticleMediaRequest $request): JsonResponse
    {
        $media = $this->articleMediaService->storeStaging(
            $request->file('file'),
            $request->user(),
            $request->validated('staging_token'),
            [
                'alt_text' => $request->validated('alt_text'),
                'copyright' => $request->validated('copyright'),
                'caption' => $request->validated('caption'),
            ],
        );

        return response()->json([
            'data' => new ArticleMediaResource($media),
        ], 201);
    }

    public function updateStaging(UpdateArticleMediaRequest $request, ArticleMedia $media): JsonResponse
    {
        $this->authorize('update', $media);
        abort_unless($media->isStaging(), 404);

        $media = $this->articleMediaService->updateMetadata($media, $request->validated());

        return response()->json([
            'data' => new ArticleMediaResource($media),
        ]);
    }

    public function destroyStaging(ArticleMedia $media): JsonResponse
    {
        $this->authorize('delete', $media);
        abort_unless($media->isStaging(), 404);

        $this->articleMediaService->delete($media);

        return response()->json(null, 204);
    }

    public function serveStagingFile(ArticleMedia $media, string $variant): StreamedResponse
    {
        $this->authorize('view', $media);
        abort_unless($media->isStaging(), 404);

        return $this->streamMediaFile($media, $variant);
    }

    public function index(Article $article): JsonResponse
    {
        $this->authorize('view', $article);

        $media = $article->media()->latest()->get();

        return response()->json([
            'data' => ArticleMediaResource::collection($media),
        ]);
    }

    public function store(StoreArticleMediaRequest $request, Article $article): JsonResponse
    {
        $this->authorize('updateContent', $article);

        $media = $this->articleMediaService->storeForArticle(
            $request->file('file'),
            $article,
            $request->user(),
            [
                'alt_text' => $request->validated('alt_text'),
                'copyright' => $request->validated('copyright'),
                'caption' => $request->validated('caption'),
            ],
        );

        return response()->json([
            'data' => new ArticleMediaResource($media),
        ], 201);
    }

    public function update(UpdateArticleMediaRequest $request, Article $article, ArticleMedia $media): JsonResponse
    {
        $this->authorize('updateContent', $article);
        abort_unless($media->article_id === $article->id, 404);

        $media = $this->articleMediaService->updateMetadata($media, $request->validated());

        return response()->json([
            'data' => new ArticleMediaResource($media),
        ]);
    }

    public function destroy(Article $article, ArticleMedia $media): JsonResponse
    {
        $this->authorize('updateContent', $article);
        abort_unless($media->article_id === $article->id, 404);

        if ($this->articleMediaService->isReferencedInContent($media, $article)) {
            return response()->json([
                'message' => __('messages.articles.image_in_use'),
            ], 422);
        }

        $this->articleMediaService->delete($media);

        return response()->json(null, 204);
    }

    public function serveFile(Article $article, ArticleMedia $media, string $variant): StreamedResponse
    {
        $this->authorize('view', $article);
        abort_unless($media->article_id === $article->id, 404);

        return $this->streamMediaFile($media, $variant);
    }

    private function streamMediaFile(ArticleMedia $media, string $variant): StreamedResponse
    {
        $path = $this->articleMediaService->resolveFilePath($media, $variant);
        $disk = Storage::disk(config('article-media.disk'));

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
