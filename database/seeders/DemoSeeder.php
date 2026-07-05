<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\PublicationEditorFont;
use App\Models\Article;
use App\Models\EditorSettingsSet;
use App\Models\Publication;
use App\Models\PublicationCategory;
use App\Models\PublicationIssue;
use App\Models\User;
use Database\Seeders\Support\DemoArticleContentBuilder;
use Database\Seeders\Support\DemoCopyrightParser;
use Database\Seeders\Support\DemoMediaImporter;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        ini_set('memory_limit', '512M');

        $productManager = User::query()->where('email', 'productmanager@example.com')->firstOrFail();

        $bookSettings = EditorSettingsSet::query()->firstOrCreate(
            [
                'owner_id' => $productManager->id,
                'name' => 'Buch-Layout',
            ],
            [
                'font' => PublicationEditorFont::Spectral,
                'has_marginal_column' => true,
            ],
        );

        $magazineSettings = EditorSettingsSet::query()->firstOrCreate(
            [
                'owner_id' => $productManager->id,
                'name' => 'Magazin-Layout',
            ],
            [
                'font' => PublicationEditorFont::Roboto,
                'has_marginal_column' => false,
            ],
        );

        $bookPublication = Publication::query()->firstOrCreate(
            [
                'owner_id' => $productManager->id,
                'name' => 'GEG Baupraxis Handbuch',
            ],
            [
                'editor_settings_set_id' => $bookSettings->id,
            ],
        );

        $magazinePublication = Publication::query()->firstOrCreate(
            [
                'owner_id' => $productManager->id,
                'name' => 'GEG Baupraxis',
            ],
            [
                'editor_settings_set_id' => $magazineSettings->id,
            ],
        );

        $bookPublication->update(['editor_settings_set_id' => $bookSettings->id]);
        $magazinePublication->update(['editor_settings_set_id' => $magazineSettings->id]);

        $issues = $this->seedIssues($bookPublication, $magazinePublication);
        $categories = $this->seedCategories($bookPublication, $magazinePublication);

        $contentBuilder = new DemoArticleContentBuilder(
            new DemoMediaImporter(app(\App\Services\ArticleMediaService::class)),
        );

        $mediaImporter = new DemoMediaImporter(app(\App\Services\ArticleMediaService::class));
        $imageFilenames = $mediaImporter->availableImageFilenames();
        $imageIndex = 0;

        /** @var list<array<string, mixed>> $articles */
        $articles = require database_path('seeders/data/demo-articles.php');

        foreach ($articles as $articleData) {
            $author = User::query()->where('email', $articleData['author_email'])->firstOrFail();
            $publicationKey = $articleData['publication'];
            $publication = $publicationKey === 'book' ? $bookPublication : $magazinePublication;
            $issueKey = $publicationKey.'-'.$articleData['issue'];
            $issue = $issues[$issueKey];

            $article = Article::query()->firstOrCreate(
                [
                    'title' => $articleData['title'],
                    'owner_id' => $author->id,
                ],
                [
                    'content' => ['type' => 'doc', 'content' => []],
                    'status' => ArticleStatus::Published,
                    'publication_issue_id' => $issue->id,
                ],
            );

            $article->update([
                'status' => ArticleStatus::Published,
                'publication_issue_id' => $issue->id,
            ]);

            $imageCount = $publicationKey === 'book' ? 1 : 2;
            $mediaItems = [];

            for ($i = 0; $i < $imageCount; $i++) {
                $filename = $imageFilenames[$imageIndex % count($imageFilenames)];
                $imageIndex++;

                $copyright = DemoCopyrightParser::parse($filename);

                $mediaItems[] = $mediaImporter->import($article, $author, $filename, [
                    'alt_text' => $this->imageAltText($articleData['title'], $i + 1),
                    'copyright' => $copyright,
                    'caption' => $this->imageCaption($articleData['title'], $i + 1),
                ]);

                gc_collect_cycles();
            }

            $content = $contentBuilder->build(
                $articleData['sections'],
                $mediaItems,
                $publicationKey === 'book',
            );

            $article->update(['content' => $content]);

            $categoryName = $articleData['category'];
            $categoryKey = $publication->id.'-'.$categoryName;

            if (isset($categories[$categoryKey])) {
                $article->publicationCategories()->syncWithoutDetaching([$categories[$categoryKey]->id]);
            }
        }
    }

    /**
     * @return array<string, PublicationIssue>
     */
    private function seedIssues(Publication $bookPublication, Publication $magazinePublication): array
    {
        $definitions = [
            'book-01-2026' => [$bookPublication, '01-2026'],
            'book-02-2026' => [$bookPublication, '02-2026'],
            'magazine-03-2026' => [$magazinePublication, '03-2026'],
            'magazine-06-2026' => [$magazinePublication, '06-2026'],
        ];

        $issues = [];

        foreach ($definitions as $key => [$publication, $label]) {
            $issues[$key] = PublicationIssue::query()->firstOrCreate(
                [
                    'publication_id' => $publication->id,
                    'label' => $label,
                ],
            );
        }

        return $issues;
    }

    /**
     * @return array<string, PublicationCategory>
     */
    private function seedCategories(Publication $bookPublication, Publication $magazinePublication): array
    {
        $definitions = [
            $bookPublication->id.'-Energieberatung' => [$bookPublication, 'Energieberatung'],
            $magazinePublication->id.'-Energieberatung' => [$magazinePublication, 'Energieberatung'],
            $magazinePublication->id.'-GEG & Recht' => [$magazinePublication, 'GEG & Recht'],
            $magazinePublication->id.'-Sanierung & Förderung' => [$magazinePublication, 'Sanierung & Förderung'],
        ];

        $categories = [];

        foreach ($definitions as $key => [$publication, $name]) {
            $categories[$key] = PublicationCategory::query()->firstOrCreate(
                [
                    'publication_id' => $publication->id,
                    'name' => $name,
                ],
            );
        }

        return $categories;
    }

    private function imageAltText(string $title, int $index): string
    {
        return "Illustration {$index} zum Thema {$title}";
    }

    private function imageCaption(string $title, int $index): string
    {
        return "Abbildung {$index}: Praxisbeispiel aus dem Bereich Energieberatung – {$title}";
    }
}
