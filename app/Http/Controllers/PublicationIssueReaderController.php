<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleMediaResource;
use App\Models\Article;
use App\Models\Publication;
use App\Models\PublicationIssue;
use App\Support\ArticleEditorSettingsResolver;
use Inertia\Inertia;
use Inertia\Response;

class PublicationIssueReaderController extends Controller
{
    public function __construct(
        private ArticleEditorSettingsResolver $editorSettingsResolver,
    ) {}

    public function show(Publication $publication, PublicationIssue $issue): Response
    {
        $this->authorize('view', $publication);
        $this->ensureIssueBelongsToPublication($publication, $issue);

        $issue->load([
            'chapters' => fn ($query) => $query->orderBy('position'),
            'articles' => fn ($query) => $query
                ->with([
                    'author:id,name',
                    'currentAssignee:id,name',
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
            'status' => $article->status->value,
            'author' => $article->author !== null
                ? ['id' => $article->author->id, 'name' => $article->author->name]
                : null,
            'publication_chapter_id' => $article->publication_chapter_id,
            'position' => $article->position,
            'content' => $article->content,
            'media' => ArticleMediaResource::collection($article->media)->resolve(),
            'editor_settings' => $this->editorSettingsResolver->resolve($article),
        ]);

        return Inertia::render('publication-issues/reader', [
            'publication' => $publication->only(['id', 'name']),
            'issue' => $issue->only(['id', 'label', 'publication_id']),
            'chapters' => $issue->chapters->map->only(['id', 'title', 'position'])->values(),
            'articles' => $articles->values(),
        ]);
    }

    private function ensureIssueBelongsToPublication(
        Publication $publication,
        PublicationIssue $issue,
    ): void {
        abort_unless($issue->publication_id === $publication->id, 404);
    }
}
