<?php

namespace Database\Seeders\Support\Handbook;

use Database\Seeders\Support\Handbook\Nodes as N;

class Chapter07Lektorat
{
    /**
     * @return array{title: string, articles: list<array{title: string, content: list<array<string, mixed>>}>}
     */
    public static function chapter(): array
    {
        return [
            'title' => 'Für Redaktion und Lektorat',
            'articles' => [
                [
                    'title' => 'Zugewiesene Artikel bearbeiten',
                    'content' => [
                        N::p(
                            'Als Redakteur:in oder Lektor:in bekommst du eingereichte Manuskripte vom Produktmanagement zugewiesen. ',
                            'Ein neuer Auftrag erscheint in deiner Aufgabenliste als Artikel im Status ',
                            N::b('„Im Lektorat“'),
                            ' mit dir als „Aktuell verantwortlich“.',
                        ),
                        N::h2('So arbeitest du am Text'),
                        N::bullets([
                            [
                                N::b('Direkt ändern: '),
                                'Du kannst den Text im Editor direkt bearbeiten – Tippfehler korrigieren, Sätze umstellen, kürzen. Jede Speicherung erzeugt eine Version, die Autorin kann deine Änderungen später per Versionsvergleich nachvollziehen.',
                            ],
                            [
                                N::b('Kommentieren statt ändern: '),
                                'Bei inhaltlichen Fragen markiere die Textstelle und hinterlasse einen Kommentar. Kommentare eignen sich für alles, was die Autorin oder das Produktmanagement entscheiden soll.',
                            ],
                            [
                                N::b('Werkzeuge nutzen: '),
                                'Die Rechtschreibprüfung findet Tipp- und Grammatikfehler, das KI-Lektorat liefert stilistische Hinweise (Wortwiederholungen, Umgangssprache, unfertige Sätze). Beide erreichst du über die Editor-Werkzeugleiste.',
                            ],
                        ]),
                        N::h2('Kontext verschaffen'),
                        N::p(
                            'In der Seitenleiste des Editors findest du alles, was du für die Einordnung brauchst: den ',
                            N::b('Verlauf'),
                            ' mit allen bisherigen Workflow-Schritten und Begründungen, die ',
                            N::b('Versionen'),
                            ' zum Vergleichen früherer Fassungen und die ',
                            N::b('Kommentare'),
                            ' aller Beteiligten.',
                        ),
                    ],
                ],
                [
                    'title' => 'Bearbeitung abschließen',
                    'content' => [
                        N::p(
                            'Wenn du mit deiner Bearbeitung fertig bist, klicke im Editor auf ',
                            N::b('„Fertig“'),
                            ' und bestätige den Dialog. Der Artikel geht damit zurück an das Produktmanagement (Status „Manuskript eingereicht“) und ist für dich gesperrt.',
                        ),
                        N::h2('Vor dem Abschließen kurz prüfen'),
                        N::steps([
                            'Sind alle deine Änderungen gespeichert?',
                            'Hast du offene Fragen als Kommentare an den Textstellen hinterlassen?',
                            'Gibt es Punkte für das Produktmanagement? Ergänze sie als Begründung im Abschluss-Dialog – sie erscheinen im Verlauf.',
                        ]),
                        N::info(
                            N::b('Gut zu wissen: '),
                            'Das Produktmanagement kann einen Artikel auch zurückrufen, bevor du fertig bist. Der Artikel verschwindet dann aus deinen aktiven Aufgaben – deine bis dahin gespeicherten Änderungen bleiben natürlich erhalten.',
                        ),
                        N::p(
                            'Braucht der Text nach deiner Runde noch eine Überarbeitung durch die Autorin, entscheidet das Produktmanagement über die erneute Zuweisung. ',
                            'Es kann den Artikel auch direkt wieder ins Lektorat geben – etwa für eine zweite Prüfrunde nach größeren Änderungen.',
                        ),
                    ],
                ],
            ],
        ];
    }
}
