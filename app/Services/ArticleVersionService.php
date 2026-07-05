<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleVersion;
use App\Models\User;

class ArticleVersionService
{
    public function snapshot(Article $article, User $user): ArticleVersion
    {
        $nextVersion = ($article->versions()->max('version_number') ?? 0) + 1;

        return $article->versions()->create([
            'version_number' => $nextVersion,
            'title' => $article->title,
            'content' => $article->content,
            'created_by_id' => $user->id,
            'created_at' => now(),
        ]);
    }
}
