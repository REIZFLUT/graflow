<?php

namespace Database\Seeders\Support\Handbook;

use Database\Seeders\Support\Handbook\Nodes as N;

class Chapter03RollenUndRechte
{
    /**
     * @return array{title: string, articles: list<array{title: string, content: list<array<string, mixed>>}>}
     */
    public static function chapter(): array
    {
        return [
            'title' => 'Rollen und Rechte',
            'articles' => [
                [
                    'title' => 'Die fünf Rollen im Überblick',
                    'content' => [
                        N::p(
                            'Jedes Benutzerkonto in Graflow hat genau eine Rolle. Die Rolle bestimmt, welche Bereiche du siehst und welche Aktionen du ausführen darfst. ',
                            'Die Rolle wird vom Administrator beim Anlegen des Kontos vergeben.',
                        ),
                        N::table(
                            ['Rolle', 'Kernaufgabe'],
                            [
                                ['Administrator', 'Verwaltet Benutzer, hat vollen Zugriff auf alle Inhalte und kann in den Workflow eingreifen.'],
                                ['Produktmanager', 'Plant Publikationen und Ausgaben, legt Artikel an, verteilt Aufgaben, gibt frei und veröffentlicht.'],
                                ['Redakteur:in', 'Bearbeitet eingereichte Manuskripte redaktionell.'],
                                ['Lektor:in', 'Prüft eingereichte Manuskripte sprachlich und inhaltlich.'],
                                ['Autor:in', 'Schreibt Manuskripte zu beauftragten Artikeln und gibt sie ab.'],
                            ],
                        ),
                        N::p(
                            'Redakteur:in und Lektor:in haben in Graflow dieselben Möglichkeiten: Beide bekommen Artikel vom Produktmanagement zur redaktionellen Bearbeitung zugewiesen. ',
                            'Die getrennten Rollen dienen der Unterscheidung, wer welche Art von Prüfung übernimmt.',
                        ),
                        N::h2('Was darf welche Rolle?'),
                        N::table(
                            ['Aktion', 'Admin', 'Produktmanager', 'Redaktion/Lektorat', 'Autor:in'],
                            [
                                ['Benutzer verwalten', 'Ja', 'Nein', 'Nein', 'Nein'],
                                ['Publikationen und Ausgaben anlegen', 'Ja', 'Ja', 'Nein', 'Nein'],
                                ['Artikel planen und Aufgaben zuweisen', 'Ja', 'Ja (eigene Artikel)', 'Nein', 'Nein'],
                                ['Manuskripte schreiben und abgeben', 'Ja', 'Nein', 'Nein', 'Ja (wenn zugewiesen)'],
                                ['Eingereichte Texte redigieren', 'Ja', 'Ja (eigene Korrektur)', 'Ja (wenn zugewiesen)', 'Nein'],
                                ['Artikel veröffentlichen', 'Ja', 'Ja (eigene Artikel)', 'Nein', 'Nein'],
                                ['Editor-Einstellungen-Sets verwalten', 'Ja', 'Ja', 'Ja', 'Nein'],
                                ['Status administrativ erzwingen', 'Ja', 'Nein', 'Nein', 'Nein'],
                            ],
                        ),
                        N::info(
                            N::b('Wichtig für Produktmanager: '),
                            '„Eigene Artikel“ bedeutet: Du kannst den Workflow nur für Artikel steuern, bei denen du selbst als Produktmanager eingetragen bist. Artikel anderer Produktmanager kannst du sehen, aber nicht steuern.',
                        ),
                    ],
                ],
                [
                    'title' => 'Wer sieht was?',
                    'content' => [
                        N::h2('Sichtbarkeit von Publikationen'),
                        N::p('Nicht jede Person sieht alle Publikationen. Die Regel ist einfach:'),
                        N::bullets([
                            [N::b('Administratoren'), ' sehen alle Publikationen.'],
                            [
                                N::b('Alle anderen'),
                                ' sehen Publikationen, die ihnen gehören oder an denen sie beteiligt sind – zum Beispiel weil sie dort einen Artikel schreiben, redigieren oder verantworten.',
                            ],
                        ]),
                        N::p(
                            'Gehört eine Publikation einer anderen Person, kannst du sie nur ansehen, aber nicht verändern. Graflow zeigt dir in diesem Fall einen entsprechenden Hinweis an.',
                        ),
                        N::h2('Sichtbarkeit von Artikeln'),
                        N::p(
                            'In deiner Aufgabenliste erscheinen Artikel, bei denen du eine Rolle spielst: als Autor:in, als aktuell verantwortliche Person, als Produktmanager des Artikels oder als frühere Beteiligte. ',
                            'Sobald du an einem Artikel mitgearbeitet hast, bleibst du als beteiligte Person vermerkt und behältst Einblick in den Artikel.',
                        ),
                        N::h2('Bearbeiten ist nicht dasselbe wie Sehen'),
                        N::p(
                            'Auch wenn du einen Artikel sehen kannst, darfst du ihn nur bearbeiten, wenn du gerade am Zug bist – also als ',
                            N::b('aktuell verantwortliche Person'),
                            ' eingetragen bist und der Artikel in einem Status ist, der die Bearbeitung erlaubt. ',
                            'Veröffentlichte Artikel sind für alle gesperrt und können nur noch gelesen werden.',
                        ),
                    ],
                ],
            ],
        ];
    }
}
