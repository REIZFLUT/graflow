<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\EditorSettingsSet;
use App\Models\Publication;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'stats' => [
                'articles' => Article::count(),
                'publications' => Publication::count(),
                'editorSettingsSets' => EditorSettingsSet::count(),
            ],
        ]);
    }
}
