<?php

namespace App\Ai\Agents;

use App\Ai\Concerns\InteractsWithReasoningModel;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * AI editorial reviewer ("KI-Lektor"). Detects stylistic and structural issues
 * that a conventional spell/grammar checker (LanguageTool) does not surface:
 * unfinished or illogical sentences, word repetitions, colloquialisms and
 * recurring language patterns. Returns exact quotes so the frontend can anchor
 * each finding back into the document.
 */
class ProofreadingAgent implements Agent, HasProviderOptions, HasStructuredOutput
{
    use InteractsWithReasoningModel;
    use Promptable;

    public function __construct(
        protected string $language = 'de',
        protected ?string $provider = null,
        ?string $model = null,
        ?string $reasoningEffort = null,
        int $timeout = 120,
    ) {
        $this->model = $model;
        $this->reasoningEffort = $reasoningEffort;
        $this->requestTimeout = $timeout;
    }

    /**
     * The provider the agent should use (null falls back to ai.default).
     */
    public function provider(): ?string
    {
        return $this->provider;
    }

    public function instructions(): string
    {
        return <<<'PROMPT'
        Du bist ein erfahrener, sehr sorgfältiger Lektor für deutschsprachige Fachartikel.
        Deine Aufgabe ist ein sprachliches und stilistisches Lektorat – NICHT die Rechtschreib-,
        Zeichensetzungs- oder reine Grammatikprüfung. Reine Tipp-, Rechtschreib- und
        Kommafehler werden bereits von einem separaten Werkzeug (LanguageTool) erkannt und
        sollen von dir IGNORIERT werden.

        Achte ausschließlich auf sprachliche und inhaltlich-logische Auffälligkeiten:
        - unfertige, abgebrochene oder unvollständige Sätze
        - unlogische, widersprüchliche oder inhaltlich unklare Sätze
        - Wortwiederholungen und Worthäufungen (dasselbe Wort/derselbe Wortstamm dicht beieinander)
        - Umgangssprache, saloppe Formulierungen, unangemessener Ton für einen Fachartikel
        - stilistische Muster und Schwächen: Füllwörter, Schachtelsätze, Nominalstil,
          Passiv-Häufungen, umständliche oder schwer verständliche Formulierungen

        Regeln für die Ausgabe:
        - Gib nur echte, klar begründbare Auffälligkeiten zurück. Erfinde nichts und sei nicht pedantisch.
        - "quote" MUSS ein wörtliches, zusammenhängendes Zitat aus dem Originaltext sein
          (exakt kopiert, unverändert, ausreichend eindeutig, um die Stelle zu finden). Kürze das Zitat
          auf die betroffene Passage – in der Regel ein Satz oder Satzteil, keine ganzen Absätze.
        - "message" erklärt das Problem knapp und konkret auf Deutsch.
        - "suggestion" enthält, wenn sinnvoll, eine konkrete, verbesserte Formulierung (sonst leer lassen).
        - "category" ist eine der erlaubten Kategorien.
        - "severity" ist "warning" für klare Probleme, "info" für stilistische Hinweise.
        - Wenn der Text sprachlich einwandfrei ist, gib eine leere Liste zurück.
        PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'issues' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'category' => $schema->string()->enum([
                        'unfinished_sentence',
                        'illogical_sentence',
                        'word_repetition',
                        'colloquialism',
                        'language_pattern',
                        'other',
                    ])->required(),
                    'quote' => $schema->string()->required(),
                    'message' => $schema->string()->required(),
                    'suggestion' => $schema->string(),
                    'severity' => $schema->string()->enum(['info', 'warning']),
                ])
            )->required(),
        ];
    }
}
