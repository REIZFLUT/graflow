<?php

namespace App\Http\Controllers;

use App\Models\EditorSettingsSet;
use App\Models\Publication;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $stats = [
            'articles' => $user->articles()->count(),
            'publications' => Publication::query()
                ->visibleTo($user)
                ->count(),
        ];

        if ($user->canManageEditorSettingsSets()) {
            $stats['editorSettingsSets'] = EditorSettingsSet::query()
                ->where('owner_id', $user->id)
                ->count();
        }

        return Inertia::render('dashboard', [
            'stats' => $stats,
        ]);
    }
}
