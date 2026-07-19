<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\PublicationChapter;
use App\Support\Handbook;
use Illuminate\Database\Seeder;

class HandbookSeeder extends Seeder
{
    public function run(): void
    {
        $issue = Handbook::resolveIssue();

        if ($issue === null) {
            return;
        }

        $publication = $issue->publication;
        $ownerId = $publication->owner_id;

        $chapter = PublicationChapter::query()->updateOrCreate(
            [
                'publication_issue_id' => $issue->id,
                'position' => 1,
            ],
            [
                'title' => 'Einführung',
            ],
        );

        Article::query()->firstOrCreate(
            [
                'title' => 'Willkommen im Graflow Handbuch',
                'publication_issue_id' => $issue->id,
            ],
            [
                'content' => [
                    'type' => 'doc',
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Dieses Handbuch beschreibt die Graflow Software. Der Administrator kann hier Artikel anlegen und bearbeiten.',
                                ],
                            ],
                        ],
                    ],
                ],
                'owner_id' => $ownerId,
                'product_manager_id' => $ownerId,
                'author_id' => $ownerId,
                'current_assignee_id' => $ownerId,
                'status' => ArticleStatus::Authoring,
                'publication_chapter_id' => $chapter->id,
                'position' => 1,
                'editor_settings_set_id' => $publication->editor_settings_set_id,
            ],
        );
    }
}
