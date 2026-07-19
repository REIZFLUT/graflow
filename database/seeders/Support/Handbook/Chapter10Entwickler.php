<?php

namespace Database\Seeders\Support\Handbook;

use Database\Seeders\Support\Handbook\Nodes as N;

class Chapter10Entwickler
{
    /**
     * @return array{title: string, articles: list<array{title: string, content: list<array<string, mixed>>}>}
     */
    public static function chapter(): array
    {
        return [
            'title' => 'Hinweise für den Entwickler',
            'articles' => [
                [
                    'title' => 'Beobachtungen und Verbesserungsvorschläge',
                    'content' => [
                        N::p(
                            'Dieses Kapitel richtet sich nicht an Endanwender. Es sammelt Probleme und Auffälligkeiten, die beim Erstellen dieses Handbuchs aus Entwicklersicht aufgefallen sind – jeweils mit Fundort im Code.',
                        ),
                        N::h2('1. Kein Benachrichtigungssystem'),
                        N::p(
                            'Workflow-Übergaben (Autor:in zugewiesen, Manuskript eingereicht, Lektorat zugewiesen …) lösen keinerlei Benachrichtigung aus. Das User-Model nutzt zwar den Notifiable-Trait, aber es existiert keine einzige Notification-Klasse und kein notify()-Aufruf im gesamten app/-Verzeichnis. ',
                            'Benutzer erfahren nur durch aktives Nachschauen in der Artikelliste, dass sie am Zug sind. Empfehlung: E-Mail- oder In-App-Benachrichtigungen in den Übergängen des ArticleWorkflowService auslösen.',
                        ),
                        N::h2('2. Handbuch-Publikation ist nur über ihren Namen verknüpft'),
                        N::p(
                            'App\\Support\\Handbook::resolvePublication() identifiziert die Handbuch-Publikation ausschließlich über den Namen aus config/handbook.php („Graflow Handbuch“). ',
                            'Für Nicht-Admins ist sie aus der Publikationsliste ausgeblendet, für Administratoren erscheint sie dort aber als normale Publikation und kann umbenannt oder gelöscht werden. ',
                            'Danach zeigt der Handbuch-Bereich ein leeres Handbuch und legt beim nächsten Aufruf still eine neue, leere Publikation an – die alten Inhalte sind aus Benutzersicht „verschwunden“. ',
                            'Auch ArticlePolicy::view (Lesezugriff aller Benutzer auf Handbuch-Artikel samt Bildern) hängt an diesem Namensabgleich.',
                        ),
                        N::bullets([
                            'Empfehlung: die Handbuch-Publikation über eine dedizierte Spalte (z. B. is_handbook) oder eine gespeicherte ID referenzieren statt über den Namen.',
                            'Zusätzlich: Umbenennen/Löschen der Handbuch-Publikation in PublicationPolicy blockieren oder sie aus der normalen Publikationsliste ausblenden.',
                        ]),
                        N::h2('3. Absolute Bild-URLs im Artikelinhalt'),
                        N::p(
                            'articleImage-Nodes speichern previewWebpUrl/previewJpegUrl als absolute URLs (inkl. Domain) direkt im TipTap-JSON des Artikels. ',
                            'Bei einem Wechsel von APP_URL (Staging → Produktion, Domainumzug) zeigen alle bestehenden Artikel auf die alte Domain. ',
                            'Empfehlung: nur die mediaId im Content speichern und die URLs beim Rendern aus der Route ableiten, oder relative Pfade verwenden.',
                        ),
                        N::h2('4. Unbegrenztes Wachstum der Versionstabelle'),
                        N::p(
                            'ArticleController::update() erzeugt bei jedem Speichern einen vollständigen Content-Snapshot in article_versions – auch wenn sich nichts geändert hat. ',
                            'Es gibt weder Deduplizierung noch Pruning. Bei fleißig speichernden Autoren wächst die Datenbank schnell. ',
                            'Empfehlung: identische aufeinanderfolgende Snapshots überspringen und/oder alte Versionen konsolidieren (z. B. per Scheduler, analog zum vorhandenen PruneArticleMediaStagingCommand).',
                        ),
                        N::h2('5. HandbookSeeder aktualisiert Inhalte nicht'),
                        N::p(
                            'Der ursprüngliche HandbookSeeder verwendet Article::firstOrCreate – bei erneutem Seeding werden geänderte Inhalte nicht übernommen. ',
                            'Der neue HandbookContentSeeder (der dieses Handbuch erzeugt) nutzt deshalb updateOrCreate und aktualisiert Inhalte bei jedem Lauf. Der alte Seeder kann perspektivisch entfallen.',
                        ),
                        N::h2('6. Server-Artefakte im Projektverzeichnis'),
                        N::p(
                            'Im Projekt-Root liegen ungetrackte Plesk-Artefakte: index.html („Domain Default page“), .php-ini, .php-version, .node-version sowie public/.php-ini und public/.php-version. ',
                            'Die index.html im Root ist bei korrektem DocumentRoot (public/) harmlos, sollte aber entfernt werden, um Verwirrung zu vermeiden; die übrigen Dateien gehören in die .gitignore.',
                        ),
                        N::h2('7. Kleinere Punkte'),
                        N::bullets([
                            'KI-Lektorat und Rechtschreibprüfung liefern 503, wenn services.ai_lektorat bzw. LanguageTool nicht konfiguriert sind. Das Frontend fängt das mit einer verständlichen Meldung ab – die nötige Serverkonfiguration ist aber nirgends für Admins dokumentiert (nur .env.example).',
                            'Kommentar-Anker können beim Umschreiben des Textes verloren gehen und erscheinen dann als „(Textbezug entfernt)“ – funktional in Ordnung, für Endanwender aber überraschend; ein Hinweis-Tooltip wäre hilfreich.',
                            'Das Handbuch ist für alle Rollen sichtbar, seine Artikel tauchen aber (korrekt) nicht in der Aufgabenliste anderer Benutzer auf, da nur der Admin beteiligt ist – bei künftigen Änderungen an der Sichtbarkeitslogik beachten.',
                        ]),
                    ],
                ],
            ],
        ];
    }
}
