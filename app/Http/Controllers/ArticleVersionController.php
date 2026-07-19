<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleVersion;
use App\Services\ArticleVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ArticleVersionController extends Controller
{
    public function __construct(
        private ArticleVersionService $articleVersionService,
    ) {}

    public function restore(Article $article, ArticleVersion $version): RedirectResponse
    {
        $this->authorize('updateContent', $article);

        abort_unless($version->article_id === $article->id, 404);

        DB::transaction(function () use ($article, $version) {
            $article->update([
                'title' => $version->title,
                'content' => $version->content,
            ]);

            $this->articleVersionService->snapshot($article, auth()->user());
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.version_restored'),
        ]);

        return to_route('articles.edit', $article);
    }
}
