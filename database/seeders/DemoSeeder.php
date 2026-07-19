<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\PublicationEditorFont;
use App\Models\Article;
use App\Models\EditorSettingsSet;
use App\Models\Publication;
use App\Models\PublicationCategory;
use App\Models\PublicationChapter;
use App\Models\PublicationIssue;
use App\Models\User;
use App\Services\ArticleMediaService;
use App\Services\ArticleVersionService;
use Database\Seeders\Support\DemoArticleContentBuilder;
use Database\Seeders\Support\DemoCopyrightParser;
use Database\Seeders\Support\DemoMediaImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

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
        $chapters = $this->seedChapters($issues);
        $categories = $this->seedCategories($bookPublication, $magazinePublication);
        $editor = User::query()->where('email', 'editor@example.com')->firstOrFail();
        $lector = User::query()->where('email', 'lector@example.com')->firstOrFail();

        $contentBuilder = new DemoArticleContentBuilder(
            new DemoMediaImporter(app(ArticleMediaService::class)),
        );

        $mediaImporter = new DemoMediaImporter(app(ArticleMediaService::class));
        $imageFilenames = $mediaImporter->availableImageFilenames();
        $imageIndex = 0;

        /** @var list<array<string, mixed>> $articles */
        $articles = require database_path('seeders/data/demo-articles.php');
        $articlePlans = $this->articlePlans();

        foreach ($articles as $articleIndex => $articleData) {
            $author = User::query()->where('email', $articleData['author_email'])->firstOrFail();
            $publicationKey = $articleData['publication'];
            $publication = $publicationKey === 'book' ? $bookPublication : $magazinePublication;
            $issueKey = $publicationKey.'-'.$articleData['issue'];
            $issue = $issues[$issueKey];
            $articlePlan = $articlePlans[$articleIndex];
            $chapter = $chapters[$issueKey][$articlePlan['chapter_position']];
            $editorialAssignee = $articlePlan['editorial_role'] === 'lector' ? $lector : $editor;
            $currentAssignee = match ($articlePlan['status']) {
                ArticleStatus::Authoring, ArticleStatus::Revision => $author,
                ArticleStatus::EditorialWork => $editorialAssignee,
                ArticleStatus::ProductManagerCorrection => $productManager,
                default => null,
            };

            $article = Article::query()->firstOrCreate(
                [
                    'title' => $articleData['title'],
                    'owner_id' => $author->id,
                ],
                [
                    'content' => ['type' => 'doc', 'content' => []],
                    'status' => $articlePlan['status'],
                    'product_manager_id' => $productManager->id,
                    'author_id' => $author->id,
                    'current_assignee_id' => $currentAssignee?->id,
                    'published_at' => $articlePlan['published_at'],
                    'publication_issue_id' => $issue->id,
                    'publication_chapter_id' => $chapter->id,
                    'position' => $articlePlan['position'],
                    'submission_deadline' => $articlePlan['submission_deadline'],
                    'target_character_count' => $articlePlan['target_character_count'],
                ],
            );

            $article->update([
                'status' => $articlePlan['status'],
                'product_manager_id' => $productManager->id,
                'author_id' => $author->id,
                'current_assignee_id' => $currentAssignee?->id,
                'published_at' => $articlePlan['published_at'],
                'publication_issue_id' => $issue->id,
                'publication_chapter_id' => $chapter->id,
                'position' => $articlePlan['position'],
                'submission_deadline' => $articlePlan['submission_deadline'],
                'target_character_count' => $articlePlan['target_character_count'],
            ]);

            $article->participants()->updateOrCreate(
                ['user_id' => $productManager->id],
                ['process_role' => $productManager->role->value],
            );
            $article->participants()->updateOrCreate(
                ['user_id' => $author->id],
                ['process_role' => $author->role->value],
            );

            $this->seedWorkflowHistory(
                $article,
                $articleIndex,
                $articlePlan['status'],
                $productManager,
                $author,
                $editorialAssignee,
            );

            $imageCount = $publicationKey === 'book' ? 1 : 2;
            $mediaItems = [];

            for ($i = 0; $i < $imageCount; $i++) {
                $filename = $imageFilenames[$imageIndex % count($imageFilenames)];
                $imageIndex++;

                $copyright = DemoCopyrightParser::parse($filename);

                $metadata = [
                    'alt_text' => $this->imageAltText($articleData['title'], $i + 1),
                    'copyright' => $copyright,
                    'caption' => $this->imageCaption($articleData['title'], $i + 1),
                ];

                $media = $article->media()
                    ->where('original_filename', $filename)
                    ->first();

                if ($media === null) {
                    $media = $mediaImporter->import($article, $author, $filename, $metadata);
                } else {
                    $media->update($metadata);
                }

                $mediaItems[] = $media;

                gc_collect_cycles();
            }

            $content = $contentBuilder->build(
                $articleData['sections'],
                $mediaItems,
                $publicationKey === 'book',
            );

            $article->update(['content' => $content]);

            $this->seedComments(
                $article,
                $articleData['sections'],
                $articleIndex,
                $content,
                $author,
                $editorialAssignee,
                $productManager,
            );

            $categoryName = $articleData['category'];
            $categoryKey = $publication->id.'-'.$categoryName;

            if (isset($categories[$categoryKey])) {
                $article->publicationCategories()->syncWithoutDetaching([$categories[$categoryKey]->id]);
            }
        }

        $this->seedVersionComparisonExample($issues);
    }

    /**
     * @return list<array{
     *     status: ArticleStatus,
     *     chapter_position: int,
     *     position: int,
     *     submission_deadline: Carbon,
     *     target_character_count: int,
     *     published_at: Carbon|null,
     *     editorial_role: 'editor'|'lector'
     * }>
     */
    private function articlePlans(): array
    {
        $definitions = [
            [ArticleStatus::Planned, 1, 1, '2026-01-12', null, 'editor'],
            [ArticleStatus::Authoring, 1, 2, '2026-01-15', null, 'lector'],
            [ArticleStatus::ManuscriptSubmitted, 2, 1, '2026-01-18', null, 'editor'],
            [ArticleStatus::RevisionRequested, 2, 2, '2026-01-20', null, 'lector'],
            [ArticleStatus::Revision, 1, 1, '2026-02-10', null, 'editor'],
            [ArticleStatus::EditorialWork, 1, 2, '2026-02-14', null, 'editor'],
            [ArticleStatus::ReadyForPublication, 2, 1, '2026-02-18', null, 'lector'],
            [ArticleStatus::Published, 1, 1, '2026-03-05', '2026-03-28', 'editor'],
            [ArticleStatus::EditorialWork, 1, 2, '2026-03-08', null, 'lector'],
            [ArticleStatus::Authoring, 2, 1, '2026-03-12', null, 'editor'],
            [ArticleStatus::Published, 2, 2, '2026-03-15', '2026-03-29', 'lector'],
            [ArticleStatus::Revision, 1, 1, '2026-06-05', null, 'editor'],
            [ArticleStatus::ProductManagerCorrection, 1, 2, '2026-06-08', null, 'editor'],
            [ArticleStatus::EditorialWork, 2, 1, '2026-06-12', null, 'lector'],
            [ArticleStatus::Published, 2, 2, '2026-06-15', '2026-06-27', 'lector'],
        ];

        return array_map(
            fn (array $definition, int $index): array => [
                'status' => $definition[0],
                'chapter_position' => $definition[1],
                'position' => $definition[2],
                'submission_deadline' => Carbon::parse($definition[3]),
                'target_character_count' => 8000 + ($index * 500),
                'published_at' => $definition[4] === null ? null : Carbon::parse($definition[4]),
                'editorial_role' => $definition[5],
            ],
            $definitions,
            array_keys($definitions),
        );
    }

    private function seedWorkflowHistory(
        Article $article,
        int $articleIndex,
        ArticleStatus $status,
        User $productManager,
        User $author,
        User $editorialAssignee,
    ): void {
        /** @var list<array{ArticleStatus|null, ArticleStatus, User, User|null, string}> $events */
        $events = [
            [null, ArticleStatus::Planned, $productManager, $author, 'Artikel für die Ausgabe eingeplant.'],
        ];

        if ($status !== ArticleStatus::Planned) {
            $events[] = [
                ArticleStatus::Planned,
                ArticleStatus::Authoring,
                $productManager,
                $author,
                'Autor mit der Ausarbeitung beauftragt.',
            ];
        }

        if (! in_array($status, [ArticleStatus::Planned, ArticleStatus::Authoring], true)) {
            $events[] = [
                ArticleStatus::Authoring,
                ArticleStatus::ManuscriptSubmitted,
                $author,
                null,
                'Manuskript zur Prüfung eingereicht.',
            ];
        }

        if (in_array($status, [ArticleStatus::RevisionRequested, ArticleStatus::Revision], true)) {
            $events[] = [
                ArticleStatus::ManuscriptSubmitted,
                ArticleStatus::RevisionRequested,
                $author,
                null,
                'Quellenangaben und Praxisbeispiel müssen ergänzt werden.',
            ];
        }

        if ($status === ArticleStatus::Revision) {
            $events[] = [
                ArticleStatus::RevisionRequested,
                ArticleStatus::Revision,
                $productManager,
                $author,
                'Überarbeitung mit konkretem Autorenbriefing gestartet.',
            ];
        }

        if ($status === ArticleStatus::ProductManagerCorrection) {
            $events[] = [
                ArticleStatus::ManuscriptSubmitted,
                ArticleStatus::ProductManagerCorrection,
                $productManager,
                $productManager,
                'Sprachliche Korrekturen am eingereichten Manuskript.',
            ];
        }

        if (in_array($status, [
            ArticleStatus::EditorialWork,
            ArticleStatus::ReadyForPublication,
            ArticleStatus::Published,
        ], true)) {
            $events[] = [
                ArticleStatus::ManuscriptSubmitted,
                ArticleStatus::EditorialWork,
                $productManager,
                $editorialAssignee,
                'Fachliche und sprachliche Redaktion zugewiesen.',
            ];

            $article->participants()->updateOrCreate(
                ['user_id' => $editorialAssignee->id],
                ['process_role' => $editorialAssignee->role->value],
            );
        }

        if (in_array($status, [ArticleStatus::ReadyForPublication, ArticleStatus::Published], true)) {
            $events[] = [
                ArticleStatus::EditorialWork,
                ArticleStatus::ReadyForPublication,
                $productManager,
                null,
                'Redaktion abgeschlossen und zur Veröffentlichung freigegeben.',
            ];
        }

        if ($status === ArticleStatus::Published) {
            $events[] = [
                ArticleStatus::ReadyForPublication,
                ArticleStatus::Published,
                $productManager,
                null,
                'Artikel mit der geplanten Ausgabe veröffentlicht.',
            ];
        }

        $historyStartedAt = Carbon::parse('2025-11-03')->addDays($articleIndex * 2);

        foreach ($events as $eventIndex => [$from, $to, $actor, $assignee, $reason]) {
            $article->workflowEvents()->firstOrCreate(
                [
                    'from_status' => $from?->value,
                    'to_status' => $to->value,
                    'actor_id' => $actor->id,
                    'assignee_id' => $assignee?->id,
                    'reason' => $reason,
                ],
                [
                    'created_at' => $historyStartedAt->copy()->addHours($eventIndex),
                ],
            );
        }
    }

    /**
     * Seed a few comment threads (with replies and resolved states) on selected
     * articles and anchor them in the article content via `comment` marks so the
     * commenting feature is demonstrated out of the box.
     *
     * @param  list<array{type: string, level?: int, text: string, marginal_note?: string|null}>  $sections
     * @param  array<string, mixed>  $content
     */
    private function seedComments(
        Article $article,
        array $sections,
        int $articleIndex,
        array $content,
        User $author,
        User $editorialAssignee,
        User $productManager,
    ): void {
        $plans = $this->commentPlans($articleIndex, $author, $editorialAssignee, $productManager);

        if ($plans === []) {
            return;
        }

        $bodyTexts = $this->bodyParagraphTexts($sections);
        $changed = false;

        foreach ($plans as $planIndex => $plan) {
            if (! isset($bodyTexts[$plan['paragraph_index']])) {
                continue;
            }

            $fullText = $bodyTexts[$plan['paragraph_index']];
            $phrase = $this->anchorPhrase($fullText);
            $creator = $plan['comments'][0][0];

            $commentBase = Carbon::parse('2026-06-20 09:00:00')
                ->addDays($articleIndex)
                ->addHours($planIndex * 2);

            $resolvedBy = $plan['resolved_by'];
            $resolvedAt = $resolvedBy !== null
                ? $commentBase->copy()->addMinutes(count($plan['comments']) * 30 + 15)
                : null;

            $thread = $article->commentThreads()->firstOrCreate(
                [
                    'created_by_id' => $creator->id,
                    'anchor_text' => $phrase,
                ],
                [
                    'resolved_at' => $resolvedAt,
                    'resolved_by_id' => $resolvedBy?->id,
                ],
            );

            if ($thread->comments()->doesntExist()) {
                foreach ($plan['comments'] as $commentIndex => [$user, $body]) {
                    $createdAt = $commentBase->copy()->addMinutes($commentIndex * 30);

                    $comment = $thread->comments()->make([
                        'user_id' => $user->id,
                        'body' => $body,
                    ]);
                    $comment->created_at = $createdAt;
                    $comment->updated_at = $createdAt;
                    $comment->save();
                }
            }

            $anchor = '';
            $applied = false;
            $content = $this->markNodeByText($content, $fullText, $thread->id, $anchor, $applied)[0];

            if ($applied) {
                $changed = true;
            }
        }

        if ($changed) {
            $article->update(['content' => $content]);
        }
    }

    /**
     * @return list<array{
     *     paragraph_index: int,
     *     resolved_by: User|null,
     *     comments: list<array{0: User, 1: string}>
     * }>
     */
    private function commentPlans(
        int $articleIndex,
        User $author,
        User $editorialAssignee,
        User $productManager,
    ): array {
        return match ($articleIndex) {
            5 => [
                [
                    'paragraph_index' => 1,
                    'resolved_by' => null,
                    'comments' => [
                        [$editorialAssignee, 'Bitte hier eine belastbare Quelle für diese Aussage ergänzen.'],
                        [$author, 'Danke für den Hinweis – ich ergänze die Quelle im nächsten Absatz.'],
                    ],
                ],
                [
                    'paragraph_index' => 6,
                    'resolved_by' => $editorialAssignee,
                    'comments' => [
                        [$editorialAssignee, 'Die Formulierung wirkt etwas umgangssprachlich, bitte sachlicher fassen.'],
                        [$author, 'Erledigt, der Absatz ist jetzt neutraler formuliert.'],
                    ],
                ],
            ],
            8 => [
                [
                    'paragraph_index' => 2,
                    'resolved_by' => null,
                    'comments' => [
                        [$editorialAssignee, 'Können wir diesen Absatz kürzen? Er wiederholt den vorherigen Gedanken.'],
                        [$author, 'Guter Punkt, ich schaue mir das in der nächsten Überarbeitung an.'],
                        [$editorialAssignee, 'Perfekt, danke dir!'],
                    ],
                ],
            ],
            12 => [
                [
                    'paragraph_index' => 1,
                    'resolved_by' => null,
                    'comments' => [
                        [$productManager, 'Hier fehlt noch der Bezug zur aktuellen GEG-Novelle.'],
                    ],
                ],
            ],
            default => [],
        };
    }

    /**
     * Collect the body paragraph texts of an article in reading order.
     *
     * @param  list<array{type: string, level?: int, text: string, marginal_note?: string|null}>  $sections
     * @return list<string>
     */
    private function bodyParagraphTexts(array $sections): array
    {
        $texts = [];

        foreach ($sections as $section) {
            if (($section['type'] ?? null) === 'paragraph' && isset($section['text'])) {
                $texts[] = (string) $section['text'];
            }
        }

        return $texts;
    }

    private function anchorPhrase(string $text): string
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_slice($words, 0, 6));
    }

    /**
     * Find the text node whose text matches `$fullText` and wrap its opening
     * phrase in a `comment` mark that references the given thread.
     *
     * @param  array<string, mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function markNodeByText(
        array $node,
        string $fullText,
        string $threadId,
        string &$anchor,
        bool &$applied,
    ): array {
        if (! $applied && ($node['type'] ?? null) === 'text' && ($node['text'] ?? null) === $fullText) {
            $applied = true;
            $anchor = $this->anchorPhrase($fullText);

            return $this->splitTextNodeWithMark($node, $anchor, $threadId);
        }

        if (isset($node['content']) && is_array($node['content'])) {
            $children = [];

            foreach ($node['content'] as $child) {
                if (is_array($child)) {
                    foreach ($this->markNodeByText($child, $fullText, $threadId, $anchor, $applied) as $piece) {
                        $children[] = $piece;
                    }
                } else {
                    $children[] = $child;
                }
            }

            $node['content'] = $children;
        }

        return [$node];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function splitTextNodeWithMark(array $node, string $phrase, string $threadId): array
    {
        $text = (string) $node['text'];
        /** @var list<array<string, mixed>> $marks */
        $marks = $node['marks'] ?? [];
        $after = mb_substr($text, mb_strlen($phrase));

        $commentMark = ['type' => 'comment', 'attrs' => ['threadId' => $threadId]];

        $pieces = [
            $this->makeTextNode($phrase, array_merge($marks, [$commentMark])),
        ];

        if ($after !== '') {
            $pieces[] = $this->makeTextNode($after, $marks);
        }

        return $pieces;
    }

    /**
     * @param  list<array<string, mixed>>  $marks
     * @return array<string, mixed>
     */
    private function makeTextNode(string $text, array $marks): array
    {
        $node = ['type' => 'text', 'text' => $text];

        if ($marks !== []) {
            $node['marks'] = array_values($marks);
        }

        return $node;
    }

    /**
     * Seed an article with a realistic version history so the version comparison
     * feature can be demonstrated: several workflow edits followed by a
     * published snapshot.
     *
     * @param  array<string, PublicationIssue>  $issues
     */
    private function seedVersionComparisonExample(array $issues): void
    {
        $author = User::query()->where('email', 'pia.maier@example.com')->firstOrFail();
        $issue = $issues['magazine-03-2026'];

        $article = Article::query()
            ->where('owner_id', $author->id)
            ->where('publication_issue_id', $issue->id)
            ->orderBy('id')
            ->firstOrFail();

        if ($article->versions()->exists()) {
            return;
        }

        $versionService = app(ArticleVersionService::class);

        /** @var list<array{title: string, status: ArticleStatus, paragraphs: list<string>}> $stages */
        $stages = [
            [
                'title' => 'Wärmepumpen im Bestand',
                'status' => ArticleStatus::Authoring,
                'paragraphs' => [
                    'Wärmepumpen sind eine Option für Neubauten.',
                ],
            ],
            [
                'title' => 'Wärmepumpen im Bestand',
                'status' => ArticleStatus::Authoring,
                'paragraphs' => [
                    'Wärmepumpen sind eine gute Option für Neubauten.',
                    'Im Gebäudebestand ist der Einsatz komplizierter.',
                ],
            ],
            [
                'title' => 'Wärmepumpen im Bestand',
                'status' => ArticleStatus::Authoring,
                'paragraphs' => [
                    'Wärmepumpen sind eine gute Option für Neubauten und Sanierungen.',
                    'Im Gebäudebestand ist der Einsatz mit sorgfältiger Planung gut möglich.',
                ],
            ],
            [
                'title' => 'Wärmepumpen im Gebäudebestand',
                'status' => ArticleStatus::Authoring,
                'paragraphs' => [
                    'Wärmepumpen sind eine gute Option für Neubauten und Sanierungen.',
                    'Im Gebäudebestand ist der Einsatz mit sorgfältiger Planung gut möglich.',
                    'Entscheidend sind die Vorlauftemperatur und der Dämmstandard.',
                ],
            ],
            [
                'title' => 'Wärmepumpen im Gebäudebestand',
                'status' => ArticleStatus::Authoring,
                'paragraphs' => [
                    'Wärmepumpen sind eine gute Option für Neubauten und Sanierungen.',
                    'Im Gebäudebestand ist der Einsatz mit sorgfältiger Planung gut möglich, wenn die Heizlast bekannt ist.',
                    'Entscheidend sind die Vorlauftemperatur und der Dämmstandard des Gebäudes.',
                ],
            ],
            [
                'title' => 'Wärmepumpen im Gebäudebestand',
                'status' => ArticleStatus::Published,
                'paragraphs' => [
                    'Wärmepumpen sind eine gute Option für Neubauten und Sanierungen.',
                    'Im Gebäudebestand ist der Einsatz mit sorgfältiger Planung gut möglich, wenn die Heizlast bekannt ist.',
                    'Entscheidend sind die Vorlauftemperatur und der Dämmstandard des Gebäudes.',
                ],
            ],
            [
                'title' => 'Wärmepumpen im Gebäudebestand',
                'status' => ArticleStatus::Published,
                'paragraphs' => [
                    'Wärmepumpen sind in vielen Fällen eine sehr gute Option für Neubauten und Sanierungen.',
                    'Im Gebäudebestand ist der Einsatz mit sorgfältiger Planung gut möglich, wenn die Heizlast korrekt ermittelt wurde.',
                    'Entscheidend sind die Vorlauftemperatur und der Dämmstandard des Gebäudes.',
                ],
            ],
            [
                'title' => 'Wärmepumpen im Gebäudebestand: Praxisleitfaden',
                'status' => ArticleStatus::Published,
                'paragraphs' => [
                    'Wärmepumpen sind in vielen Fällen eine sehr gute Option für Neubauten und Sanierungen.',
                    'Im Gebäudebestand ist der Einsatz mit sorgfältiger Planung gut möglich, wenn die Heizlast korrekt ermittelt wurde.',
                    'Entscheidend sind die Vorlauftemperatur, der hydraulische Abgleich und der Dämmstandard des Gebäudes.',
                ],
            ],
        ];

        foreach ($stages as $index => $stage) {
            $article->versions()->create([
                'version_number' => $index + 1,
                'title' => $stage['title'],
                'content' => $this->comparisonDoc($stage['paragraphs']),
                'status' => $stage['status'],
                'created_by_id' => $author->id,
                'created_at' => now()->subMinutes(count($stages) - $index),
            ]);
        }

        $versionService->snapshot($article, $author);
    }

    /**
     * @param  list<string>  $paragraphs
     * @return array<string, mixed>
     */
    private function comparisonDoc(array $paragraphs): array
    {
        return [
            'type' => 'doc',
            'content' => array_map(
                fn (string $text): array => [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => $text],
                    ],
                ],
                $paragraphs,
            ),
        ];
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
     * @param  array<string, PublicationIssue>  $issues
     * @return array<string, array<int, PublicationChapter>>
     */
    private function seedChapters(array $issues): array
    {
        $definitions = [
            'book-01-2026' => [
                1 => 'Gebäudehülle und Bestand',
                2 => 'Recht, Daten und Nachhaltigkeit',
            ],
            'book-02-2026' => [
                1 => 'Quartiere und Gebäudeplanung',
                2 => 'Effiziente Gebäudetechnik',
            ],
            'magazine-03-2026' => [
                1 => 'Digitale Gebäudetechnik',
                2 => 'Markt und Regulierung',
            ],
            'magazine-06-2026' => [
                1 => 'Recht und Sanierung',
                2 => 'Messtechnik und Zukunftstechnologien',
            ],
        ];

        $chapters = [];

        foreach ($definitions as $issueKey => $chapterDefinitions) {
            foreach ($chapterDefinitions as $position => $title) {
                $chapters[$issueKey][$position] = PublicationChapter::query()->updateOrCreate(
                    [
                        'publication_issue_id' => $issues[$issueKey]->id,
                        'position' => $position,
                    ],
                    [
                        'title' => $title,
                    ],
                );
            }
        }

        return $chapters;
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
