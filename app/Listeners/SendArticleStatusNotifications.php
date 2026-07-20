<?php

namespace App\Listeners;

use App\Enums\ArticleStatus;
use App\Enums\NotificationType;
use App\Events\ArticleStatusChanged;
use App\Models\User;
use App\Notifications\ArticleStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendArticleStatusNotifications implements ShouldQueue
{
    /**
     * Only dispatch after the surrounding database transaction has committed.
     */
    public bool $afterCommit = true;

    public function handle(ArticleStatusChanged $event): void
    {
        $article = $event->article;

        /** @var array<string, array{0: User, 1: NotificationType}> $recipients */
        $recipients = [];

        $add = function (?User $user, NotificationType $type) use (&$recipients, $event): void {
            if ($user === null || $user->id === $event->actor->id) {
                return;
            }

            $recipients["{$user->id}-{$type->value}"] = [$user, $type];
        };

        if ($event->assignee !== null) {
            $add($event->assignee, NotificationType::AssignedResponsible);
        }

        if ($event->to === ArticleStatus::Published) {
            $add($article->author, NotificationType::ArticlePublished);
            $add($article->owner, NotificationType::ArticlePublished);
        }

        if ($event->to === ArticleStatus::ManuscriptSubmitted) {
            if (in_array($event->from, [ArticleStatus::Authoring, ArticleStatus::Revision], true)) {
                $add($article->productManager, NotificationType::ManuscriptSubmitted);
            } elseif ($event->from === ArticleStatus::EditorialWork) {
                $add($article->productManager, NotificationType::EditorialCompleted);
            }
        }

        if ($event->to === ArticleStatus::RevisionRequested) {
            $add($article->productManager, NotificationType::RevisionRequested);
        }

        if ($event->to === ArticleStatus::ReadyForPublication) {
            $add($article->productManager, NotificationType::ReadyForPublication);
        }

        foreach ($recipients as [$user, $type]) {
            if ($user->wantsNotification($type)) {
                $user->notify(new ArticleStatusNotification($article, $type, $event->reason));
            }
        }
    }
}
