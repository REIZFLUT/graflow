<?php

namespace App\Events;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArticleStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Article $article,
        public ?ArticleStatus $from,
        public ArticleStatus $to,
        public User $actor,
        public ?User $assignee,
        public ?string $reason = null,
    ) {}
}
