<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Events\ArticleStatusChanged;
use App\Models\Article;
use App\Models\PublicationIssue;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArticleWorkflowService
{
    public function plan(
        User $actor,
        PublicationIssue $publicationIssue,
        string $title,
        User $author,
        ?int $publicationChapterId,
        int $position,
        string $submissionDeadline,
        int $targetCharacterCount,
    ): Article {
        $this->ensureProductManager($actor);
        $this->ensureRole($author, UserRole::Author, 'author_id');

        if ($position < 1) {
            throw ValidationException::withMessages([
                'position' => __('The position must be at least 1.'),
            ]);
        }

        return DB::transaction(function () use (
            $actor,
            $publicationIssue,
            $title,
            $author,
            $publicationChapterId,
            $position,
            $submissionDeadline,
            $targetCharacterCount,
        ): Article {
            $article = Article::query()->create([
                'title' => $title,
                'content' => ['type' => 'doc', 'content' => []],
                'owner_id' => $author->id,
                'product_manager_id' => $actor->id,
                'author_id' => $author->id,
                'current_assignee_id' => null,
                'status' => ArticleStatus::Planned,
                'publication_issue_id' => $publicationIssue->id,
                'publication_chapter_id' => $publicationChapterId,
                'position' => $position,
                'submission_deadline' => $submissionDeadline,
                'target_character_count' => $targetCharacterCount,
            ]);

            $this->recordParticipant($article, $actor);
            $this->recordParticipant($article, $author);
            $this->recordEvent($article, null, ArticleStatus::Planned, $actor, $author);

            ArticleStatusChanged::dispatch($article, null, ArticleStatus::Planned, $actor, null);

            return $article->refresh();
        });
    }

    public function assignAuthor(
        Article $article,
        User $actor,
        User $author,
        ?string $reason = null,
    ): Article {
        $this->ensureRole($author, UserRole::Author, 'assignee_id');

        return DB::transaction(function () use ($article, $actor, $author, $reason): Article {
            $lockedArticle = $this->lock($article);
            $this->ensureWorkflowManager($lockedArticle, $actor);

            if (! in_array($lockedArticle->status, [
                ArticleStatus::Planned,
                ArticleStatus::ManuscriptSubmitted,
                ArticleStatus::RevisionRequested,
                ArticleStatus::ReadyForPublication,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => __('This workflow transition is not allowed.'),
                ]);
            }

            $to = $lockedArticle->status === ArticleStatus::Planned
                ? ArticleStatus::Authoring
                : ArticleStatus::Revision;

            $lockedArticle->author_id = $author->id;
            $lockedArticle->owner_id = $author->id;

            return $this->applyTransition($lockedArticle, $actor, $to, $author, $reason);
        });
    }

    public function assignEditorial(
        Article $article,
        User $actor,
        User $assignee,
        ?string $reason = null,
    ): Article {
        if (! in_array($assignee->role, [UserRole::Editor, UserRole::Lector], true)) {
            throw ValidationException::withMessages([
                'assignee_id' => __('The assignee must be an editor or lector.'),
            ]);
        }

        return $this->transition(
            $article,
            $actor,
            [ArticleStatus::ManuscriptSubmitted, ArticleStatus::RevisionRequested, ArticleStatus::ReadyForPublication],
            ArticleStatus::EditorialWork,
            $assignee,
            $reason,
        );
    }

    public function forceStatus(
        Article $article,
        User $actor,
        ArticleStatus $status,
        ?User $assignee = null,
        ?string $reason = null,
    ): Article {
        if ($actor->role !== UserRole::Admin) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($article, $actor, $status, $assignee, $reason): Article {
            $lockedArticle = $this->lock($article);
            $targetAssignee = match ($status) {
                ArticleStatus::Authoring, ArticleStatus::Revision => $lockedArticle->author,
                ArticleStatus::EditorialWork => $assignee,
                ArticleStatus::ProductManagerCorrection => $lockedArticle->productManager,
                default => null,
            };

            if (
                in_array($status, [ArticleStatus::Authoring, ArticleStatus::Revision], true)
                && ($targetAssignee === null || $targetAssignee->role !== UserRole::Author)
            ) {
                throw ValidationException::withMessages([
                    'status' => __('An author is required for this status.'),
                ]);
            }

            if (
                $status === ArticleStatus::EditorialWork
                && ($targetAssignee === null
                    || ! in_array($targetAssignee->role, [UserRole::Editor, UserRole::Lector], true))
            ) {
                throw ValidationException::withMessages([
                    'assignee_id' => __('An editor or lector is required for this status.'),
                ]);
            }

            if (
                $status === ArticleStatus::ProductManagerCorrection
                && ($targetAssignee === null || $targetAssignee->role !== UserRole::ProductManager)
            ) {
                throw ValidationException::withMessages([
                    'status' => __('A product manager is required for this status.'),
                ]);
            }

            $lockedArticle->published_at = $status === ArticleStatus::Published
                ? ($lockedArticle->published_at ?? Carbon::now())
                : null;

            return $this->applyTransition(
                $lockedArticle,
                $actor,
                $status,
                $targetAssignee,
                $reason,
            );
        });
    }

    public function submitManuscript(Article $article, User $actor, ?string $reason = null): Article
    {
        return DB::transaction(function () use ($article, $actor, $reason): Article {
            $lockedArticle = $this->lock($article);

            if (
                ! in_array($lockedArticle->status, [ArticleStatus::Authoring, ArticleStatus::Revision], true)
                || $lockedArticle->current_assignee_id !== $actor->id
                || $lockedArticle->author_id !== $actor->id
            ) {
                throw new AuthorizationException;
            }

            return $this->applyTransition(
                $lockedArticle,
                $actor,
                ArticleStatus::ManuscriptSubmitted,
                null,
                $reason,
            );
        });
    }

    public function completeEditorialWork(Article $article, User $actor, ?string $reason = null): Article
    {
        return DB::transaction(function () use ($article, $actor, $reason): Article {
            $lockedArticle = $this->lock($article);

            if (
                $lockedArticle->status !== ArticleStatus::EditorialWork
                || $lockedArticle->current_assignee_id !== $actor->id
                || ! in_array($actor->role, [UserRole::Editor, UserRole::Lector], true)
            ) {
                throw new AuthorizationException;
            }

            return $this->applyTransition(
                $lockedArticle,
                $actor,
                ArticleStatus::ManuscriptSubmitted,
                null,
                $reason,
            );
        });
    }

    public function requestRevision(Article $article, User $actor, string $reason): Article
    {
        return DB::transaction(function () use ($article, $actor, $reason): Article {
            $lockedArticle = $this->lock($article);

            if (
                $lockedArticle->status !== ArticleStatus::ManuscriptSubmitted
                || $lockedArticle->author_id !== $actor->id
            ) {
                throw new AuthorizationException;
            }

            return $this->applyTransition(
                $lockedArticle,
                $actor,
                ArticleStatus::RevisionRequested,
                null,
                $reason,
            );
        });
    }

    public function returnToAuthor(Article $article, User $actor, string $reason): Article
    {
        return DB::transaction(function () use ($article, $actor, $reason): Article {
            $lockedArticle = $this->lock($article);
            $this->ensureWorkflowManager($lockedArticle, $actor);

            if (! in_array($lockedArticle->status, [
                ArticleStatus::ManuscriptSubmitted,
                ArticleStatus::RevisionRequested,
                ArticleStatus::ReadyForPublication,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => __('This workflow transition is not allowed.'),
                ]);
            }

            $author = $lockedArticle->author;

            if ($author === null || $author->role !== UserRole::Author) {
                throw ValidationException::withMessages([
                    'status' => __('An author is required for this status.'),
                ]);
            }

            return $this->applyTransition(
                $lockedArticle,
                $actor,
                ArticleStatus::Revision,
                $author,
                $reason,
            );
        });
    }

    public function unpublish(Article $article, User $actor, string $reason): Article
    {
        return DB::transaction(function () use ($article, $actor, $reason): Article {
            $lockedArticle = $this->lock($article);
            $this->ensureWorkflowManager($lockedArticle, $actor);

            if ($lockedArticle->status !== ArticleStatus::Published) {
                throw ValidationException::withMessages([
                    'status' => __('This workflow transition is not allowed.'),
                ]);
            }

            $lockedArticle->published_at = null;

            return $this->applyTransition(
                $lockedArticle,
                $actor,
                ArticleStatus::ReadyForPublication,
                null,
                $reason,
            );
        });
    }

    public function startProductManagerCorrection(
        Article $article,
        User $actor,
        ?string $reason = null,
    ): Article {
        return DB::transaction(function () use ($article, $actor, $reason): Article {
            $lockedArticle = $this->lock($article);
            $this->ensureWorkflowManager($lockedArticle, $actor);

            if ($lockedArticle->status === ArticleStatus::ProductManagerCorrection) {
                throw ValidationException::withMessages([
                    'status' => __('This workflow transition is not allowed.'),
                ]);
            }

            $lockedArticle->published_at = null;

            return $this->applyTransition(
                $lockedArticle,
                $actor,
                ArticleStatus::ProductManagerCorrection,
                $actor,
                $reason,
            );
        });
    }

    public function completeProductManagerCorrection(
        Article $article,
        User $actor,
        ?string $reason = null,
    ): Article {
        return $this->transition(
            $article,
            $actor,
            [ArticleStatus::ProductManagerCorrection],
            ArticleStatus::ManuscriptSubmitted,
            null,
            $reason,
        );
    }

    public function recallToManuscript(Article $article, User $actor, ?string $reason = null): Article
    {
        return $this->transition(
            $article,
            $actor,
            [
                ArticleStatus::Planned,
                ArticleStatus::Authoring,
                ArticleStatus::ProductManagerCorrection,
                ArticleStatus::RevisionRequested,
                ArticleStatus::Revision,
                ArticleStatus::EditorialWork,
                ArticleStatus::ReadyForPublication,
                ArticleStatus::Published,
            ],
            ArticleStatus::ManuscriptSubmitted,
            null,
            $reason,
            function (Article $lockedArticle): void {
                $lockedArticle->published_at = null;
            },
        );
    }

    public function markReady(Article $article, User $actor, ?string $reason = null): Article
    {
        return $this->transition(
            $article,
            $actor,
            [ArticleStatus::ManuscriptSubmitted, ArticleStatus::RevisionRequested, ArticleStatus::EditorialWork],
            ArticleStatus::ReadyForPublication,
            null,
            $reason,
        );
    }

    public function publish(Article $article, User $actor, ?string $reason = null): Article
    {
        return $this->transition(
            $article,
            $actor,
            [ArticleStatus::ReadyForPublication],
            ArticleStatus::Published,
            null,
            $reason,
            function (Article $lockedArticle): void {
                $lockedArticle->published_at = Carbon::now();
            },
        );
    }

    /**
     * @param  list<ArticleStatus>  $allowedFrom
     * @param  (callable(Article): void)|null  $beforeSave
     */
    private function transition(
        Article $article,
        User $actor,
        array $allowedFrom,
        ArticleStatus $to,
        ?User $assignee,
        ?string $reason,
        ?callable $beforeSave = null,
    ): Article {
        return DB::transaction(function () use (
            $article,
            $actor,
            $allowedFrom,
            $to,
            $assignee,
            $reason,
            $beforeSave,
        ): Article {
            $lockedArticle = $this->lock($article);
            $this->ensureWorkflowManager($lockedArticle, $actor);

            if (! in_array($lockedArticle->status, $allowedFrom, true)) {
                throw ValidationException::withMessages([
                    'status' => __('This workflow transition is not allowed.'),
                ]);
            }

            if ($beforeSave !== null) {
                $beforeSave($lockedArticle);
            }

            return $this->applyTransition($lockedArticle, $actor, $to, $assignee, $reason);
        });
    }

    private function applyTransition(
        Article $article,
        User $actor,
        ArticleStatus $to,
        ?User $assignee,
        ?string $reason,
    ): Article {
        $from = $article->status;
        $article->status = $to;
        $article->current_assignee_id = $assignee?->id;
        $article->save();

        if ($article->productManager !== null) {
            $this->recordParticipant($article, $article->productManager);
        }

        if ($article->author !== null) {
            $this->recordParticipant($article, $article->author);
        }

        if ($assignee !== null) {
            $this->recordParticipant($article, $assignee);
        }

        $this->recordEvent($article, $from, $to, $actor, $assignee, $reason);

        ArticleStatusChanged::dispatch($article, $from, $to, $actor, $assignee, $reason);

        return $article->refresh();
    }

    private function lock(Article $article): Article
    {
        return Article::query()
            ->whereKey($article->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureProductManager(User $actor): void
    {
        if ($actor->role !== UserRole::ProductManager) {
            throw new AuthorizationException;
        }
    }

    private function ensureWorkflowManager(Article $article, User $actor): void
    {
        if (
            $actor->role !== UserRole::Admin
            && ($actor->role !== UserRole::ProductManager || $article->product_manager_id !== $actor->id)
        ) {
            throw new AuthorizationException;
        }
    }

    private function ensureRole(User $user, UserRole $role, string $field): void
    {
        if ($user->role !== $role) {
            throw ValidationException::withMessages([
                $field => __('The selected user has an invalid role.'),
            ]);
        }
    }

    private function recordParticipant(Article $article, User $user): void
    {
        $article->participants()->updateOrCreate(
            ['user_id' => $user->id],
            ['process_role' => $user->role->value],
        );
    }

    private function recordEvent(
        Article $article,
        ?ArticleStatus $from,
        ArticleStatus $to,
        User $actor,
        ?User $assignee = null,
        ?string $reason = null,
    ): void {
        $article->workflowEvents()->create([
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor->id,
            'assignee_id' => $assignee?->id,
            'reason' => $reason,
        ]);
    }
}
