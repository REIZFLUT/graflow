<?php

namespace Tests\Feature;

use App\Ai\Agents\ProofreadingAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProofreadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ai_lektorat.enabled' => true,
            'services.ai_lektorat.provider' => 'openai',
            'services.ai_lektorat.model' => 'azure/gpt-5.3-codex',
            'services.ai_lektorat.reasoning_effort' => 'medium',
            'ai.default' => 'openai',
            'ai.providers.openai.key' => 'test-key',
        ]);
    }

    public function test_guest_cannot_proofread(): void
    {
        $this->postJson(route('proofread.check'), [
            'text' => 'Das ist ein Test.',
        ])->assertUnauthorized();
    }

    public function test_validation_requires_text(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('proofread.check'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    public function test_successful_review_returns_issues(): void
    {
        ProofreadingAgent::fake([
            [
                'issues' => [
                    [
                        'category' => 'word_repetition',
                        'quote' => 'schön schön',
                        'message' => 'Das Wort "schön" steht doppelt.',
                        'suggestion' => 'schön',
                        'severity' => 'warning',
                    ],
                ],
            ],
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('proofread.check'), [
                'text' => 'Der Tag war schön schön.',
            ])
            ->assertOk()
            ->assertJsonPath('issues.0.category', 'word_repetition')
            ->assertJsonPath('issues.0.quote', 'schön schön')
            ->assertJsonPath('issues.0.suggestion', 'schön')
            ->assertJsonPath('issues.0.severity', 'warning');
    }

    public function test_unknown_category_and_severity_are_normalized(): void
    {
        ProofreadingAgent::fake([
            [
                'issues' => [
                    [
                        'category' => 'made_up',
                        'quote' => 'irgendein Satz',
                        'message' => 'Hinweis.',
                        'severity' => 'critical',
                    ],
                    [
                        'category' => 'colloquialism',
                        'quote' => '',
                        'message' => 'Wird verworfen, weil das Zitat fehlt.',
                    ],
                ],
            ],
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('proofread.check'), [
                'text' => 'irgendein Satz steht hier.',
            ])
            ->assertOk()
            ->assertJsonCount(1, 'issues')
            ->assertJsonPath('issues.0.category', 'other')
            ->assertJsonPath('issues.0.severity', 'warning');
    }

    public function test_missing_configuration_returns_503(): void
    {
        config(['ai.providers.openai.key' => '']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('proofread.check'), [
                'text' => 'Hallo Welt.',
            ])
            ->assertStatus(503)
            ->assertJsonPath('reason', 'not_configured');
    }

    public function test_disabled_feature_returns_503(): void
    {
        config(['services.ai_lektorat.enabled' => false]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('proofread.check'), [
                'text' => 'Hallo Welt.',
            ])
            ->assertStatus(503)
            ->assertJsonPath('reason', 'not_configured');
    }

    public function test_provider_failure_returns_502(): void
    {
        ProofreadingAgent::fake(function (): void {
            throw new RuntimeException('Upstream boom');
        });

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('proofread.check'), [
                'text' => 'Hallo Welt.',
            ])
            ->assertStatus(502)
            ->assertJsonPath('reason', 'failed');
    }
}
