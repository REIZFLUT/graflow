<?php

namespace Tests\Feature;

use App\Support\TranslationLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        TranslationLoader::clearCache();
    }

    public function test_article_created_message_is_english_by_default(): void
    {
        app()->setLocale('en');

        $this->assertSame(
            'Article created.',
            __('messages.articles.created'),
        );
    }

    public function test_article_created_message_is_german_when_locale_is_de(): void
    {
        app()->setLocale('de');

        $this->assertSame(
            'Artikel erstellt.',
            __('messages.articles.created'),
        );
    }

    public function test_custom_validation_message_is_translated(): void
    {
        app()->setLocale('en');
        $this->assertSame(
            'Categories require a publication assignment.',
            __('validation_custom.categories_require_publication'),
        );

        app()->setLocale('de');
        $this->assertSame(
            'Kategorien erfordern eine Publikationszuordnung.',
            __('validation_custom.categories_require_publication'),
        );
    }
}
