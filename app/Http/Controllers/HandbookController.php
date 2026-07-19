<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreHandbookArticleRequest;
use App\Http\Resources\ArticleMediaResource;
use App\Models\Article;
use App\Support\ArticleEditorSettingsResolver;
use App\Support\Handbook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HandbookController extends Controller
{
    public function __construct(
        private ArticleEditorSettingsResolver $editorSettingsResolver,
    ) {}

    public function show(Request $request): Response
    {
        $canManage = $request->user()?->role === UserRole::Admin;

        $issue = Handbook::resolveIssue();

        if ($issue === null) {
            return Inertia::render('handbook/reader', [
                'title' => Handbook::name(),
                'chapters' => [],
                'articles' => [],
                'canManage' => $canManage,
            ]);
        }

        $issue->load([
            'chapters' => fn ($query) => $query->orderBy('position'),
            'articles' => fn ($query) => $query
                ->with([
                    'editorSettingsSet',
                    'publicationIssue.publication.editorSettingsSet',
                    'media' => fn ($media) => $media->latest(),
                ])
                ->reorder()
                ->leftJoin(
                    'publication_chapters',
                    'publication_chapters.id',
                    '=',
                    'articles.publication_chapter_id',
                )
                ->select('articles.*')
                ->orderBy('publication_chapters.position')
                ->orderBy('articles.position')
                ->orderBy('articles.id'),
        ]);

        $articles = $issue->articles->map(fn (Article $article): array => [
            'id' => $article->id,
            'title' => $article->title,
            'publication_chapter_id' => $article->publication_chapter_id,
            'position' => $article->position,
            'content' => $article->content,
            'media' => ArticleMediaResource::collection($article->media)->resolve(),
            'editor_settings' => $this->editorSettingsResolver->resolve($article),
        ]);

        return Inertia::render('handbook/reader', [
            'title' => $issue->publication->name,
            'chapters' => $issue->chapters->map->only(['id', 'title', 'position'])->values(),
            'articles' => $articles->values(),
            'canManage' => $canManage,
        ]);
    }

    public function storeArticle(StoreHandbookArticleRequest $request): RedirectResponse
    {
        $issue = Handbook::resolveIssue();

        abort_if($issue === null, 404);

        $validated = $request->validated();
        $chapterId = $validated['publication_chapter_id'] ?? null;
        $userId = $request->user()->id;

        $position = (int) Article::query()
            ->where('publication_issue_id', $issue->id)
            ->when(
                $chapterId !== null,
                fn ($query) => $query->where('publication_chapter_id', $chapterId),
                fn ($query) => $query->whereNull('publication_chapter_id'),
            )
            ->max('position');

        $article = Article::query()->create([
            'title' => $validated['title'],
            'content' => ['type' => 'doc', 'content' => []],
            'owner_id' => $userId,
            'product_manager_id' => $userId,
            'author_id' => $userId,
            'current_assignee_id' => $userId,
            'status' => ArticleStatus::Authoring,
            'publication_issue_id' => $issue->id,
            'publication_chapter_id' => $chapterId,
            'position' => $position + 1,
            'editor_settings_set_id' => $issue->publication->editor_settings_set_id,
        ]);

        $article->participants()->updateOrCreate(
            ['user_id' => $userId],
            ['process_role' => $request->user()->role->value],
        );

        return to_route('articles.edit', $article);
    }
}
