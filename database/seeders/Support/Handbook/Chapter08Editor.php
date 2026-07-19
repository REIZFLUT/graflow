<?php

namespace Database\Seeders\Support\Handbook;

use Database\Seeders\Support\Handbook\Nodes as N;

class Chapter08Editor
{
    /**
     * @return array{title: string, articles: list<array{title: string, content: list<array<string, mixed>>}>}
     */
    public static function chapter(): array
    {
        return [
            'title' => 'Der Editor im Detail',
            'articles' => [
                [
                    'title' => 'Grundlagen: Schreiben und Formatieren',
                    'content' => [
                        N::p(
                            'Der Text-Editor ist das Herzstück von Graflow. Oben findest du die Werkzeugleiste mit allen Formatierungen, rechts die Seitenleiste mit Fußnoten, Medien, Versionen, Verlauf und Kommentaren. ',
                            'Unten zeigt die Fußzeile Wortzahl, Zeichenzahl (mit Fortschritt zur Ziel-Zeichenanzahl), die verantwortliche Person und die Abgabefrist.',
                        ),
                        N::img(
                            'editor.png',
                            'Der Artikel-Editor mit Werkzeugleiste, Textbereich und Seitenleiste',
                            'Der Artikel-Editor: oben die Werkzeugleiste, unten die Statistik-Fußzeile.',
                        ),
                        N::h2('Textstruktur'),
                        N::bullets([
                            [N::b('Überschrift 2 und Überschrift 3'), ' – gliedere deinen Text mit zwei Überschriftenebenen. (Die Hauptüberschrift ist der Artikeltitel selbst.)'],
                            [N::b('Aufzählung und nummerierte Liste'), ' – für Stichpunkte und Schritt-für-Schritt-Anleitungen.'],
                            [N::b('Zitat'), ' – hebt längere Zitate als eigenen Block hervor.'],
                        ]),
                        N::h2('Zeichenformatierung'),
                        N::bullets([
                            [N::b('Fett'), ' und ', N::i('Kursiv'), ' – für Betonungen.'],
                            [N::b('Hochgestellt / Tiefgestellt'), ' – z. B. für m² oder CO₂.'],
                            [N::b('Formel / Blockformel'), ' – mathematische Formeln in LaTeX-Syntax, als Formel im Fließtext oder als eigener Block.'],
                        ]),
                        N::h2('Speichern'),
                        N::p(
                            'Speichere über die Schaltfläche ',
                            N::b('„Speichern“'),
                            '. Jeder Speichervorgang erstellt automatisch eine neue Version (siehe „Versionen und Verlauf“). ',
                            'Der Artikeltitel lässt sich direkt über dem Text bearbeiten.',
                        ),
                    ],
                ],
                [
                    'title' => 'Besondere Elemente: Infokasten, Marginalien, Fußnoten, Tabellen',
                    'content' => [
                        N::h2('Absatz- und Zeichenformate'),
                        N::p('Über die Menüs „Absatzformate“ und „Zeichenformate“ in der Werkzeugleiste stehen besondere semantische Formate bereit:'),
                        N::bullets([
                            [N::b('Autorenkommentar'), ' – ein Meta-Kommentar des Autors außerhalb des Haupttextes, dargestellt kursiv und fett.'],
                            [N::b('Roter Text'), ' – Hervorhebung wichtiger Begriffe, die besondere Aufmerksamkeit brauchen.'],
                        ]),
                        N::h2('Infokasten'),
                        N::p(
                            'Der ',
                            N::b('Infokasten'),
                            ' (unter „Blockelemente“) hebt ergänzende Hinweise für die Leserschaft hervor – hellblau hinterlegt mit blauem Rahmen. Dieses Handbuch nutzt Infokästen für Tipps und Warnungen.',
                        ),
                        N::h2('Marginalien'),
                        N::p(
                            'Wenn die Publikation mit ',
                            N::b('Marginalspalte'),
                            ' konfiguriert ist, kannst du zu Absätzen, Überschriften und anderen Blöcken kurze Randbemerkungen (Marginalien) erfassen. ',
                            'Sie erscheinen neben dem Text in der Marginalspalte – typisch für Fachpublikationen als Orientierungshilfe beim Querlesen.',
                        ),
                        N::h2('Fußnoten'),
                        N::steps([
                            'Markiere das Wort oder die Textstelle, auf die sich die Fußnote bezieht.',
                            ['Klicke in der Werkzeugleiste auf ', N::b('„Fußnote“'), ' und erfasse den Fußnotentext.'],
                            ['Alle Fußnoten des Artikels findest du gesammelt in der Seitenleiste unter ', N::b('„Fußnoten“'), ' – dort kannst du sie auch bearbeiten.'],
                        ]),
                        N::h2('Tabellen'),
                        N::p(
                            'Über ',
                            N::b('„Tabelle“'),
                            ' fügst du Tabellen ein. Im Tabellen-Menü kannst du Zeilen und Spalten hinzufügen oder löschen, die Kopfzeile umschalten und die ganze Tabelle entfernen.',
                        ),
                    ],
                ],
                [
                    'title' => 'Bilder und Medien',
                    'content' => [
                        N::p(
                            'Jeder Artikel hat eine eigene Medienverwaltung. Du findest sie in der Seitenleiste des Editors unter ',
                            N::b('„Medien“'),
                            '.',
                        ),
                        N::h2('Ein Bild hochladen und einfügen'),
                        N::steps([
                            ['Öffne ', N::b('„Medien“'), ' und klicke auf ', N::b('„Bild hochladen“'), '.'],
                            [
                                'Wähle die Bilddatei und fülle die Felder aus: ',
                                N::b('Alt-Text'),
                                ' (Beschreibung für Screenreader) und ',
                                N::b('Copyright'),
                                ' sind Pflicht, die ',
                                N::b('Bildunterschrift'),
                                ' ist optional.',
                            ],
                            ['Setze den Cursor an die gewünschte Stelle im Text und klicke beim Bild auf ', N::b('„Einfügen“'), '.'],
                        ]),
                        N::h2('Gut zu wissen'),
                        N::bullets([
                            'Graflow erzeugt beim Upload automatisch webtaugliche Vorschauversionen des Bildes. Das Original bleibt für die spätere Produktion erhalten.',
                            'Alt-Text, Copyright und Bildunterschrift kannst du jederzeit in der Medienverwaltung nachbearbeiten.',
                            'Ein Bild, das noch im Artikel verwendet wird, kann nicht gelöscht werden. Entferne es zuerst aus dem Text („Bild aus Artikel entfernen“).',
                        ]),
                        N::info(
                            N::b('Pflichtfelder ernst nehmen: '),
                            'Der Alt-Text macht Inhalte für Menschen mit Sehbeeinträchtigung zugänglich, das Copyright sichert die Rechteklärung. Beides ist bewusst verpflichtend.',
                        ),
                    ],
                ],
                [
                    'title' => 'Kommentare: Zusammenarbeit im Text',
                    'content' => [
                        N::p(
                            'Mit Kommentaren besprechen alle Beteiligten konkrete Textstellen direkt im Artikel – ohne den Text selbst zu verändern.',
                        ),
                        N::h2('Einen Kommentar hinzufügen'),
                        N::steps([
                            'Markiere ein Wort oder eine Textstelle im Artikel.',
                            ['Klicke in der Werkzeugleiste auf ', N::b('„Kommentar“'), ' und schreibe deine Anmerkung.'],
                        ]),
                        N::h2('Mit Kommentaren arbeiten'),
                        N::bullets([
                            ['Alle Kommentare findest du in der Seitenleiste unter ', N::b('„Kommentare“'), '; kommentierte Stellen werden im Text hervorgehoben („Kommentare im Text anzeigen“).'],
                            ['Auf Kommentare kannst du ', N::b('antworten'), ' – so entstehen Diskussionsstränge zu einer Textstelle.'],
                            ['Ist ein Punkt geklärt, markiere den Kommentar als ', N::b('„Erledigt“'), '. Bei Bedarf lässt er sich ', N::b('„Wieder öffnen“'), '.'],
                        ]),
                        N::info(
                            N::b('Konvention: '),
                            'Wer eine Anmerkung umgesetzt hat, markiert sie als „Erledigt“. So sieht das Produktmanagement auf einen Blick, ob noch offene Punkte existieren.',
                        ),
                    ],
                ],
                [
                    'title' => 'Versionen und Verlauf',
                    'content' => [
                        N::h2('Versionen: jede Speicherung wird gesichert'),
                        N::p(
                            'Bei jedem Speichern legt Graflow automatisch eine neue ',
                            N::b('Version'),
                            ' des Artikels an – mit Titel, Inhalt, Status und der Person, die gespeichert hat. ',
                            'Du findest alle Versionen in der Seitenleiste unter „Versionen“.',
                        ),
                        N::bullets([
                            [
                                N::b('Vergleichen: '),
                                'Wähle zwei Versionen aus, um die Unterschiede farblich hervorgehoben zu sehen (grün = hinzugefügt, rot = entfernt). Mit einem Klick auf eine Änderung springst du zur Stelle im Editor. Der Schnellvergleich „Letzte Arbeitsversion ↔ Aktuelle Veröffentlichung“ zeigt, was sich seit der Veröffentlichung getan hat.',
                            ],
                            [
                                N::b('Wiederherstellen: '),
                                'Über „Wiederherstellen“ ersetzt du den aktuellen Text durch eine frühere Version. Keine Sorge: Auch dabei entsteht automatisch eine neue Version – es geht nichts verloren.',
                            ],
                        ]),
                        N::h2('Verlauf: der Workflow-Lebenslauf'),
                        N::p(
                            'Unter ',
                            N::b('„Verlauf“'),
                            ' siehst du alle Workflow-Schritte des Artikels: jeden Statuswechsel mit Datum, handelnder Person, neuer Verantwortlicher und Begründung. ',
                            'Der Verlauf beantwortet Fragen wie „Warum liegt der Artikel wieder bei mir?“ oder „Wer hat die Überarbeitung angefordert?“.',
                        ),
                    ],
                ],
                [
                    'title' => 'Rechtschreibprüfung und KI-Lektorat',
                    'content' => [
                        N::p('Graflow bietet zwei sich ergänzende Prüfwerkzeuge, beide erreichbar über die Werkzeugleiste des Editors:'),
                        N::h2('Rechtschreibprüfung'),
                        N::p(
                            'Die ',
                            N::b('Rechtschreibprüfung'),
                            ' findet Rechtschreib-, Grammatik- und Zeichensetzungsfehler. ',
                            'Jeder Fund zeigt die betroffene Stelle mit Korrekturvorschlägen – per ',
                            N::b('„Übernehmen“'),
                            ' korrigierst du direkt im Text, per ',
                            N::b('„Verwerfen“'),
                            ' ignorierst du den Hinweis.',
                        ),
                        N::h2('KI-Lektorat'),
                        N::p(
                            'Das ',
                            N::b('KI-Lektorat'),
                            ' geht über die Rechtschreibung hinaus und prüft Sprache und Stil. Es meldet unter anderem:',
                        ),
                        N::bullets([
                            [N::b('Unfertige Sätze'), ' – Sätze, die grammatikalisch nicht abgeschlossen sind.'],
                            [N::b('Unlogische Sätze'), ' – Aussagen, die inhaltlich nicht schlüssig wirken.'],
                            [N::b('Wortwiederholungen'), ' – auffällig häufige Begriffe in kurzer Folge.'],
                            [N::b('Umgangssprache'), ' – Formulierungen, die nicht zum Fachtext passen.'],
                            [N::b('Sprachliche Muster'), ' – stilistische Auffälligkeiten wie Füllwörter oder Schachtelsätze.'],
                        ]),
                        N::p(
                            'Jeder Hinweis nennt die betroffene Textstelle, eine Erklärung und – wo sinnvoll – einen Formulierungsvorschlag. ',
                            'Die Hinweise sind Empfehlungen: Du entscheidest, was du übernimmst.',
                        ),
                        N::info(
                            N::b('Hinweis: '),
                            'Beide Prüfungen benötigen eine Serverkonfiguration durch den Administrator. Erscheint die Meldung „… ist nicht konfiguriert“, wende dich an ihn. Das KI-Lektorat ersetzt außerdem kein menschliches Lektorat – es ist eine Vorstufe.',
                        ),
                    ],
                ],
                [
                    'title' => 'PDF-Export und Korrekturfahnen',
                    'content' => [
                        N::p(
                            'Jeden Artikel kannst du als PDF exportieren – zum Beispiel als Korrekturfahne für Personen, die nicht in Graflow arbeiten.',
                        ),
                        N::h2('PDF erzeugen'),
                        N::p(
                            'Klicke im Editor auf ',
                            N::b('„PDF exportieren“'),
                            '. Das erzeugte PDF öffnet sich im integrierten PDF-Viewer und wird am Artikel gespeichert.',
                        ),
                        N::h2('PDF annotieren'),
                        N::p('Im PDF-Viewer stehen dir Korrekturwerkzeuge zur Verfügung:'),
                        N::bullets([
                            [N::b('Markieren'), ' – Textstellen farblich hervorheben.'],
                            [N::b('Stift'), ' – freihändig zeichnen und anstreichen.'],
                            [N::b('Rechteck'), ' – Bereiche einrahmen.'],
                            [N::b('Löschen'), ' – eigene Anmerkungen wieder entfernen.'],
                        ]),
                        N::p(
                            'Mit ',
                            N::b('„Annotiertes PDF speichern“'),
                            ' sicherst du deine Anmerkungen als eigene PDF-Fassung. Unter ',
                            N::b('„PDF-Versionen“'),
                            ' bleiben alle erzeugten und annotierten PDFs mit Datum erhalten – so lassen sich Korrekturstände sauber auseinanderhalten.',
                        ),
                    ],
                ],
            ],
        ];
    }
}
