<?php

namespace Database\Seeders\Support\Handbook;

use Database\Seeders\Support\Handbook\Nodes as N;

class Chapter09Administratoren
{
    /**
     * @return array{title: string, articles: list<array{title: string, content: list<array<string, mixed>>}>}
     */
    public static function chapter(): array
    {
        return [
            'title' => 'Für Administratoren',
            'articles' => [
                [
                    'title' => 'Benutzer verwalten',
                    'content' => [
                        N::p(
                            'Als Administrator legst du alle Benutzerkonten an und weist die Rollen zu. Du findest die Benutzerverwaltung im Menüpunkt ',
                            N::b('Benutzer'),
                            ' – er ist nur für Administratoren sichtbar.',
                        ),
                        N::img(
                            'benutzer.png',
                            'Die Benutzerverwaltung mit Name, E-Mail, Rolle und Erstellungsdatum',
                            'Die Benutzerverwaltung: alle Konten mit ihren Rollen im Überblick.',
                        ),
                        N::h2('Einen Benutzer anlegen'),
                        N::steps([
                            [N::b('„Neuer Benutzer“'), ' klicken.'],
                            'Name, E-Mail-Adresse und ein Start-Passwort vergeben.',
                            [
                                'Die passende ', N::b('Rolle'),
                                ' wählen: Administrator, Produktmanager, Redakteur:in, Lektor:in oder Autor:in. Die Rolle bestimmt alle Rechte (siehe Kapitel „Rollen und Rechte“).',
                            ],
                            'Zugangsdaten sicher an die Person übermitteln und sie bitten, das Passwort zu ändern sowie die Zwei-Faktor-Authentifizierung zu aktivieren.',
                        ]),
                        N::h2('Benutzer bearbeiten und löschen'),
                        N::bullets([
                            'Name, E-Mail, Rolle und Passwort kannst du jederzeit ändern. Lässt du das Passwortfeld leer, bleibt das bisherige Passwort bestehen.',
                            'Ein Rollenwechsel wirkt sofort – die Person sieht beim nächsten Seitenaufruf die Bereiche ihrer neuen Rolle.',
                            'Ein Benutzer kann nicht gelöscht werden, solange noch Datensätze mit ihm verknüpft sind oder es sich um den letzten Administrator handelt.',
                        ]),
                        N::info(
                            N::b('Empfehlung: '),
                            'Vergib pro Person genau ein Konto mit der niedrigsten Rolle, die für ihre Arbeit ausreicht. Für Vertretungen ist es besser, ein zweites Konto mit passender Rolle anzulegen, als Rollen hin- und herzuwechseln.',
                        ),
                    ],
                ],
                [
                    'title' => 'Editor-Einstellungen-Sets verwalten',
                    'content' => [
                        N::p(
                            'Unter ',
                            N::b('Editor-Einstellungen'),
                            ' verwaltest du wiederverwendbare Sets, die das Erscheinungsbild des Editors steuern. Neben Administratoren dürfen auch Produktmanager, Redaktion und Lektorat Sets verwalten – nur Autorinnen und Autoren nicht.',
                        ),
                        N::h2('Was ein Set festlegt'),
                        N::bullets([
                            [N::b('Schriftart'), ' – z. B. Spectral (Serif) oder Roboto (Sans).'],
                            [N::b('Marginalspalte'), ' – ob Artikel eine Randspalte für Marginalien anzeigen.'],
                        ]),
                        N::h2('Wo Sets wirken'),
                        N::bullets([
                            ['Jede ', N::b('Publikation'), ' bekommt ein Standard-Set – es gilt für alle ihre Artikel.'],
                            ['Einzelne ', N::b('Artikel'), ' können das Set über ihre Metadaten gezielt überschreiben.'],
                            'Ein Set, das noch von Publikationen oder Artikeln verwendet wird, kann nicht gelöscht werden.',
                        ]),
                    ],
                ],
                [
                    'title' => 'In den Workflow eingreifen: Status setzen',
                    'content' => [
                        N::p(
                            'Als Administrator kannst du bei jedem Artikel die Aktion ',
                            N::b('„Status setzen“'),
                            ' nutzen. Damit setzt du den Artikel direkt in einen beliebigen Status und trägst bei Bedarf eine neue verantwortliche Person ein – am regulären Workflow vorbei.',
                        ),
                        N::h2('Typische Einsatzfälle'),
                        N::bullets([
                            'Ein veröffentlichter Artikel muss doch noch korrigiert werden → zurück in „In Bearbeitung“ oder „Produktmanager Korrektur“.',
                            'Eine verantwortliche Person ist ausgefallen → Artikel in den passenden Status setzen und einer anderen Person zuweisen.',
                            'Ein Artikel hängt durch ein Versehen im falschen Status fest.',
                        ]),
                        N::info(
                            N::b('Mit Bedacht einsetzen: '),
                            'Der administrative Eingriff umgeht die eingebauten Regeln des Workflows. Er wird vollständig im Verlauf protokolliert. Hinterlege immer eine Begründung, damit das Team nachvollziehen kann, warum eingegriffen wurde. Beachte, dass die Zielperson zur Rolle passen muss: Manuskript-Status brauchen Autor:innen, Lektorats-Status Redaktion oder Lektorat.',
                        ),
                    ],
                ],
                [
                    'title' => 'Das Handbuch pflegen',
                    'content' => [
                        N::p(
                            'Dieses Handbuch ist technisch eine normale Graflow-Publikation („Graflow Handbuch“) mit einer Ausgabe („Handbuch“), die dem ersten Administrator gehört. ',
                            'Als Administrator kannst du es direkt im Handbuch-Bereich pflegen – für alle anderen Rollen ist das Handbuch reine Lektüre.',
                        ),
                        N::h2('Artikel hinzufügen und bearbeiten'),
                        N::steps([
                            ['Öffne den Menüpunkt ', N::b('Handbuch'), '.'],
                            [
                                'Klicke auf ', N::b('„Artikel hinzufügen“'),
                                ', vergib einen Titel und wähle das Kapitel. Anschließend öffnet sich der gewohnte Artikel-Editor.',
                            ],
                            ['Bestehende Artikel bearbeitest du über ', N::b('„Bearbeiten“'), ' direkt am Artikel; ', N::b('„Löschen“'), ' entfernt einen Artikel nach Rückfrage endgültig.'],
                        ]),
                        N::h2('Kapitelstruktur ändern'),
                        N::p(
                            'Neue Kapitel oder eine andere Reihenfolge pflegst du über die normale Ausgabenplanung: Öffne unter ',
                            N::b('Publikationen'),
                            ' die Publikation „Graflow Handbuch“, dort die Ausgabe „Handbuch“ und ihre ',
                            N::b('Planung'),
                            '. Kapitel und Artikelpositionen funktionieren wie bei jeder anderen Ausgabe.',
                        ),
                        N::info(
                            N::b('Vorsicht: '),
                            'Graflow erkennt das Handbuch an seinem Namen. Benenne die Publikation „Graflow Handbuch“ nicht um und lösche sie nicht – sonst ist das Handbuch für alle Benutzer leer und beim nächsten Aufruf wird eine neue, leere Handbuch-Publikation angelegt.',
                        ),
                    ],
                ],
            ],
        ];
    }
}
