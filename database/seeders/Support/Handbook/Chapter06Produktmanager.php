<?php

namespace Database\Seeders\Support\Handbook;

use Database\Seeders\Support\Handbook\Nodes as N;

class Chapter06Produktmanager
{
    /**
     * @return array{title: string, articles: list<array{title: string, content: list<array<string, mixed>>}>}
     */
    public static function chapter(): array
    {
        return [
            'title' => 'Für Produktmanager',
            'articles' => [
                [
                    'title' => 'Publikationen und Ausgaben anlegen',
                    'content' => [
                        N::p(
                            'Als Produktmanager legst du die Grundstruktur an, in der später alle Artikel entstehen: die Publikation, ihre Ausgaben und die Kategorien.',
                        ),
                        N::img(
                            'publikationen.png',
                            'Die Publikationsübersicht mit Namen, Ausgaben und Eigentümern',
                            'Der Bereich Publikationen: alle Magazine mit ihren Ausgaben im Überblick.',
                        ),
                        N::h2('Eine neue Publikation anlegen'),
                        N::steps([
                            ['Öffne ', N::b('Publikationen'), ' und klicke auf ', N::b('„Neue Publikation“'), '.'],
                            'Vergib einen Namen, zum Beispiel „Energieberater Magazin“.',
                            [
                                'Wähle ein ', N::b('Editor-Einstellungen-Set'), '. Es legt fest, mit welcher Schriftart und ob mit Marginalspalte die Artikel dieser Publikation im Editor dargestellt werden. Falls noch kein Set existiert, lege zuerst eines unter „Editor-Einstellungen“ an.',
                            ],
                        ]),
                        N::h2('Ausgaben verwalten'),
                        N::p(
                            'Innerhalb der Publikation legst du ',
                            N::b('Ausgaben'),
                            ' an, zum Beispiel „07-2026“. Jede Ausgabe hat eine eigene Planung mit Kapiteln und Artikeln. ',
                            'Beim Löschen einer Ausgabe verlieren die zugeordneten Artikel ihre Zuordnung – sie werden nicht gelöscht.',
                        ),
                        N::h2('Kategorien'),
                        N::p(
                            'Pro Publikation kannst du ',
                            N::b('Kategorien'),
                            ' anlegen (z. B. „Markt & Politik“). Kategorien werden Artikeln über deren Metadaten zugeordnet und helfen, Inhalte thematisch zu ordnen.',
                        ),
                        N::info(
                            N::b('Hinweis: '),
                            'Publikationen, die dir nicht gehören, kannst du nur ansehen. Alle Verwaltungsfunktionen stehen dir nur bei deinen eigenen Publikationen zur Verfügung.',
                        ),
                    ],
                ],
                [
                    'title' => 'Ausgabenplanung: Kapitel und Artikel planen',
                    'content' => [
                        N::p(
                            'Die ',
                            N::b('Planung'),
                            ' einer Ausgabe ist deine Kommandozentrale: Hier legst du die Kapitelstruktur fest und beauftragst Artikel bei deinen Autorinnen und Autoren. ',
                            'Du erreichst sie über die Publikation → Ausgabe → ',
                            N::b('„Planung“'),
                            '.',
                        ),
                        N::img(
                            'planung.png',
                            'Die Ausgabenplanung mit Kapiteln, Artikelformular und Artikelübersicht',
                            'Die Ausgabenplanung: Kapitel festlegen, Artikel beauftragen, Überblick behalten.',
                        ),
                        N::h2('Kapitel anlegen'),
                        N::p(
                            'Lege zunächst die Kapitel der Ausgabe mit Titel und Position an. Die Position bestimmt die Reihenfolge in der fertigen Ausgabe. ',
                            'Beim Löschen eines Kapitels bleiben dessen Artikel erhalten, sind danach aber keinem Kapitel mehr zugeordnet.',
                        ),
                        N::h2('Einen Artikel planen'),
                        N::p('Mit dem Formular „Artikel planen“ erstellst du einen Artikelauftrag:'),
                        N::bullets([
                            [N::b('Titel'), ' – der Arbeitstitel des Artikels.'],
                            [N::b('Autor'), ' – die Person, die das Manuskript schreiben soll.'],
                            [N::b('Kapitel und Artikelposition'), ' – wo der Artikel in der Ausgabe stehen soll.'],
                            [N::b('Abgabefrist'), ' – bis wann das Manuskript vorliegen soll.'],
                            [N::b('Ziel-Zeichenanzahl'), ' – die gewünschte Textlänge. Die Autorin sieht ihren Fortschritt live im Editor.'],
                        ]),
                        N::p(
                            'Der Artikel startet im Status ',
                            N::b('„Geplant“'),
                            '. Damit die Autorin loslegen kann, musst du ihr den Artikel noch zuweisen (siehe nächster Artikel). ',
                            'In der Artikelübersicht der Planung siehst du alle Artikel der Ausgabe nach Kapiteln gruppiert – mit Status, Verantwortlichen und Fristen.',
                        ),
                        N::info(
                            N::b('Wichtig: '),
                            'Du wirst automatisch als Produktmanager des Artikels eingetragen. Nur du (und Administratoren) können den Workflow dieses Artikels steuern.',
                        ),
                    ],
                ],
                [
                    'title' => 'Aufgaben zuweisen: Autor:innen, Redaktion und Lektorat',
                    'content' => [
                        N::p(
                            'Als Produktmanager entscheidest du bei jedem Workflow-Schritt, wer als Nächstes am Artikel arbeitet. ',
                            'Die Zuweisungen findest du im Editor des Artikels im Workflow-Bereich.',
                        ),
                        N::h2('Autor:in zuweisen'),
                        N::p(
                            'Mit ',
                            N::b('„Autor:in zuweisen“'),
                            ' übergibst du das Manuskript an eine Autorin oder einen Autor:',
                        ),
                        N::bullets([
                            'Bei einem geplanten Artikel startet damit die Erstbearbeitung – Status „In Bearbeitung“.',
                            'Bei einem bereits eingereichten Artikel startet eine Überarbeitungsrunde – Status „In Überarbeitung“.',
                        ]),
                        N::h2('Redaktion / Lektorat zuweisen'),
                        N::p(
                            'Liegt ein Manuskript vor („Manuskript eingereicht“ oder „Überarbeitung angefordert“), kannst du es mit ',
                            N::b('„Redaktion / Lektorat zuweisen“'),
                            ' an eine Redakteurin oder einen Lektor geben. Der Artikel wechselt in den Status „Im Lektorat“. ',
                            'Nach Abschluss der Bearbeitung kommt er automatisch als „Manuskript eingereicht“ zu dir zurück.',
                        ),
                        N::h2('Zurückrufen'),
                        N::p(
                            'Mit ',
                            N::b('„Zurückrufen“'),
                            ' holst du einen Artikel aus dem Lektorat zurück, ohne dass die Bearbeitung abgeschlossen wurde – zum Beispiel wenn es dringender geworden ist oder die falsche Person zugewiesen war. ',
                            'Der Artikel steht danach wieder auf „Manuskript eingereicht“.',
                        ),
                        N::info(
                            N::b('Begründungen nutzen: '),
                            'Bei jedem Workflow-Schritt kannst du eine Begründung hinterlegen. Nutze das Feld – die Begründung erscheint im Verlauf des Artikels und erspart Rückfragen.',
                        ),
                    ],
                ],
                [
                    'title' => 'Selbst korrigieren: die Produktmanager-Korrektur',
                    'content' => [
                        N::p(
                            'Manchmal willst du kleine Änderungen selbst vornehmen, statt eine ganze Lektorats- oder Überarbeitungsrunde zu starten. ',
                            'Dafür gibt es die ',
                            N::b('Produktmanager-Korrektur'),
                            '.',
                        ),
                        N::steps([
                            [
                                'Wähle bei einem eingereichten Manuskript ', N::b('„Korrektur starten“'),
                                '. Der Artikel wechselt in den Status „Produktmanager Korrektur“ und du kannst den Text direkt bearbeiten.',
                            ],
                            'Nimm deine Änderungen im Editor vor. Alle Änderungen werden versioniert und im Workflow-Verlauf dokumentiert.',
                            [
                                'Schließe mit ', N::b('„Korrektur abschließen“'),
                                ' ab. Der Artikel steht danach wieder auf „Manuskript eingereicht“.',
                            ],
                        ]),
                        N::info(
                            N::b('Transparenz: '),
                            'Auch deine eigenen Korrekturen sind über die Versionen für alle Beteiligten nachvollziehbar. Bei größeren inhaltlichen Änderungen ist es meist besser, den Artikel der Autorin zur Überarbeitung zurückzugeben.',
                        ),
                    ],
                ],
                [
                    'title' => 'Freigeben und veröffentlichen',
                    'content' => [
                        N::h2('Als bereit markieren'),
                        N::p(
                            'Ist ein Artikel fertig geprüft, markierst du ihn mit ',
                            N::b('„Als bereit markieren“'),
                            ' – der Status wechselt zu „Bereit zur Veröffentlichung“. ',
                            'Das ist deine Freigabe: Der Artikel ist inhaltlich abgeschlossen und wartet nur noch auf die Veröffentlichung.',
                        ),
                        N::h2('Veröffentlichen'),
                        N::p(
                            'Mit ',
                            N::b('„Veröffentlichen“'),
                            ' schließt du den Artikel endgültig ab. Das Veröffentlichungsdatum wird gespeichert und der Artikel ist ab sofort ',
                            N::b('für alle gesperrt'),
                            ' – auch für dich. Überlege deshalb vor dem Klick, ob wirklich alles passt; der Bestätigungsdialog weist dich darauf hin.',
                        ),
                        N::h2('Die fertige Ausgabe ansehen'),
                        N::p(
                            'Über ',
                            N::b('„Ansehen“'),
                            ' bei der Ausgabe öffnest du die schreibgeschützte Gesamtansicht: alle Artikel der Ausgabe in ihrer Kapitel-Reihenfolge, so wie sie aktuell stehen. ',
                            'Das ist praktisch für einen letzten Gesamtblick vor der Veröffentlichung.',
                        ),
                        N::h2('PDF-Korrekturfahnen'),
                        N::p(
                            'Aus dem Editor heraus kannst du jeden Artikel als ',
                            N::b('PDF exportieren'),
                            ' – etwa für externe Korrekturleser. PDFs lassen sich direkt in Graflow annotieren (Markierungen, Stift, Rechtecke) und als annotierte Fassung speichern. ',
                            'Alle erzeugten und annotierten PDFs bleiben unter „PDF-Versionen“ am Artikel erhalten.',
                        ),
                        N::info(
                            N::b('Falls doch noch etwas auffällt: '),
                            'Solange der Artikel „Bereit zur Veröffentlichung“ ist, kannst du ihn jederzeit zurück in den Workflow geben – zum Beispiel erneut einer Autor:in oder dem Lektorat zuweisen. Nach der Veröffentlichung hilft nur noch der Administrator (Status administrativ setzen).',
                        ),
                    ],
                ],
            ],
        ];
    }
}
