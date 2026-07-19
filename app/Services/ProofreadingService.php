<?php

namespace App\Services;

use App\Ai\Agents\ProofreadingAgent;
use Laravel\Ai\Responses\StructuredAgentResponse;

/**
 * Orchestration layer for the AI editorial review ("KI-Lektorat"). Resolves the
 * configured provider/model from config/services.php (credentials come from
 * config/ai.php via the .env), prompts the {@see ProofreadingAgent} and
 * normalizes its structured output into a predictable list of issues.
 */
class ProofreadingService
{
    /**
     * Whether the AI review is enabled and a provider key is configured.
     */
    public function isConfigured(): bool
    {
        if (! (bool) config('services.ai_lektorat.enabled', true)) {
            return false;
        }

        $provider = $this->provider() ?? (string) config('ai.default');

        return filled(config("ai.providers.{$provider}.key"));
    }

    /**
     * Run the editorial review over the given plain text and return the found
     * issues. Throws on any provider/transport failure.
     *
     * @return list<array{category: string, quote: string, message: string, suggestion: string, severity: string}>
     */
    public function check(string $text, ?string $language = null): array
    {
        $agent = new ProofreadingAgent(
            language: $language ?? (string) config('services.ai_lektorat.language', 'de'),
            provider: $this->provider(),
            model: $this->model(),
            reasoningEffort: $this->reasoningEffort(),
        );

        $response = $agent->prompt($this->buildPrompt($text));

        $structured = $response instanceof StructuredAgentResponse
            ? $response->toArray()
            : [];

        /** @var list<mixed> $issues */
        $issues = $structured['issues'] ?? [];

        return $this->normalizeIssues($issues);
    }

    /**
     * Build the user prompt that carries the article text.
     */
    protected function buildPrompt(string $text): string
    {
        return 'Lektoriere den folgenden Artikeltext und gib die gefundenen sprachlichen '
            ."Auffälligkeiten als strukturierte Liste zurück.\n\n"
            ."--- ARTIKELTEXT ---\n{$text}\n--- ENDE ---";
    }

    protected function provider(): ?string
    {
        $provider = config('services.ai_lektorat.provider');

        return filled($provider) ? (string) $provider : null;
    }

    protected function model(): ?string
    {
        $model = config('services.ai_lektorat.model');

        return filled($model) ? (string) $model : null;
    }

    protected function reasoningEffort(): ?string
    {
        $effort = config('services.ai_lektorat.reasoning_effort');

        return filled($effort) ? (string) $effort : null;
    }

    /**
     * Normalize and validate the raw structured issues from the model.
     *
     * @param  list<mixed>  $issues
     * @return list<array{category: string, quote: string, message: string, suggestion: string, severity: string}>
     */
    protected function normalizeIssues(array $issues): array
    {
        $allowedCategories = [
            'unfinished_sentence',
            'illogical_sentence',
            'word_repetition',
            'colloquialism',
            'language_pattern',
            'other',
        ];

        $normalized = [];

        foreach ($issues as $issue) {
            if (! is_array($issue)) {
                continue;
            }

            $quote = trim((string) ($issue['quote'] ?? ''));

            if ($quote === '') {
                continue;
            }

            $category = (string) ($issue['category'] ?? 'other');
            $severity = (string) ($issue['severity'] ?? 'warning');

            $normalized[] = [
                'category' => in_array($category, $allowedCategories, true) ? $category : 'other',
                'quote' => $quote,
                'message' => trim((string) ($issue['message'] ?? '')),
                'suggestion' => trim((string) ($issue['suggestion'] ?? '')),
                'severity' => in_array($severity, ['info', 'warning'], true) ? $severity : 'warning',
            ];
        }

        return $normalized;
    }
}
