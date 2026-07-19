<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Http\Requests\ArticleWorkflowReasonRequest;
use App\Http\Requests\AssignArticleWorkflowRequest;
use App\Http\Requests\ForceArticleStatusRequest;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleWorkflowService;
use Illuminate\Http\RedirectResponse;

class ArticleWorkflowController extends Controller
{
    public function __construct(
        private ArticleWorkflowService $workflow,
    ) {}

    public function assignAuthor(
        AssignArticleWorkflowRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->assignAuthor(
            $article,
            $request->user(),
            User::query()->findOrFail($request->integer('assignee_id')),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function assignEditorial(
        AssignArticleWorkflowRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->assignEditorial(
            $article,
            $request->user(),
            User::query()->findOrFail($request->integer('assignee_id')),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function submitManuscript(
        ArticleWorkflowReasonRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->submitManuscript(
            $article,
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function completeEditorialWork(
        ArticleWorkflowReasonRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->completeEditorialWork(
            $article,
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function forceStatus(
        ForceArticleStatusRequest $request,
        Article $article,
    ): RedirectResponse {
        $assignee = $request->filled('assignee_id')
            ? User::query()->findOrFail($request->integer('assignee_id'))
            : null;

        $this->workflow->forceStatus(
            $article,
            $request->user(),
            ArticleStatus::from($request->string('status')->toString()),
            $assignee,
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function requestRevision(
        ArticleWorkflowReasonRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->requestRevision(
            $article,
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function startProductManagerCorrection(
        ArticleWorkflowReasonRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->startProductManagerCorrection(
            $article,
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function completeProductManagerCorrection(
        ArticleWorkflowReasonRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->completeProductManagerCorrection(
            $article,
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function returnToAuthor(
        ArticleWorkflowReasonRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->returnToAuthor(
            $article,
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function unpublish(
        ArticleWorkflowReasonRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->unpublish(
            $article,
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function recall(
        ArticleWorkflowReasonRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->recallToManuscript(
            $article,
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function markReady(
        ArticleWorkflowReasonRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->markReady(
            $article,
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }

    public function publish(
        ArticleWorkflowReasonRequest $request,
        Article $article,
    ): RedirectResponse {
        $this->workflow->publish(
            $article,
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('articles.edit', $article);
    }
}
