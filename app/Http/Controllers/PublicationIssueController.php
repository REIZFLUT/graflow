<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicationIssueRequest;
use App\Http\Requests\UpdatePublicationIssueRequest;
use App\Models\Publication;
use App\Models\PublicationIssue;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PublicationIssueController extends Controller
{
    public function store(StorePublicationIssueRequest $request, Publication $publication): RedirectResponse
    {
        $this->authorize('update', $publication);

        $publication->issues()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.issues.created'),
        ]);

        return to_route('publications.edit', $publication);
    }

    public function update(
        UpdatePublicationIssueRequest $request,
        Publication $publication,
        PublicationIssue $issue,
    ): RedirectResponse {
        $this->authorize('update', $publication);

        abort_unless($issue->publication_id === $publication->id, 404);

        $issue->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.issues.saved'),
        ]);

        return to_route('publications.edit', $publication);
    }

    public function destroy(Publication $publication, PublicationIssue $issue): RedirectResponse
    {
        $this->authorize('update', $publication);

        abort_unless($issue->publication_id === $publication->id, 404);

        $issue->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.issues.deleted'),
        ]);

        return to_route('publications.edit', $publication);
    }
}
