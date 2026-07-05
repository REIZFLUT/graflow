<?php

namespace App\Console\Commands;

use App\Services\ArticleMediaService;
use Illuminate\Console\Command;

class PruneArticleMediaStagingCommand extends Command
{
    protected $signature = 'article-media:prune-staging';

    protected $description = 'Remove expired staging article media files';

    public function handle(ArticleMediaService $articleMediaService): int
    {
        $count = $articleMediaService->pruneExpiredStaging();

        $this->info("Pruned {$count} expired staging media item(s).");

        return self::SUCCESS;
    }
}
