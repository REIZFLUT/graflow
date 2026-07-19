<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleCommentThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ArticleCommentController extends Controller
{
    public function store(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('comment', $article);

        $validated = $request->validate([
            'id' => ['required', 'uuid', 'unique:article_comment_threads,id'],
            'body' => ['required', 'string', 'max:5000'],
            'anchor_text' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'array'],
        ]);

        DB::transaction(function () use ($validated, $article, $request): void {
            $article->update(['content' => $validated['content']]);

            $thread = $article->commentThreads()->create([
                'id' => $validated['id'],
                'created_by_id' => $request->user()->id,
                'anchor_text' => $validated['anchor_text'] ?? null,
            ]);

            $thread->comments()->create([
                'user_id' => $request->user()->id,
                'body' => $validated['body'],
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.comment_added'),
        ]);

        return to_route('articles.edit', $article);
    }

    public function reply(Request $request, Article $article, ArticleCommentThread $thread): RedirectResponse
    {
        $this->authorize('comment', $article);

        abort_unless($thread->article_id === $article->id, 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $thread->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.comment_replied'),
        ]);

        return to_route('articles.edit', $article);
    }

    public function resolve(Request $request, Article $article, ArticleCommentThread $thread): RedirectResponse
    {
        $this->authorize('comment', $article);

        abort_unless($thread->article_id === $article->id, 404);

        $thread->update([
            'resolved_at' => now(),
            'resolved_by_id' => $request->user()->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.comment_resolved'),
        ]);

        return to_route('articles.edit', $article);
    }

    public function reopen(Article $article, ArticleCommentThread $thread): RedirectResponse
    {
        $this->authorize('comment', $article);

        abort_unless($thread->article_id === $article->id, 404);

        $thread->update([
            'resolved_at' => null,
            'resolved_by_id' => null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.comment_reopened'),
        ]);

        return to_route('articles.edit', $article);
    }
}
