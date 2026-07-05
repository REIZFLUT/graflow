<?php

namespace App\Policies;

use App\Models\ArticleMedia;
use App\Models\User;

class ArticleMediaPolicy
{
    public function view(User $user, ArticleMedia $media): bool
    {
        return $user->id === $media->owner_id;
    }

    public function update(User $user, ArticleMedia $media): bool
    {
        return $user->id === $media->owner_id;
    }

    public function delete(User $user, ArticleMedia $media): bool
    {
        return $user->id === $media->owner_id;
    }
}
