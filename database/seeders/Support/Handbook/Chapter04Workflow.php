<?php

namespace Database\Seeders\Support\Handbook;

use Database\Seeders\Support\Handbook\Nodes as N;

class Chapter04Workflow
{
    /**
     * @return array{title: string, articles: list<array{title: string, content: list<array<string, mixed>>}>}
     */
    public static function chapter(): array
    {
        return [
            'title' => 'Der Redaktionsworkflow',
            'articles' => [
                [
                    'title' => 'Die Artikel-Status im Überblick',
                    'content' => [
                        N::p(
                            'Jeder Artikel durchläuft auf dem Weg zur Veröffentlichung mehrere Stationen. Der aktuelle ',
                            N::b('Status'),
                            ' zeigt jederzeit, wo der Artikel gerade steht und wer am Zug ist.',
                        ),
                        N::table(
                            ['Status', 'Bedeutung', 'Wer ist am Zug?'],
                            [
                                ['Geplant', 'Der Artikel ist angelegt, aber noch niemandem zur Bearbeitung zugewiesen.', 'Produktmanager'],
                                ['In Bearbeitung', 'Die Autorin oder der Autor schreibt am Manuskript.', 'Autor:in'],
                                ['Manuskript eingereicht', 'Das Manuskript liegt beim Produktmanagement und wartet auf den nächsten Schritt.', 'Produktmanager'],
                                ['Überarbeitung angefordert', 'Die Autorin oder der Autor hat um Änderungen am eingereichten Manuskript gebeten.', 'Produktmanager'],
                                ['In Überarbeitung', 'Der Artikel wurde zur Überarbeitung erneut an eine Autorin oder einen Autor gegeben.', 'Autor:in'],
                                ['Im Lektorat', 'Redaktion oder Lektorat bearbeiten den Text.', 'Redakteur:in / Lektor:in'],
                                ['Produktmanager Korrektur', 'Das Produktmanagement bearbeitet den Text gerade selbst.', 'Produktmanager'],
                                ['Bereit zur Veröffentlichung', 'Der Artikel ist fertig geprüft und kann veröffentlicht werden.', 'Produktmanager'],
                                ['Veröffentlicht', 'Der Artikel ist veröffentlicht und für alle gesperrt.', 'Niemand – abgeschlossen'],
                            ],
                        ),
                        N::info(
                            N::b('Merksatz: '),
                            '„Manuskript eingereicht“ ist die zentrale Drehscheibe des Workflows. Von hier aus entscheidet das Produktmanagement, ob der Text ins Lektorat geht, überarbeitet wird, selbst korrigiert wird oder zur Veröffentlichung freigegeben wird.',
                        ),
                    ],
                ],
                [
                    'title' => 'Der Weg eines Artikels',
                    'content' => [
                        N::p('So sieht der typische Ablauf vom ersten Plan bis zur Veröffentlichung aus:'),
                        N::steps([
                            [
                                N::b('Planen: '),
                                'Der Produktmanager legt den Artikel in der Ausgabenplanung an – mit Titel, Autor:in, Kapitel, Abgabefrist und Ziel-Zeichenanzahl. Der Artikel startet im Status „Geplant“.',
                            ],
                            [
                                N::b('Autor:in zuweisen: '),
                                'Der Produktmanager weist das Manuskript einer Autorin oder einem Autor zu. Der Status wechselt zu „In Bearbeitung“, die Person erscheint als „Aktuell verantwortlich“.',
                            ],
                            [
                                N::b('Schreiben und abgeben: '),
                                'Die Autorin schreibt den Text im Editor und klickt zum Schluss auf „Abgeben“. Der Status wechselt zu „Manuskript eingereicht“ – ab jetzt ist der Text für sie gesperrt.',
                            ],
                            [
                                N::b('Prüfen lassen: '),
                                'Der Produktmanager weist den Artikel der Redaktion oder dem Lektorat zu („Im Lektorat“). Nach Abschluss der Bearbeitung („Fertig“) geht der Artikel zurück an das Produktmanagement.',
                            ],
                            [
                                N::b('Freigeben: '),
                                'Ist der Text fertig, markiert ihn der Produktmanager „Als bereit“ – Status „Bereit zur Veröffentlichung“.',
                            ],
                            [
                                N::b('Veröffentlichen: '),
                                'Mit „Veröffentlichen“ wird der Artikel abgeschlossen. Danach sind keine Änderungen mehr möglich.',
                            ],
                        ]),
                        N::h2('Schleifen sind normal'),
                        N::p('Kaum ein Text ist im ersten Anlauf fertig. Der Workflow sieht deshalb mehrere Schleifen vor:'),
                        N::bullets([
                            [
                                N::b('Überarbeitung durch die Autorin / den Autor: '),
                                'Das Produktmanagement kann ein eingereichtes Manuskript jederzeit wieder einer Autor:in zuweisen – der Artikel wechselt dann in „In Überarbeitung“. Auch die Autorin selbst kann nach der Abgabe eine „Überarbeitung anfordern“, wenn ihr noch etwas auffällt (mit Pflicht-Begründung).',
                            ],
                            [
                                N::b('Mehrere Lektoratsrunden: '),
                                'Nach jeder abgeschlossenen Lektoratsrunde landet der Artikel wieder bei „Manuskript eingereicht“. Von dort kann er erneut ins Lektorat, zu einer anderen Person oder zurück zur Autorin.',
                            ],
                            [
                                N::b('Korrektur durch das Produktmanagement: '),
                                'Der Produktmanager kann den Text auch selbst bearbeiten („Korrektur starten“) und die Korrektur anschließend abschließen.',
                            ],
                        ]),
                        N::h2('Alles wird protokolliert'),
                        N::p(
                            'Jeder Workflow-Schritt wird im ',
                            N::b('Verlauf'),
                            ' des Artikels festgehalten: wer, wann, von welchem Status zu welchem, mit welcher Begründung. ',
                            'Du findest den Verlauf im Editor in der Seitenleiste unter „Verlauf“. So bleibt jede Entscheidung nachvollziehbar.',
                        ),
                    ],
                ],
                [
                    'title' => 'Verantwortlichkeit und Sperren',
                    'content' => [
                        N::h2('Eine Person ist am Zug'),
                        N::p(
                            'In den aktiven Arbeitsphasen hat jeder Artikel genau eine ',
                            N::b('aktuell verantwortliche Person'),
                            '. Nur sie darf den Text in dieser Phase bearbeiten. ',
                            'Wechselt der Status, wechselt in der Regel auch die Verantwortung – zum Beispiel von der Autorin zum Produktmanagement bei der Abgabe.',
                        ),
                        N::h2('Wann ist ein Artikel gesperrt?'),
                        N::bullets([
                            [
                                N::b('Nach der Abgabe: '),
                                'Sobald du dein Manuskript abgegeben hast, kannst du es nicht mehr bearbeiten – erst wieder, wenn es dir zur Überarbeitung zugewiesen wird.',
                            ],
                            [
                                N::b('Wenn jemand anderes am Zug ist: '),
                                'Artikel, bei denen eine andere Person als verantwortlich eingetragen ist, kannst du ansehen, aber nicht ändern.',
                            ],
                            [
                                N::b('Nach der Veröffentlichung: '),
                                'Veröffentlichte Artikel sind endgültig gesperrt. Änderungen sind dann nur noch über einen administrativen Eingriff möglich (siehe Kapitel „Für Administratoren“).',
                            ],
                        ]),
                        N::info(
                            N::b('Keine Angst vor Datenverlust: '),
                            'Bei jedem Speichern entsteht automatisch eine neue Version des Artikels. Ältere Fassungen lassen sich jederzeit vergleichen und wiederherstellen – mehr dazu im Kapitel „Der Editor im Detail“.',
                        ),
                    ],
                ],
            ],
        ];
    }
}
