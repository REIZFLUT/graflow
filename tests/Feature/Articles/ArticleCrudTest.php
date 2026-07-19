<?php

namespace Tests\Feature\Articles;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ArticleCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function tipTapContent(string $text = 'Hello'): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => $text],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tipTapContentWithMarginalNoteAndFootnote(): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'heading',
                    'attrs' => [
                        'level' => 2,
                        'id' => 'heading-block-1',
                        'marginalNote' => 'Erläuterung zur Überschrift',
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => 'Einleitung'],
                    ],
                ],
                [
                    'type' => 'paragraph',
                    'attrs' => [
                        'id' => 'paragraph-block-1',
                        'marginalNote' => null,
                    ],
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'markiertes Wort',
                            'marks' => [
                                [
                                    'type' => 'footnote',
                                    'attrs' => [
                                        'id' => 'footnote-ref-1',
                                        'content' => 'Fußnotentext',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_guests_are_redirected_from_articles_index(): void
    {
        $this->get(route('articles.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_articles_index(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'owner_id' => $user->id,
            'author_id' => $user->id,
            'current_assignee_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('articles.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data.0.id', $article->id)
                ->where('articles.data.0.author.name', $user->name)
                ->where('articles.data.0.current_assignee.name', $user->name));
    }

    public function test_product_managers_are_redirected_from_generic_create_page(): void
    {
        $user = User::factory()->productManager()->create();

        $this->actingAs($user)
            ->get(route('articles.create'))
            ->assertRedirect(route('publications.index'));
    }

    public function test_generic_store_redirects_without_creating_an_article(): void
    {
        $user = User::factory()->productManager()->create();

        $this->actingAs($user)
            ->post(route('articles.store'), [
                'title' => 'My Article',
                'content' => $this->tipTapContent(),
            ])
            ->assertRedirect(route('publications.index'));

        $this->assertDatabaseEmpty('articles');
    }

    public function test_authenticated_users_can_view_edit_page_for_own_article(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->get(route('articles.edit', $article))
            ->assertOk();
    }

    public function test_edit_page_includes_version_status(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create([
            'status' => ArticleStatus::Published,
        ]);
        $article->versions()->create([
            'version_number' => 1,
            'title' => $article->title,
            'content' => $article->content,
            'status' => ArticleStatus::Published,
            'created_by_id' => $user->id,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('articles.edit', $article))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('article.versions.0.status', ArticleStatus::Published->value)
            );
    }

    public function test_update_persists_changes(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => 'Updated Title',
                'content' => $this->tipTapContent('Updated'),
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();

        $this->assertSame('Updated Title', $article->title);
        $this->assertSame('Updated', $article->content['content'][0]['content'][0]['text']);
    }

    public function test_update_does_not_change_workflow_status(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create([
            'status' => ArticleStatus::Authoring,
        ]);

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => $article->title,
                'content' => $this->tipTapContent(),
                'status' => ArticleStatus::Published->value,
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();

        $this->assertSame(ArticleStatus::Authoring, $article->status);
    }

    public function test_update_ignores_status_input(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => $article->title,
                'content' => $this->tipTapContent(),
                'status' => 'invalid-status',
            ])
            ->assertRedirect(route('articles.edit', $article));

        $this->assertSame(ArticleStatus::Authoring, $article->refresh()->status);
    }

    public function test_update_persists_marginal_notes_and_footnotes(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();
        $content = $this->tipTapContentWithMarginalNoteAndFootnote();

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => 'Article With Marginalia',
                'content' => $content,
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(
            'Erläuterung zur Überschrift',
            $article->content['content'][0]['attrs']['marginalNote'],
        );
        $this->assertSame(
            'footnote',
            $article->content['content'][1]['content'][0]['marks'][0]['type'],
        );
        $this->assertSame(
            'Fußnotentext',
            $article->content['content'][1]['content'][0]['marks'][0]['attrs']['content'],
        );
        $this->assertSame(
            'markiertes Wort',
            $article->content['content'][1]['content'][0]['text'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function tipTapContentWithSuperscriptAndSubscript(): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'CO'],
                        [
                            'type' => 'text',
                            'text' => '2',
                            'marks' => [
                                ['type' => 'subscript'],
                            ],
                        ],
                        ['type' => 'text', 'text' => ' und 10 m'],
                        [
                            'type' => 'text',
                            'text' => '2',
                            'marks' => [
                                ['type' => 'superscript'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_update_persists_superscript_and_subscript(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();
        $content = $this->tipTapContentWithSuperscriptAndSubscript();

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => 'Article With Script Marks',
                'content' => $content,
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(
            'subscript',
            $article->content['content'][0]['content'][1]['marks'][0]['type'],
        );
        $this->assertSame(
            'superscript',
            $article->content['content'][0]['content'][3]['marks'][0]['type'],
        );
        $this->assertSame('2', $article->content['content'][0]['content'][1]['text']);
        $this->assertSame('2', $article->content['content'][0]['content'][3]['text']);
    }

    /**
     * @return array<string, mixed>
     */
    private function tipTapContentWithMath(): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'E = '],
                        [
                            'type' => 'inlineMath',
                            'attrs' => [
                                'latex' => 'mc^2',
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'blockMath',
                    'attrs' => [
                        'latex' => '\\sum_{i=1}^{n} x_i',
                    ],
                ],
            ],
        ];
    }

    public function test_update_persists_inline_and_block_math(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();
        $content = $this->tipTapContentWithMath();

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => 'Article With Math',
                'content' => $content,
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(
            'inlineMath',
            $article->content['content'][0]['content'][1]['type'],
        );
        $this->assertSame(
            'mc^2',
            $article->content['content'][0]['content'][1]['attrs']['latex'],
        );
        $this->assertSame(
            'blockMath',
            $article->content['content'][1]['type'],
        );
        $this->assertSame(
            '\\sum_{i=1}^{n} x_i',
            $article->content['content'][1]['attrs']['latex'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function tipTapContentWithTable(): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'table',
                    'content' => [
                        [
                            'type' => 'tableRow',
                            'content' => [
                                [
                                    'type' => 'tableHeader',
                                    'content' => [
                                        [
                                            'type' => 'paragraph',
                                            'content' => [
                                                ['type' => 'text', 'text' => 'Spalte A'],
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'tableHeader',
                                    'content' => [
                                        [
                                            'type' => 'paragraph',
                                            'content' => [
                                                ['type' => 'text', 'text' => 'Spalte B'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'tableRow',
                            'content' => [
                                [
                                    'type' => 'tableCell',
                                    'content' => [
                                        [
                                            'type' => 'paragraph',
                                            'content' => [
                                                ['type' => 'text', 'text' => 'Wert 1'],
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'tableCell',
                                    'content' => [
                                        [
                                            'type' => 'paragraph',
                                            'content' => [
                                                ['type' => 'text', 'text' => 'Wert 2'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_update_persists_tables(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();
        $content = $this->tipTapContentWithTable();

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => 'Article With Table',
                'content' => $content,
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame('table', $article->content['content'][0]['type']);
        $this->assertSame(
            'tableHeader',
            $article->content['content'][0]['content'][0]['content'][0]['type'],
        );
        $this->assertSame(
            'Spalte A',
            $article->content['content'][0]['content'][0]['content'][0]['content'][0]['content'][0]['text'],
        );
        $this->assertSame(
            'Wert 2',
            $article->content['content'][0]['content'][1]['content'][1]['content'][0]['content'][0]['text'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function tipTapContentWithFootnoteAndSurroundingSpaces(): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'vor '],
                        [
                            'type' => 'text',
                            'text' => 'Wort',
                            'marks' => [
                                [
                                    'type' => 'footnote',
                                    'attrs' => [
                                        'id' => 'footnote-ref-1',
                                        'content' => 'Fußnotentext',
                                    ],
                                ],
                            ],
                        ],
                        ['type' => 'text', 'text' => ' nach'],
                    ],
                ],
            ],
        ];
    }

    public function test_update_preserves_whitespace_around_footnote_marks(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();
        $content = $this->tipTapContentWithFootnoteAndSurroundingSpaces();

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => 'Whitespace Around Footnote',
                'content' => $content,
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();

        $paragraphContent = $article->content['content'][0]['content'];

        $this->assertSame('vor ', $paragraphContent[0]['text']);
        $this->assertSame('Wort', $paragraphContent[1]['text']);
        $this->assertSame(' nach', $paragraphContent[2]['text']);
        $this->assertSame(
            'footnote',
            $paragraphContent[1]['marks'][0]['type'],
        );
    }

    public function test_update_creates_version_with_marginal_notes_and_footnotes(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();
        $content = $this->tipTapContentWithMarginalNoteAndFootnote();

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => 'Updated With Marginalia',
                'content' => $content,
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $version = $article->versions()->latest('version_number')->first();

        $this->assertNotNull($version);
        $this->assertSame(
            'Erläuterung zur Überschrift',
            $version->content['content'][0]['attrs']['marginalNote'],
        );
        $this->assertSame(
            'Fußnotentext',
            $version->content['content'][1]['content'][0]['marks'][0]['attrs']['content'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function tipTapContentWithSpecialFormats(): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'attrs' => [
                        'id' => 'paragraph-autorenkommentar',
                        'paragraphFormat' => 'autorenkommentar',
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => 'Ein Autorenkommentar.'],
                    ],
                ],
                [
                    'type' => 'paragraph',
                    'attrs' => [
                        'id' => 'paragraph-character-format',
                    ],
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Wichtiger Begriff',
                            'marks' => [
                                [
                                    'type' => 'characterFormat',
                                    'attrs' => [
                                        'className' => 'text-red',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'infoBox',
                    'attrs' => [
                        'id' => 'info-box-1',
                    ],
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                ['type' => 'text', 'text' => 'Zusatzinformation.'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_update_persists_special_formats_and_block_elements(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();
        $content = $this->tipTapContentWithSpecialFormats();

        $this->actingAs($user)
            ->put(route('articles.update', $article), [
                'title' => 'Article With Special Formats',
                'content' => $content,
            ])
            ->assertRedirect(route('articles.edit', $article));

        $article->refresh();
        $this->assertSame(
            'autorenkommentar',
            $article->content['content'][0]['attrs']['paragraphFormat'],
        );
        $this->assertSame(
            'characterFormat',
            $article->content['content'][1]['content'][0]['marks'][0]['type'],
        );
        $this->assertSame(
            'text-red',
            $article->content['content'][1]['content'][0]['marks'][0]['attrs']['className'],
        );
        $this->assertSame(
            'infoBox',
            $article->content['content'][2]['type'],
        );
        $this->assertSame(
            'Zusatzinformation.',
            $article->content['content'][2]['content'][0]['content'][0]['text'],
        );
    }
}
