<?php

namespace App\Ai\Concerns;

use Laravel\Ai\Enums\Lab;

/**
 * Shared configuration for agents that talk to a reasoning model (for example
 * the internal Azure/LiteLLM deployment reached through the OpenAI driver).
 */
trait InteractsWithReasoningModel
{
    protected ?string $model = null;

    protected ?string $reasoningEffort = null;

    protected int $requestTimeout = 120;

    /**
     * The model the agent should use (null falls back to the provider default).
     */
    public function model(): ?string
    {
        return $this->model;
    }

    /**
     * The HTTP timeout in seconds for agent requests.
     */
    public function timeout(): int
    {
        return $this->requestTimeout;
    }

    /**
     * Provider-specific generation options (reasoning effort for OpenAI-style
     * providers). Providers that do not support reasoning receive no options.
     *
     * @return array<string, mixed>
     */
    public function providerOptions(Lab|string $provider): array
    {
        if (blank($this->reasoningEffort)) {
            return [];
        }

        return match ($provider) {
            Lab::OpenAI, Lab::Azure, Lab::OpenAICompatible => [
                'reasoning' => ['effort' => $this->reasoningEffort],
            ],
            default => [],
        };
    }
}
