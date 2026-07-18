<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpellCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.languagetool.url' => 'http://languagetool.test',
            'services.languagetool.token' => 'test-token',
        ]);
    }

    public function test_guest_cannot_check_spelling(): void
    {
        $this->postJson(route('spellcheck.check'), [
            'text' => 'Das ist ein Test.',
        ])->assertUnauthorized();
    }

    public function test_validation_requires_text(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('spellcheck.check'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    public function test_successful_check_proxies_to_languagetool(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://languagetool.test/v2/check' => Http::response([
                'matches' => [
                    [
                        'message' => 'Möglicherweise falsch geschrieben.',
                        'shortMessage' => 'Rechtschreibfehler',
                        'offset' => 10,
                        'length' => 5,
                        'replacements' => [
                            ['value' => 'Fehler'],
                        ],
                        'context' => [
                            'text' => 'mit ein Fehler',
                            'offset' => 4,
                            'length' => 5,
                        ],
                        'rule' => [
                            'id' => 'GERMAN_SPELLER_RULE',
                            'description' => 'Rechtschreibprüfung',
                            'category' => [
                                'id' => 'TYPOS',
                                'name' => 'Tippfehler',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('spellcheck.check'), [
            'text' => 'Das ist ein Fehler.',
            'language' => 'de-DE',
            'level' => 'picky',
        ]);

        $response->assertOk()
            ->assertJsonPath('matches.0.shortMessage', 'Rechtschreibfehler')
            ->assertJsonPath('matches.0.replacements.0.value', 'Fehler')
            ->assertJsonPath('matches.0.rule.category.id', 'TYPOS');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://languagetool.test/v2/check'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['text'] === 'Das ist ein Fehler.'
                && $request['language'] === 'de-DE'
                && $request['level'] === 'picky';
        });
    }

    public function test_defaults_language_and_level_when_omitted(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://languagetool.test/v2/check' => Http::response([
                'matches' => [],
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('spellcheck.check'), [
                'text' => 'Hallo Welt.',
            ])
            ->assertOk()
            ->assertJsonPath('matches', []);

        Http::assertSent(function (Request $request): bool {
            return $request['language'] === 'de-DE'
                && $request['level'] === 'picky';
        });
    }

    public function test_unavailable_service_returns_503(): void
    {
        Http::preventStrayRequests();
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('spellcheck.check'), [
                'text' => 'Hallo Welt.',
            ])
            ->assertStatus(503)
            ->assertJsonPath('message', 'LanguageTool is unavailable.');
    }

    public function test_upstream_failure_returns_502(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://languagetool.test/v2/check' => Http::response('error', 500),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('spellcheck.check'), [
                'text' => 'Hallo Welt.',
            ])
            ->assertStatus(502)
            ->assertJsonPath('message', 'LanguageTool check failed.');
    }

    public function test_missing_configuration_returns_503(): void
    {
        config([
            'services.languagetool.url' => '',
            'services.languagetool.token' => '',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('spellcheck.check'), [
                'text' => 'Hallo Welt.',
            ])
            ->assertStatus(503)
            ->assertJsonPath('message', 'LanguageTool is not configured.');
    }
}
