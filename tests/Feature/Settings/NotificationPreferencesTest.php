<?php

namespace Tests\Feature\Settings;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_page_is_displayed_with_role_relevant_preferences(): void
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author)
            ->get(route('notifications.edit'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('settings/notifications')
                ->has('preferences', count(NotificationType::forRole($author->role))),
            );
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author)
            ->patch(route('notifications.update'), [
                'preferences' => [
                    NotificationType::AssignedResponsible->value => false,
                    NotificationType::ArticlePublished->value => true,
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('notifications.edit'));

        $author->refresh();

        $this->assertFalse($author->wantsNotification(NotificationType::AssignedResponsible));
        $this->assertTrue($author->wantsNotification(NotificationType::ArticlePublished));
    }

    public function test_update_requires_all_role_relevant_preferences(): void
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author)
            ->patch(route('notifications.update'), [
                'preferences' => [
                    NotificationType::AssignedResponsible->value => true,
                ],
            ])
            ->assertSessionHasErrors('preferences.'.NotificationType::ArticlePublished->value);
    }
}
