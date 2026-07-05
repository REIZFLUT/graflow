<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateArticleMetadataRequest;
use App\Models\Article;
use App\Models\EditorSettingsSet;
use App\Models\Publication;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ArticleMetadataController extends Controller
{
    public function edit(Article $article): Response
    {
        $this->authorize('view', $article);

        $article->load([
            'editorSettingsSet',
            'publicationIssue.publication.editorSettingsSet',
            'publicationCategories',
        ]);

        $publications = Publication::query()
            ->where('owner_id', auth()->id())
            ->with([
                'issues' => fn ($query) => $query->orderByDesc('created_at'),
                'categories' => fn ($query) => $query->orderBy('name'),
                'editorSettingsSet',
            ])
            ->orderBy('name')
            ->get();

        $editorSettingsSets = EditorSettingsSet::query()
            ->where('owner_id', auth()->id())
            ->orderBy('name')
            ->get();

        $defaultEditorSettingsSet = $article->publicationIssue?->publication?->editorSettingsSet;

        return Inertia::render('articles/metadata', [
            'article' => $article,
            'publications' => $publications,
            'editorSettingsSets' => $editorSettingsSets,
            'defaultEditorSettingsSet' => $defaultEditorSettingsSet,
        ]);
    }

    public function update(UpdateArticleMetadataRequest $request, Article $article): RedirectResponse
    {
        $article->update([
            'publication_issue_id' => $request->validated('publication_issue_id'),
            'editor_settings_set_id' => $request->validated('editor_settings_set_id'),
        ]);

        $article->publicationCategories()->sync(
            $request->validated('publication_category_ids') ?? [],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.metadata_saved'),
        ]);

        return to_route('articles.metadata.edit', $article);
    }
}
