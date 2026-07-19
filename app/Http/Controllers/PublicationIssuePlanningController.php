<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StorePlannedArticleRequest;
use App\Models\Publication;
use App\Models\PublicationIssue;
use App\Models\User;
use App\Services\ArticleWorkflowService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicationIssuePlanningController extends Controller
{
    public function __construct(
        private ArticleWorkflowService $workflow,
    ) {}

    public function show(Publication $publication, PublicationIssue $issue): Response
    {
        $this->authorize('update', $publication);
        $this->ensureIssueBelongsToPublication($publication, $issue);

        $issue->load([
            'chapters',
            'articles' => fn ($query) => $query
                ->with(['author:id,name', 'currentAssignee:id,name'])
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

        return Inertia::render('publication-issues/planning', [
            'publication' => $publication->only(['id', 'name']),
            'issue' => $issue,
            'authors' => User::query()
                ->where('role', '=', UserRole::Author->value)
                ->orderBy('name', 'asc')
                ->get(['id', 'name']),
        ]);
    }

    public function store(
        StorePlannedArticleRequest $request,
        Publication $publication,
        PublicationIssue $issue,
    ): RedirectResponse {
        $this->ensureIssueBelongsToPublication($publication, $issue);

        $validated = $request->validated();

        $this->workflow->plan(
            $request->user(),
            $issue,
            $validated['title'],
            User::query()->whereKey($validated['author_id'])->firstOrFail(),
            $validated['publication_chapter_id'] ?? null,
            $validated['position'],
            $validated['submission_deadline'],
            $validated['target_character_count'],
        );

        return to_route('publications.issues.planning.show', [$publication, $issue]);
    }

    private function ensureIssueBelongsToPublication(
        Publication $publication,
        PublicationIssue $issue,
    ): void {
        abort_unless($issue->publication_id === $publication->id, 404);
    }
}
