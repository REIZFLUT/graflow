<?php

namespace Database\Seeders\Support\Handbook;

use Database\Seeders\Support\Handbook\Nodes as N;

class Chapter01Einfuehrung
{
    /**
     * @return array{title: string, articles: list<array{title: string, content: list<array<string, mixed>>}>}
     */
    public static function chapter(): array
    {
        return [
            'title' => 'Einführung',
            'articles' => [
                [
                    'title' => 'Willkommen im Graflow Handbuch',
                    'content' => [
                        N::p(
                            'Graflow ist eine Software für das gemeinsame Schreiben, Redigieren und Veröffentlichen von Fachartikeln. ',
                            'Produktmanager planen Ausgaben und Artikel, Autorinnen und Autoren schreiben die Manuskripte, Redaktion und Lektorat prüfen die Texte – und am Ende wird der fertige Artikel veröffentlicht. ',
                            'Dieses Handbuch erklärt Schritt für Schritt, wie du Graflow in deiner Rolle richtig einsetzt.',
                        ),
                        N::h2('Für wen ist dieses Handbuch?'),
                        N::p('Das Handbuch richtet sich an alle, die mit Graflow arbeiten:'),
                        N::bullets([
                            [N::b('Autorinnen und Autoren'), ' – schreiben Manuskripte und geben sie ab.'],
                            [N::b('Redakteurinnen, Redakteure und Lektorat'), ' – prüfen und überarbeiten eingereichte Texte.'],
                            [N::b('Produktmanager'), ' – planen Publikationen und Ausgaben, verteilen Aufgaben und veröffentlichen Artikel.'],
                            [N::b('Administratoren'), ' – verwalten Benutzerkonten und greifen bei Bedarf in den Ablauf ein.'],
                        ]),
                        N::p(
                            'Du musst nicht alles lesen: Die Kapitel „Erste Schritte“, „Rollen und Rechte“ und „Der Redaktionsworkflow“ sind für alle gedacht. ',
                            'Danach folgt für jede Rolle ein eigenes Kapitel mit den Aufgaben, die dich betreffen. ',
                            'Das Kapitel „Der Editor im Detail“ beschreibt alle Funktionen des Text-Editors und ist vor allem für alle interessant, die Texte schreiben oder bearbeiten.',
                        ),
                        N::h2('So findest du dich im Handbuch zurecht'),
                        N::bullets([
                            ['Links siehst du das ', N::b('Inhaltsverzeichnis'), ' mit allen Kapiteln und Artikeln. Ein Klick öffnet den jeweiligen Artikel.'],
                            ['Über das Suchfeld ', N::i('„Handbuch durchsuchen …“'), ' findest du Artikel nach Stichworten.'],
                            ['Das Handbuch erreichst du jederzeit über den Menüpunkt ', N::b('Handbuch'), ' in der linken Seitenleiste.'],
                        ]),
                        N::img(
                            'handbuch-reader.png',
                            'Der Handbuch-Bereich in Graflow mit Inhaltsverzeichnis und Suchfeld',
                            'Das Handbuch in Graflow: links das Inhaltsverzeichnis, oben die Suche.',
                        ),
                        N::info(
                            N::b('Tipp: '),
                            'Wenn du Graflow zum ersten Mal benutzt, lies zuerst „Erste Schritte“ und danach das Kapitel zu deiner Rolle. Damit bist du startklar.',
                        ),
                    ],
                ],
                [
                    'title' => 'Graflow im Überblick',
                    'content' => [
                        N::p('Damit du die folgenden Kapitel gut einordnen kannst, hilft ein kurzer Blick auf die Grundbausteine von Graflow.'),
                        N::h2('Die Bausteine: Publikation, Ausgabe, Kapitel, Artikel'),
                        N::p('Alle Inhalte in Graflow sind in einer festen Hierarchie organisiert:'),
                        N::steps([
                            [N::b('Publikation'), ' – das übergeordnete Produkt, zum Beispiel ein Magazin wie „Energieberater Magazin“.'],
                            [N::b('Ausgabe'), ' – eine konkrete Ausgabe der Publikation, zum Beispiel „07-2026“.'],
                            [N::b('Kapitel'), ' – die geordnete Struktur innerhalb einer Ausgabe, zum Beispiel „Markt & Politik“.'],
                            [N::b('Artikel'), ' – der einzelne Text, an dem Autorinnen, Redaktion und Produktmanagement gemeinsam arbeiten.'],
                        ]),
                        N::p(
                            'Jeder Artikel gehört zu genau einer Ausgabe und hat innerhalb seines Kapitels eine feste Position. ',
                            'So entsteht aus vielen einzelnen Artikeln am Ende eine vollständige, geordnete Ausgabe.',
                        ),
                        N::h2('Der Grundgedanke: ein klarer Workflow'),
                        N::p(
                            'Jeder Artikel hat zu jedem Zeitpunkt genau einen ',
                            N::b('Status'),
                            ' (zum Beispiel „In Bearbeitung“ oder „Im Lektorat“) und in den Arbeitsphasen genau eine ',
                            N::b('verantwortliche Person'),
                            '. Der Status bestimmt, wer den Artikel gerade bearbeiten darf und welcher Schritt als Nächstes ansteht. ',
                            'Alle Statuswechsel werden automatisch im Verlauf des Artikels protokolliert – es ist also jederzeit nachvollziehbar, wer wann was entschieden hat.',
                        ),
                        N::info(
                            'Der komplette Ablauf vom geplanten Artikel bis zur Veröffentlichung ist im Kapitel „Der Redaktionsworkflow“ beschrieben.',
                        ),
                        N::h2('Übrigens: Dieses Handbuch ist selbst eine Publikation'),
                        N::p(
                            'Das Handbuch, das du gerade liest, ist technisch gesehen eine ganz normale Graflow-Publikation mit einer einzigen Ausgabe. ',
                            'Der Administrator pflegt die Handbuch-Artikel mit demselben Editor, mit dem auch alle anderen Artikel geschrieben werden. ',
                            'Du siehst hier also gleichzeitig ein Beispiel dafür, was Graflow kann.',
                        ),
                    ],
                ],
            ],
        ];
    }
}
