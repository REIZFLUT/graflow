<?php

namespace App\Policies;

use App\Models\ArticlePdf;
use App\Models\User;

class ArticlePdfPolicy
{
    public function view(User $user, ArticlePdf $articlePdf): bool
    {
        return $user->can('view', $articlePdf->article);
    }

    public function update(User $user, ArticlePdf $articlePdf): bool
    {
        return $user->can('view', $articlePdf->article);
    }

    public function delete(User $user, ArticlePdf $articlePdf): bool
    {
        return $user->can('manageWorkflow', $articlePdf->article);
    }
}
