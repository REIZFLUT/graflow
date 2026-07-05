<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Str;

class DemoEditorShowcaseBuilder
{
    /**
     * @return list<array<string, mixed>>
     */
    public function build(bool $withMarginalNotes): array
    {
        $footnoteId = (string) Str::uuid();

        return [
            $this->heading('Gestaltungs- und Strukturbeispiele', 3, null, 'showcase-h3'),
            $this->paragraphWithMarks(
                'Energieberatung verbindet ',
                'technische Analyse',
                ' mit ',
                'wirtschaftlicher Bewertung',
                ' und rechtlicher Einordnung nach GEG.',
                $footnoteId,
                'Vgl. Gebäudeenergiegesetz (GEG), insbesondere Anforderungen an Energieausweise und Sanierungsfahrpläne.',
            ),
            $this->paragraphWithScripts(),
            $this->paragraphWithInlineMath(),
            $this->blockMath(),
            $this->bulletList(),
            $this->orderedList(),
            $this->blockquote($withMarginalNotes),
            $this->authorCommentParagraph(),
            $this->characterFormatParagraph(),
            $this->infoBox(),
            $this->energyTable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function heading(string $text, int $level, ?string $marginalNote, string $id): array
    {
        return [
            'type' => 'heading',
            'attrs' => [
                'level' => $level,
                'id' => $id,
                'marginalNote' => $marginalNote,
            ],
            'content' => [
                ['type' => 'text', 'text' => $text],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paragraphWithMarks(
        string $beforeBold,
        string $boldText,
        string $between,
        string $italicText,
        string $afterItalic,
        string $footnoteId,
        string $footnoteContent,
    ): array {
        return [
            'type' => 'paragraph',
            'attrs' => [
                'id' => 'showcase-formatted-paragraph',
                'marginalNote' => null,
            ],
            'content' => [
                ['type' => 'text', 'text' => $beforeBold],
                [
                    'type' => 'text',
                    'text' => $boldText,
                    'marks' => [['type' => 'bold']],
                ],
                ['type' => 'text', 'text' => $between],
                [
                    'type' => 'text',
                    'text' => $italicText,
                    'marks' => [['type' => 'italic']],
                ],
                ['type' => 'text', 'text' => $afterItalic.' '],
                [
                    'type' => 'text',
                    'text' => 'GEG',
                    'marks' => [[
                        'type' => 'footnote',
                        'attrs' => [
                            'id' => $footnoteId,
                            'content' => $footnoteContent,
                        ],
                    ]],
                ],
                ['type' => 'text', 'text' => '.'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paragraphWithScripts(): array
    {
        return [
            'type' => 'paragraph',
            'attrs' => [
                'id' => 'showcase-scripts',
                'marginalNote' => null,
            ],
            'content' => [
                ['type' => 'text', 'text' => 'Der CO'],
                [
                    'type' => 'text',
                    'text' => '2',
                    'marks' => [['type' => 'subscript']],
                ],
                ['type' => 'text', 'text' => '-Ausstoß je Quadratmeter Nutzfläche sinkt bei guter Hüllqualität auf unter 10 kg/(m'],
                [
                    'type' => 'text',
                    'text' => '2',
                    'marks' => [['type' => 'superscript']],
                ],
                ['type' => 'text', 'text' => '·a).'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paragraphWithInlineMath(): array
    {
        return [
            'type' => 'paragraph',
            'attrs' => [
                'id' => 'showcase-inline-math',
                'marginalNote' => null,
            ],
            'content' => [
                ['type' => 'text', 'text' => 'Der Jahresprimärenergiebedarf lässt sich näherungsweise als '],
                [
                    'type' => 'inlineMath',
                    'attrs' => [
                        'latex' => 'Q = A \\cdot U \\cdot \\Delta T',
                    ],
                ],
                ['type' => 'text', 'text' => ' abschätzen, wobei A die übertragende Fläche bezeichnet.'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blockMath(): array
    {
        return [
            'type' => 'blockMath',
            'attrs' => [
                'latex' => 'Q_{H,eff} = \\sum_{i=1}^{n} (A_i \\cdot U_i \\cdot f_i \\cdot \\Delta T)',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bulletList(): array
    {
        return [
            'type' => 'bulletList',
            'attrs' => [
                'id' => 'showcase-bullet-list',
            ],
            'content' => [
                $this->listItem('Bestandsaufnahme von Hülle und Anlagentechnik'),
                $this->listItem('Variantenvergleich mit Wirtschaftlichkeitsrechnung'),
                $this->listItem('Abstimmung von Fördermitteln und Umsetzungsplan'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderedList(): array
    {
        return [
            'type' => 'orderedList',
            'attrs' => [
                'id' => 'showcase-ordered-list',
            ],
            'content' => [
                $this->listItem('Energieausweis und Verbrauchsanalyse'),
                $this->listItem('Sanierungsfahrplan mit Etappierung'),
                $this->listItem('Qualitätssicherung bei Ausführung und Betrieb'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listItem(string $text): array
    {
        return [
            'type' => 'listItem',
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
    private function blockquote(bool $withMarginalNotes): array
    {
        return [
            'type' => 'blockquote',
            'attrs' => [
                'id' => 'showcase-blockquote',
                'marginalNote' => $withMarginalNotes ? 'Zitat für Entscheider aufbereiten' : null,
            ],
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Energieeffizienz ist kein Selbstzweck, sondern die Grundlage für klimagerechtes Bauen, planbare Betriebskosten und langfristigen Werterhalt.',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function authorCommentParagraph(): array
    {
        return [
            'type' => 'paragraph',
            'attrs' => [
                'id' => 'showcase-autorenkommentar',
                'paragraphFormat' => 'autorenkommentar',
                'marginalNote' => null,
            ],
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'Autorenkommentar: In der Beratungspraxis lohnt es sich, Standardannahmen zu dokumentieren und gegen Messwerte zu prüfen.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function characterFormatParagraph(): array
    {
        return [
            'type' => 'paragraph',
            'attrs' => [
                'id' => 'showcase-character-format',
                'marginalNote' => null,
            ],
            'content' => [
                ['type' => 'text', 'text' => 'Besonders kritisch ist die '],
                [
                    'type' => 'text',
                    'text' => 'Wärmebrückenfreiheit',
                    'marks' => [[
                        'type' => 'characterFormat',
                        'attrs' => [
                            'className' => 'text-red',
                        ],
                    ]],
                ],
                ['type' => 'text', 'text' => ' im Übergang von Innen- zu Außenbauteilen.'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function infoBox(): array
    {
        return [
            'type' => 'infoBox',
            'attrs' => [
                'id' => 'showcase-info-box',
            ],
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Infokasten: Die Bundesförderung für effiziente Gebäude (BEG) bündelt Förderprogramme für Einzelmaßnahmen und Gesamtsanierungen.',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function energyTable(): array
    {
        return [
            'type' => 'table',
            'content' => [
                [
                    'type' => 'tableRow',
                    'content' => [
                        $this->tableHeaderCell('Maßnahme'),
                        $this->tableHeaderCell('Einsparpotenzial'),
                    ],
                ],
                [
                    'type' => 'tableRow',
                    'content' => [
                        $this->tableCell('Fassadendämmung'),
                        $this->tableCell('15–25 % Heizenergie'),
                    ],
                ],
                [
                    'type' => 'tableRow',
                    'content' => [
                        $this->tableCell('Fenstertausch'),
                        $this->tableCell('8–12 % Heizenergie'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tableHeaderCell(string $text): array
    {
        return [
            'type' => 'tableHeader',
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
    private function tableCell(string $text): array
    {
        return [
            'type' => 'tableCell',
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
}
