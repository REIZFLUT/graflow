<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TranslationLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InertiaLocaleSharingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        TranslationLoader::clearCache();
    }

    public function test_inertia_shares_locale_and_translations_on_dashboard(): void
    {
        $user = User::factory()->create();

        app()->setLocale('en');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'en')
                ->has('translations.nav')
                ->where('translations.nav.dashboard', 'Dashboard')
            );
    }

    public function test_inertia_shares_german_translations_when_locale_is_de(): void
    {
        $user = User::factory()->create();

        app()->setLocale('de');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'de')
                ->where('translations.nav.articles', 'Artikel')
            );
    }

    public function test_inertia_shares_publication_contributor_translations(): void
    {
        $user = User::factory()->create();

        app()->setLocale('de');

        $this->actingAs($user)
            ->get(route('publications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('translations.publications.view', 'Ansehen')
                ->where('translations.publications.owned_by', 'Eigentümer: :name')
                ->where('translations.publications.owner_notice', 'Diese Publikation gehört :name. Du kannst sie nur ansehen.')
            );
    }
}
