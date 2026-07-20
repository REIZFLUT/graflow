<?php

namespace App\Http\Controllers\Settings;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\NotificationPreferencesUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Show the user's notification settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        $preferences = array_map(
            fn (NotificationType $type): array => [
                'key' => $type->value,
                'enabled' => $user->wantsNotification($type),
            ],
            NotificationType::forRole($user->role),
        );

        return Inertia::render('settings/notifications', [
            'preferences' => $preferences,
        ]);
    }

    /**
     * Update the user's notification preferences.
     */
    public function update(NotificationPreferencesUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->notification_preferences = array_merge(
            $user->notification_preferences ?? [],
            $request->relevantPreferences(),
        );

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.settings.notifications_updated')]);

        return to_route('notifications.edit');
    }
}
