<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicationCategoryRequest;
use App\Http\Requests\UpdatePublicationCategoryRequest;
use App\Models\Publication;
use App\Models\PublicationCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PublicationCategoryController extends Controller
{
    public function store(StorePublicationCategoryRequest $request, Publication $publication): RedirectResponse
    {
        $this->authorize('update', $publication);

        $publication->categories()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.categories.created'),
        ]);

        return to_route('publications.edit', $publication);
    }

    public function update(
        UpdatePublicationCategoryRequest $request,
        Publication $publication,
        PublicationCategory $category,
    ): RedirectResponse {
        $this->authorize('update', $publication);

        abort_unless($category->publication_id === $publication->id, 404);

        $category->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.categories.saved'),
        ]);

        return to_route('publications.edit', $publication);
    }

    public function destroy(Publication $publication, PublicationCategory $category): RedirectResponse
    {
        $this->authorize('update', $publication);

        abort_unless($category->publication_id === $publication->id, 404);

        $category->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.categories.deleted'),
        ]);

        return to_route('publications.edit', $publication);
    }
}
