<?php

namespace Database\Seeders\Support\Handbook;

use Database\Seeders\Support\Handbook\Nodes as N;

class Chapter02ErsteSchritte
{
    /**
     * @return array{title: string, articles: list<array{title: string, content: list<array<string, mixed>>}>}
     */
    public static function chapter(): array
    {
        return [
            'title' => 'Erste Schritte',
            'articles' => [
                [
                    'title' => 'Anmeldung und Konto',
                    'content' => [
                        N::h2('Anmelden'),
                        N::p(
                            'Dein Benutzerkonto wird vom Administrator angelegt. Du erhältst von ihm deine E-Mail-Adresse und ein Passwort. ',
                            'Öffne Graflow im Browser und melde dich auf der Anmeldeseite mit diesen Zugangsdaten an.',
                        ),
                        N::img(
                            'login.png',
                            'Die Anmeldeseite von Graflow mit Feldern für E-Mail und Passwort',
                            'Die Anmeldeseite: E-Mail-Adresse und Passwort eingeben, dann anmelden.',
                        ),
                        N::p(
                            'Hast du dein Passwort vergessen, nutze den Link ',
                            N::i('„Passwort vergessen?“'),
                            ' auf der Anmeldeseite. Du erhältst dann eine E-Mail mit einem Link, über den du ein neues Passwort setzen kannst.',
                        ),
                        N::h2('Dein Konto absichern'),
                        N::p(
                            'Unter ',
                            N::b('Einstellungen → Sicherheit'),
                            ' kannst du dein Konto zusätzlich schützen:',
                        ),
                        N::bullets([
                            [N::b('Passwort ändern'), ' – lege regelmäßig ein neues, sicheres Passwort fest.'],
                            [N::b('Zwei-Faktor-Authentifizierung (2FA)'), ' – bei der Anmeldung wird zusätzlich ein Einmalcode aus einer Authenticator-App abgefragt. Sehr empfehlenswert.'],
                            [N::b('Passkeys'), ' – melde dich ganz ohne Passwort an, zum Beispiel per Fingerabdruck oder Gesichtserkennung deines Geräts.'],
                        ]),
                        N::h2('Profil und Erscheinungsbild'),
                        N::bullets([
                            ['Unter ', N::b('Einstellungen → Profil'), ' änderst du deinen Namen und deine E-Mail-Adresse.'],
                            ['Unter ', N::b('Einstellungen → Erscheinungsbild'), ' wählst du zwischen hellem und dunklem Design.'],
                        ]),
                        N::info(
                            N::b('Wichtig: '),
                            'Deine Rolle (zum Beispiel Autor:in oder Produktmanager) kannst du nicht selbst ändern. Wende dich dafür an deinen Administrator.',
                        ),
                    ],
                ],
                [
                    'title' => 'Die Oberfläche kennenlernen',
                    'content' => [
                        N::p('Nach der Anmeldung landest du auf dem Dashboard. Links findest du die Seitenleiste – sie ist deine zentrale Navigation.'),
                        N::img(
                            'dashboard.png',
                            'Das Graflow-Dashboard mit Seitenleiste und Kennzahlen',
                            'Das Dashboard nach der Anmeldung, links die Seitenleiste.',
                        ),
                        N::h2('Die Menüpunkte der Seitenleiste'),
                        N::table(
                            ['Menüpunkt', 'Was du dort findest'],
                            [
                                ['Dashboard', 'Startseite mit einem Überblick über deine Artikel und Publikationen.'],
                                ['Artikel', 'Deine Aufgabenliste: alle Artikel, für die du verantwortlich oder an denen du beteiligt bist.'],
                                ['Publikationen', 'Die Publikationen mit ihren Ausgaben, Kapiteln und Kategorien.'],
                                ['Handbuch', 'Dieses Handbuch – die Dokumentation der Graflow Software.'],
                                ['Editor-Einstellungen', 'Wiederverwendbare Sets für Schriftart und Marginalspalte (nicht für Autorinnen und Autoren sichtbar).'],
                                ['Benutzer', 'Die Benutzerverwaltung (nur für Administratoren sichtbar).'],
                            ],
                        ),
                        N::p(
                            'Welche Menüpunkte du siehst, hängt von deiner Rolle ab. Als Autor:in siehst du zum Beispiel weder „Editor-Einstellungen“ noch „Benutzer“ – das ist normal und kein Fehler.',
                        ),
                        N::h2('Benutzermenü'),
                        N::p(
                            'Unten in der Seitenleiste findest du dein Benutzermenü. Dort erreichst du die ',
                            N::b('Einstellungen'),
                            ' (Profil, Sicherheit, Erscheinungsbild) und kannst dich ',
                            N::b('abmelden'),
                            '.',
                        ),
                    ],
                ],
                [
                    'title' => 'Deine Aufgabenliste: der Bereich Artikel',
                    'content' => [
                        N::p(
                            'Der Bereich ',
                            N::b('Artikel'),
                            ' („Meine Aufgaben“) ist für die tägliche Arbeit der wichtigste Ort in Graflow. ',
                            'Hier siehst du alle Artikel, für die du gerade verantwortlich bist oder an denen du beteiligt warst.',
                        ),
                        N::img(
                            'artikel-liste.png',
                            'Die Artikelliste mit Status, Abgabefrist und Filtern',
                            'Die Aufgabenliste: Titel, Status, Ausgabe, Verantwortliche und Abgabefrist auf einen Blick.',
                        ),
                        N::h2('Die wichtigsten Spalten'),
                        N::bullets([
                            [N::b('Status'), ' – zeigt, in welchem Workflow-Schritt sich der Artikel befindet (siehe Kapitel „Der Redaktionsworkflow“).'],
                            [N::b('Aktuell verantwortlich'), ' – die Person, die als Nächstes am Artikel arbeiten muss. Steht dort dein Name, bist du am Zug.'],
                            [N::b('Abgabefrist'), ' – der Termin, bis zu dem das Manuskript abgegeben sein soll. Überfällige Artikel werden markiert.'],
                            [N::b('Ziel-Zeichen'), ' – die vereinbarte Textlänge in Zeichen.'],
                        ]),
                        N::h2('Suchen, filtern, sortieren'),
                        N::bullets([
                            'Über das Suchfeld findest du Artikel nach ihrem Titel.',
                            'Mit den Filtern grenzt du die Liste nach Publikation, Ausgabe, Autor oder Status ein.',
                            'Ein Klick auf eine Spaltenüberschrift sortiert die Liste auf- oder absteigend.',
                            'Über „Spalten“ blendest du Spalten ein und aus, die du (nicht) brauchst.',
                        ]),
                        N::info(
                            N::b('Tipp: '),
                            'Schau regelmäßig in deine Aufgabenliste. Graflow verschickt derzeit keine automatischen Benachrichtigungen – neue Aufgaben erkennst du daran, dass ein Artikel mit deinem Namen in „Aktuell verantwortlich“ auftaucht.',
                        ),
                    ],
                ],
            ],
        ];
    }
}
