<?php

namespace App\Policies;

use App\Models\ArticlePdf;
use App\Models\User;

class ArticlePdfPolicy
{
    public function view(User $user, ArticlePdf $articlePdf): bool
    {
        return $user->id === $articlePdf->owner_id;
    }

    public function update(User $user, ArticlePdf $articlePdf): bool
    {
        return $user->id === $articlePdf->owner_id;
    }

    public function delete(User $user, ArticlePdf $articlePdf): bool
    {
        return $user->id === $articlePdf->owner_id;
    }
}
