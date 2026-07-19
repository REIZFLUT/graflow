<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicationChapterRequest;
use App\Http\Requests\UpdatePublicationChapterRequest;
use App\Models\Publication;
use App\Models\PublicationChapter;
use App\Models\PublicationIssue;
use Illuminate\Http\RedirectResponse;

class PublicationChapterController extends Controller
{
    public function store(
        StorePublicationChapterRequest $request,
        Publication $publication,
        PublicationIssue $issue,
    ): RedirectResponse {
        $this->ensureIssueBelongsToPublication($publication, $issue);
        $issue->chapters()->create($request->validated());

        return to_route('publications.issues.planning.show', [$publication, $issue]);
    }

    public function update(
        UpdatePublicationChapterRequest $request,
        Publication $publication,
        PublicationIssue $issue,
        PublicationChapter $chapter,
    ): RedirectResponse {
        $this->ensureScoped($publication, $issue, $chapter);
        $chapter->update($request->validated());

        return to_route('publications.issues.planning.show', [$publication, $issue]);
    }

    public function destroy(
        Publication $publication,
        PublicationIssue $issue,
        PublicationChapter $chapter,
    ): RedirectResponse {
        $this->authorize('update', $publication);
        $this->ensureScoped($publication, $issue, $chapter);
        PublicationChapter::query()->whereKey($chapter->id)->delete();

        return to_route('publications.issues.planning.show', [$publication, $issue]);
    }

    private function ensureIssueBelongsToPublication(
        Publication $publication,
        PublicationIssue $issue,
    ): void {
        abort_unless($issue->publication_id === $publication->id, 404);
    }

    private function ensureScoped(
        Publication $publication,
        PublicationIssue $issue,
        PublicationChapter $chapter,
    ): void {
        $this->ensureIssueBelongsToPublication($publication, $issue);
        abort_unless($chapter->publication_issue_id === $issue->id, 404);
    }
}
