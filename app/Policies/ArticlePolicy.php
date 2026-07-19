<?php

namespace App\Policies;

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Article $article): bool
    {
        return $user->role === UserRole::Admin
            || $user->id === $article->product_manager_id
            || $user->id === $article->author_id
            || $user->id === $article->current_assignee_id
            || $article->participants()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::ProductManager], true);
    }

    public function update(User $user, Article $article): bool
    {
        return $this->updateContent($user, $article);
    }

    public function updateContent(User $user, Article $article): bool
    {
        return $article->status !== ArticleStatus::Published
            && $user->id === $article->current_assignee_id
            && in_array($article->status, [
                ArticleStatus::Authoring,
                ArticleStatus::Revision,
                ArticleStatus::EditorialWork,
                ArticleStatus::ProductManagerCorrection,
            ], true);
    }

    public function submitManuscript(User $user, Article $article): bool
    {
        return $article->status !== ArticleStatus::Published
            && $user->id === $article->author_id
            && $user->id === $article->current_assignee_id
            && in_array($article->status, [ArticleStatus::Authoring, ArticleStatus::Revision], true);
    }

    public function completeEditorialWork(User $user, Article $article): bool
    {
        return $article->status === ArticleStatus::EditorialWork
            && $user->id === $article->current_assignee_id
            && in_array($user->role, [UserRole::Editor, UserRole::Lector], true);
    }

    public function requestRevision(User $user, Article $article): bool
    {
        return $article->status === ArticleStatus::ManuscriptSubmitted
            && $user->id === $article->author_id;
    }

    public function manageWorkflow(User $user, Article $article): bool
    {
        return $article->status !== ArticleStatus::Published
            && (
                $user->role === UserRole::Admin
                || (
                    $user->role === UserRole::ProductManager
                    && $user->id === $article->product_manager_id
                )
            );
    }

    public function forceStatus(User $user, Article $article): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Article $article): bool
    {
        return $this->manageWorkflow($user, $article);
    }
}
