<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArticleStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Article $article,
        public NotificationType $type,
        public ?string $reason = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $key = "notifications.{$this->type->value}";

        $message = (new MailMessage)
            ->subject(__("{$key}.subject", ['title' => $this->article->title]))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name]))
            ->line(__("{$key}.line", ['title' => $this->article->title]));

        $reason = trim((string) $this->reason);

        if ($reason !== '') {
            $message->line(__('notifications.reason', ['reason' => $reason]));
        }

        return $message
            ->action(__('notifications.action'), route('articles.edit', $this->article))
            ->line(__('notifications.footer'));
    }
}
