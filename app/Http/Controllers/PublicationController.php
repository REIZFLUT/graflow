<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicationRequest;
use App\Http\Requests\UpdatePublicationRequest;
use App\Models\EditorSettingsSet;
use App\Models\Publication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PublicationController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Publication::class);

        $user = auth()->user();

        $publications = Publication::query()
            ->visibleTo($user)
            ->with([
                'owner:id,name',
                'issues' => fn ($query) => $query
                    ->select(['id', 'publication_id', 'label'])
                    ->orderByDesc('id'),
            ])
            ->withCount('issues')
            ->latest()
            ->paginate(15)
            ->through(fn (Publication $publication): Publication => $publication->setAttribute(
                'can_edit',
                $publication->owner_id === $user->id,
            ));

        return Inertia::render('publications/index', [
            'publications' => $publications,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Publication::class);

        return Inertia::render('publications/create', [
            'editorSettingsSets' => auth()->user()->canManageEditorSettingsSets()
                ? $this->availableEditorSettingsSets()
                : collect(),
        ]);
    }

    public function store(StorePublicationRequest $request): RedirectResponse
    {
        $this->authorize('create', Publication::class);

        $publication = Publication::query()->create([
            ...$request->validated(),
            'owner_id' => $request->user()->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.publications.created'),
        ]);

        return to_route('publications.edit', $publication);
    }

    public function edit(Publication $publication): Response
    {
        $this->authorize('view', $publication);

        $canEdit = $publication->owner_id === auth()->id();

        $publication->load([
            'owner:id,name',
            'issues' => fn ($query) => $query->orderByDesc('created_at'),
            'categories' => fn ($query) => $query->orderBy('name'),
            'editorSettingsSet',
        ]);

        $publication->setAttribute('can_edit', $canEdit);

        return Inertia::render('publications/edit', [
            'publication' => $publication,
            'editorSettingsSets' => $canEdit && auth()->user()->canManageEditorSettingsSets()
                ? $this->availableEditorSettingsSets()
                : collect(),
        ]);
    }

    public function update(UpdatePublicationRequest $request, Publication $publication): RedirectResponse
    {
        $this->authorize('update', $publication);

        $publication->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.publications.saved'),
        ]);

        return to_route('publications.edit', $publication);
    }

    public function destroy(Publication $publication): RedirectResponse
    {
        $this->authorize('delete', $publication);

        $publication->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.publications.deleted'),
        ]);

        return to_route('publications.index');
    }

    /**
     * @return Collection<int, EditorSettingsSet>
     */
    private function availableEditorSettingsSets(): Collection
    {
        return EditorSettingsSet::query()
            ->where('owner_id', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'font', 'has_marginal_column']);
    }
}
