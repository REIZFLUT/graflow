<?php

namespace App\Policies;

use App\Models\ArticleMedia;
use App\Models\User;

class ArticleMediaPolicy
{
    public function view(User $user, ArticleMedia $media): bool
    {
        return $media->isStaging()
            ? $user->id === $media->owner_id
            : $media->article !== null && $user->can('view', $media->article);
    }

    public function update(User $user, ArticleMedia $media): bool
    {
        return $media->isStaging()
            ? $user->id === $media->owner_id
            : $media->article !== null && $user->can('updateContent', $media->article);
    }

    public function delete(User $user, ArticleMedia $media): bool
    {
        return $this->update($user, $media);
    }
}
