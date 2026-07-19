<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateArticleMetadataRequest;
use App\Models\Article;
use App\Models\EditorSettingsSet;
use App\Models\Publication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ArticleMetadataController extends Controller
{
    public function edit(Article $article): Response
    {
        $this->authorize('view', $article);

        $article->load([
            'editorSettingsSet',
            'publicationChapter',
            'publicationIssue.chapters',
            'publicationIssue.publication.editorSettingsSet',
            'publicationCategories',
        ]);

        $publications = Publication::query()
            ->visibleTo(auth()->user())
            ->with([
                'issues' => fn ($query) => $query
                    ->with('chapters')
                    ->orderByDesc('created_at'),
                'categories' => fn ($query) => $query->orderBy('name'),
                'editorSettingsSet',
            ])
            ->orderBy('name')
            ->get();

        $editorSettingsSets = auth()->user()->canManageEditorSettingsSets()
            ? EditorSettingsSet::query()
                ->where('owner_id', auth()->id())
                ->orderBy('name')
                ->get()
            : collect();

        $defaultEditorSettingsSet = $article->publicationIssue?->publication?->editorSettingsSet;

        return Inertia::render('articles/metadata', [
            'article' => $article,
            'publications' => $publications,
            'editorSettingsSets' => $editorSettingsSets,
            'defaultEditorSettingsSet' => $defaultEditorSettingsSet,
            'canEdit' => Gate::allows('manageWorkflow', $article),
        ]);
    }

    public function update(UpdateArticleMetadataRequest $request, Article $article): RedirectResponse
    {
        $publicationIssueId = $request->validated('publication_issue_id');
        $updateData = [
            'publication_issue_id' => $publicationIssueId,
            'publication_chapter_id' => $publicationIssueId === null
                ? null
                : $request->validated('publication_chapter_id'),
        ];

        if ($publicationIssueId !== null) {
            $updateData['position'] = $request->validated('position');
        }

        if ($request->user()->canManageEditorSettingsSets()) {
            $updateData['editor_settings_set_id'] = $request->validated('editor_settings_set_id');
        }

        $article->update($updateData);

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
