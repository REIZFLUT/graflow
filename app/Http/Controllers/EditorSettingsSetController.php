<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEditorSettingsSetRequest;
use App\Http\Requests\UpdateEditorSettingsSetRequest;
use App\Models\EditorSettingsSet;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EditorSettingsSetController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', EditorSettingsSet::class);

        $editorSettingsSets = EditorSettingsSet::query()
            ->where('owner_id', auth()->id())
            ->withCount(['publications', 'articles'])
            ->latest()
            ->paginate(15);

        return Inertia::render('editor-settings-sets/index', [
            'editorSettingsSets' => $editorSettingsSets,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', EditorSettingsSet::class);

        return Inertia::render('editor-settings-sets/create');
    }

    public function store(StoreEditorSettingsSetRequest $request): RedirectResponse
    {
        $this->authorize('create', EditorSettingsSet::class);

        $editorSettingsSet = EditorSettingsSet::query()->create([
            ...$request->validated(),
            'owner_id' => $request->user()->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.editor_settings_sets.created'),
        ]);

        return to_route('editor-settings-sets.edit', $editorSettingsSet);
    }

    public function edit(EditorSettingsSet $editorSettingsSet): Response
    {
        $this->authorize('view', $editorSettingsSet);

        $editorSettingsSet->loadCount(['publications', 'articles']);

        return Inertia::render('editor-settings-sets/edit', [
            'editorSettingsSet' => $editorSettingsSet,
        ]);
    }

    public function update(
        UpdateEditorSettingsSetRequest $request,
        EditorSettingsSet $editorSettingsSet,
    ): RedirectResponse {
        $this->authorize('update', $editorSettingsSet);

        $editorSettingsSet->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.editor_settings_sets.saved'),
        ]);

        return to_route('editor-settings-sets.edit', $editorSettingsSet);
    }

    public function destroy(EditorSettingsSet $editorSettingsSet): RedirectResponse
    {
        $this->authorize('delete', $editorSettingsSet);

        if ($editorSettingsSet->publications()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('messages.editor_settings_sets.in_use_publications'),
            ]);

            return to_route('editor-settings-sets.edit', $editorSettingsSet);
        }

        if ($editorSettingsSet->articles()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('messages.editor_settings_sets.in_use_articles'),
            ]);

            return to_route('editor-settings-sets.edit', $editorSettingsSet);
        }

        $editorSettingsSet->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.editor_settings_sets.deleted'),
        ]);

        return to_route('editor-settings-sets.index');
    }
}
