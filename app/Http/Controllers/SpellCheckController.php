<?php

namespace App\Http\Controllers;

use App\Http\Requests\SpellCheckRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpellCheckController extends Controller
{
    public function check(SpellCheckRequest $request): JsonResponse
    {
        $connection = $this->resolveConnection();

        if ($connection === null) {
            return response()->json([
                'message' => 'LanguageTool is not configured.',
                'reason' => 'not_configured',
            ], 503);
        }

        $payload = array_merge($request->payload(), $connection['params']);

        try {
            $response = Http::withHeaders($connection['headers'])
                ->asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(120)
                ->post("{$connection['base_url']}/v2/check", $payload);
        } catch (ConnectionException $exception) {
            Log::warning('LanguageTool connection failed.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'LanguageTool is unavailable.',
                'reason' => 'unavailable',
            ], 503);
        }

        if ($response->failed()) {
            Log::warning('LanguageTool check failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'message' => 'LanguageTool check failed.',
                'reason' => 'failed',
            ], 502);
        }

        /** @var array{matches?: list<array<string, mixed>>} $body */
        $body = $response->json() ?? [];
        $matches = $body['matches'] ?? [];

        return response()->json([
            'matches' => array_map(
                fn (array $match): array => $this->normalizeMatch($match),
                $matches,
            ),
        ]);
    }

    /**
     * Resolve the active LanguageTool connection based on the configured driver.
     *
     * Returns null when the selected driver is missing required credentials.
     *
     * @return array{base_url: string, headers: array<string, string>, params: array<string, string>}|null
     */
    private function resolveConnection(): ?array
    {
        $driver = (string) config('services.languagetool.driver', 'local');

        if ($driver === 'saas') {
            $baseUrl = rtrim((string) config('services.languagetool.api_url'), '/');
            $username = (string) config('services.languagetool.username');
            $apiKey = (string) config('services.languagetool.api_key');

            if ($baseUrl === '' || $username === '' || $apiKey === '') {
                return null;
            }

            return [
                'base_url' => $baseUrl,
                'headers' => [],
                'params' => [
                    'username' => $username,
                    'apiKey' => $apiKey,
                ],
            ];
        }

        $baseUrl = rtrim((string) config('services.languagetool.url'), '/');
        $token = (string) config('services.languagetool.token');

        if ($baseUrl === '' || $token === '') {
            return null;
        }

        return [
            'base_url' => $baseUrl,
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ],
            'params' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $match
     * @return array{
     *     message: string,
     *     shortMessage: string,
     *     offset: int,
     *     length: int,
     *     replacements: list<array{value: string}>,
     *     context: array{text: string, offset: int, length: int},
     *     rule: array{id: string, description: string, category: array{id: string, name: string}}
     * }
     */
    private function normalizeMatch(array $match): array
    {
        /** @var list<array{value?: string}> $replacements */
        $replacements = $match['replacements'] ?? [];

        /** @var array{text?: string, offset?: int, length?: int} $context */
        $context = $match['context'] ?? [];

        /** @var array{id?: string, description?: string, category?: array{id?: string, name?: string}} $rule */
        $rule = $match['rule'] ?? [];

        /** @var array{id?: string, name?: string} $category */
        $category = $rule['category'] ?? [];

        return [
            'message' => (string) ($match['message'] ?? ''),
            'shortMessage' => (string) ($match['shortMessage'] ?? ''),
            'offset' => (int) ($match['offset'] ?? 0),
            'length' => (int) ($match['length'] ?? 0),
            'replacements' => array_values(array_map(
                fn (array $replacement): array => [
                    'value' => (string) ($replacement['value'] ?? ''),
                ],
                $replacements,
            )),
            'context' => [
                'text' => (string) ($context['text'] ?? ''),
                'offset' => (int) ($context['offset'] ?? 0),
                'length' => (int) ($context['length'] ?? 0),
            ],
            'rule' => [
                'id' => (string) ($rule['id'] ?? ''),
                'description' => (string) ($rule['description'] ?? ''),
                'category' => [
                    'id' => (string) ($category['id'] ?? ''),
                    'name' => (string) ($category['name'] ?? ''),
                ],
            ],
        ];
    }
}
