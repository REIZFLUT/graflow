<?php

namespace Tests\Feature\Notifications;

use App\Enums\ArticleStatus;
use App\Enums\NotificationType;
use App\Events\ArticleStatusChanged;
use App\Listeners\SendArticleStatusNotifications;
use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticleStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ArticleStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_transition_dispatches_status_changed_event(): void
    {
        Event::fake([ArticleStatusChanged::class]);

        $productManager = User::factory()->productManager()->create();
        $article = Article::factory()->readyForPublication()->create([
            'product_manager_id' => $productManager->id,
        ]);

        $this->actingAs($productManager)
            ->post(route('articles.workflow.publish', $article))
            ->assertRedirect();

        Event::assertDispatched(
            ArticleStatusChanged::class,
            fn (ArticleStatusChanged $event): bool => $event->to === ArticleStatus::Published
                && $event->from === ArticleStatus::ReadyForPublication
                && $event->article->is($article),
        );
    }

    public function test_new_assignee_is_notified_when_becoming_responsible(): void
    {
        Notification::fake();

        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->authoring()->create([
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
            'current_assignee_id' => $author->id,
        ]);

        $event = new ArticleStatusChanged(
            $article,
            ArticleStatus::Planned,
            ArticleStatus::Authoring,
            $productManager,
            $author,
        );

        (new SendArticleStatusNotifications)->handle($event);

        Notification::assertSentTo(
            $author,
            ArticleStatusNotification::class,
            fn (ArticleStatusNotification $notification): bool => $notification->type === NotificationType::AssignedResponsible,
        );
    }

    public function test_actor_is_not_notified_about_their_own_action(): void
    {
        Notification::fake();

        $productManager = User::factory()->productManager()->create();
        $article = Article::factory()->productManagerCorrection()->create([
            'product_manager_id' => $productManager->id,
            'current_assignee_id' => $productManager->id,
        ]);

        $event = new ArticleStatusChanged(
            $article,
            ArticleStatus::ManuscriptSubmitted,
            ArticleStatus::ProductManagerCorrection,
            $productManager,
            $productManager,
        );

        (new SendArticleStatusNotifications)->handle($event);

        Notification::assertNothingSent();
    }

    public function test_author_is_notified_when_article_published(): void
    {
        Notification::fake();

        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->published()->create([
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
            'owner_id' => $author->id,
        ]);

        $event = new ArticleStatusChanged(
            $article,
            ArticleStatus::ReadyForPublication,
            ArticleStatus::Published,
            $productManager,
            null,
        );

        (new SendArticleStatusNotifications)->handle($event);

        Notification::assertSentTo(
            $author,
            ArticleStatusNotification::class,
            fn (ArticleStatusNotification $notification): bool => $notification->type === NotificationType::ArticlePublished,
        );
    }

    public function test_product_manager_is_notified_when_manuscript_submitted(): void
    {
        Notification::fake();

        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
        ]);

        $event = new ArticleStatusChanged(
            $article,
            ArticleStatus::Authoring,
            ArticleStatus::ManuscriptSubmitted,
            $author,
            null,
        );

        (new SendArticleStatusNotifications)->handle($event);

        Notification::assertSentTo(
            $productManager,
            ArticleStatusNotification::class,
            fn (ArticleStatusNotification $notification): bool => $notification->type === NotificationType::ManuscriptSubmitted,
        );
    }

    public function test_product_manager_is_notified_when_editorial_work_completed(): void
    {
        Notification::fake();

        $productManager = User::factory()->productManager()->create();
        $editor = User::factory()->editor()->create();
        $article = Article::factory()->manuscriptSubmitted()->create([
            'product_manager_id' => $productManager->id,
        ]);

        $event = new ArticleStatusChanged(
            $article,
            ArticleStatus::EditorialWork,
            ArticleStatus::ManuscriptSubmitted,
            $editor,
            null,
        );

        (new SendArticleStatusNotifications)->handle($event);

        Notification::assertSentTo(
            $productManager,
            ArticleStatusNotification::class,
            fn (ArticleStatusNotification $notification): bool => $notification->type === NotificationType::EditorialCompleted,
        );
    }

    public function test_disabled_preference_prevents_notification(): void
    {
        Notification::fake();

        $productManager = User::factory()->productManager()->create();
        $author = User::factory()->author()->create([
            'notification_preferences' => [
                NotificationType::AssignedResponsible->value => false,
            ],
        ]);
        $article = Article::factory()->authoring()->create([
            'product_manager_id' => $productManager->id,
            'author_id' => $author->id,
            'current_assignee_id' => $author->id,
        ]);

        $event = new ArticleStatusChanged(
            $article,
            ArticleStatus::Planned,
            ArticleStatus::Authoring,
            $productManager,
            $author,
        );

        (new SendArticleStatusNotifications)->handle($event);

        Notification::assertNotSentTo($author, ArticleStatusNotification::class);
    }

    public function test_reason_is_included_in_mail_when_present(): void
    {
        $author = User::factory()->author()->create();
        $article = Article::factory()->create();

        $mail = (new ArticleStatusNotification(
            $article,
            NotificationType::AssignedResponsible,
            'Bitte Abschnitt 2 kürzen.',
        ))->toMail($author);

        $this->assertContains(
            __('notifications.reason', ['reason' => 'Bitte Abschnitt 2 kürzen.']),
            $mail->introLines,
        );
    }

    public function test_reason_is_omitted_when_empty(): void
    {
        $author = User::factory()->author()->create();
        $article = Article::factory()->create();

        $mail = (new ArticleStatusNotification(
            $article,
            NotificationType::AssignedResponsible,
            '   ',
        ))->toMail($author);

        foreach ($mail->introLines as $line) {
            $this->assertStringNotContainsString(
                __('notifications.reason', ['reason' => '']),
                $line,
            );
        }
    }

    public function test_notification_defaults_depend_on_role(): void
    {
        $author = User::factory()->author()->make();
        $productManager = User::factory()->productManager()->make();

        $this->assertTrue($author->wantsNotification(NotificationType::AssignedResponsible));
        $this->assertTrue($author->wantsNotification(NotificationType::ArticlePublished));
        $this->assertFalse($author->wantsNotification(NotificationType::ManuscriptSubmitted));

        $this->assertTrue($productManager->wantsNotification(NotificationType::AssignedResponsible));
        $this->assertTrue($productManager->wantsNotification(NotificationType::ManuscriptSubmitted));
        $this->assertTrue($productManager->wantsNotification(NotificationType::ReadyForPublication));
    }
}
