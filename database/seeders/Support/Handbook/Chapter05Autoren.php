<?php

namespace Database\Seeders\Support\Handbook;

use Database\Seeders\Support\Handbook\Nodes as N;

class Chapter05Autoren
{
    /**
     * @return array{title: string, articles: list<array{title: string, content: list<array<string, mixed>>}>}
     */
    public static function chapter(): array
    {
        return [
            'title' => 'Für Autorinnen und Autoren',
            'articles' => [
                [
                    'title' => 'Deinen Artikelauftrag finden und starten',
                    'content' => [
                        N::p(
                            'Als Autor:in bekommst du Artikelaufträge vom Produktmanagement zugewiesen. ',
                            'Du erkennst einen neuen Auftrag daran, dass in deiner Aufgabenliste (Menüpunkt ',
                            N::b('Artikel'),
                            ') ein Artikel im Status ',
                            N::b('„In Bearbeitung“'),
                            ' erscheint, bei dem du als „Aktuell verantwortlich“ eingetragen bist.',
                        ),
                        N::h2('Das gehört zu deinem Auftrag'),
                        N::bullets([
                            [N::b('Abgabefrist'), ' – bis wann dein Manuskript abgegeben sein soll. Überfällige Artikel werden in der Liste und im Editor markiert.'],
                            [N::b('Ziel-Zeichenanzahl'), ' – die vereinbarte Textlänge. Im Editor siehst du unten laufend deinen Fortschritt, z. B. „8.500 / 12.000 Buchstaben (71 %)“.'],
                            [N::b('Kapitel und Position'), ' – wo dein Artikel in der Ausgabe eingeplant ist.'],
                        ]),
                        N::h2('Loslegen'),
                        N::steps([
                            'Öffne den Artikel per Klick in deiner Aufgabenliste. Der Text-Editor öffnet sich.',
                            'Schreibe dein Manuskript. Nutze Überschriften, Listen, Infokästen, Fußnoten und Bilder – alle Funktionen sind im Kapitel „Der Editor im Detail“ beschrieben.',
                            'Speichere regelmäßig über die Schaltfläche „Speichern“. Jeder Speichervorgang legt automatisch eine Version an, auf die du zurückgreifen kannst.',
                        ]),
                        N::info(
                            N::b('Tipp: '),
                            'Prüfe deinen Text vor der Abgabe mit der Rechtschreibprüfung und dem KI-Lektorat (beides in der Editor-Werkzeugleiste). So bekommst du sprachliche Hinweise, bevor das echte Lektorat den Text sieht.',
                        ),
                    ],
                ],
                [
                    'title' => 'Manuskript abgeben',
                    'content' => [
                        N::p(
                            'Wenn dein Manuskript fertig ist, gibst du es an das Produktmanagement ab. ',
                            'Klicke dazu im Editor auf ',
                            N::b('„Abgeben“'),
                            ' und bestätige den Dialog. Du kannst dabei optional eine Begründung oder Anmerkung für das Produktmanagement hinterlassen.',
                        ),
                        N::h2('Was passiert bei der Abgabe?'),
                        N::bullets([
                            'Der Status wechselt zu „Manuskript eingereicht“.',
                            'Der Artikel ist ab sofort für dich gesperrt – du kannst ihn weiterhin lesen, aber nicht mehr bearbeiten.',
                            'Das Produktmanagement übernimmt und entscheidet über den nächsten Schritt (Lektorat, Überarbeitung oder Freigabe).',
                        ]),
                        N::info(
                            N::b('Gib erst ab, wenn du wirklich fertig bist.'),
                            ' Nach der Abgabe kommst du nur wieder an den Text, wenn das Produktmanagement ihn dir erneut zuweist – oder du eine Überarbeitung anforderst (siehe nächster Artikel).',
                        ),
                    ],
                ],
                [
                    'title' => 'Überarbeitung: anfordern und durchführen',
                    'content' => [
                        N::h2('Dir ist nach der Abgabe noch etwas aufgefallen?'),
                        N::p(
                            'Solange dein Manuskript im Status „Manuskript eingereicht“ liegt, kannst du selbst eine ',
                            N::b('Überarbeitung anfordern'),
                            '. Öffne den Artikel und wähle „Überarbeitung anfordern“. ',
                            'Eine ',
                            N::b('Begründung ist Pflicht'),
                            ' – beschreibe kurz, was du ändern möchtest. Der Artikel wechselt dann in den Status „Überarbeitung angefordert“ und das Produktmanagement entscheidet, ob es dir den Text erneut zuweist.',
                        ),
                        N::h2('Du bekommst den Artikel zur Überarbeitung zurück'),
                        N::p(
                            'Weist dir das Produktmanagement einen bereits eingereichten Artikel erneut zu, steht er im Status ',
                            N::b('„In Überarbeitung“'),
                            '. Das läuft genauso wie die erste Bearbeitung: Du änderst den Text im Editor und gibst ihn anschließend wieder mit „Abgeben“ ab.',
                        ),
                        N::h2('Woher weißt du, was zu tun ist?'),
                        N::bullets([
                            ['Sieh im ', N::b('Verlauf'), ' nach (Seitenleiste im Editor): Dort steht die Begründung zum Workflow-Schritt, z. B. was überarbeitet werden soll.'],
                            ['Prüfe die ', N::b('Kommentare'), ': Redaktion, Lektorat oder Produktmanagement markieren Textstellen oft mit konkreten Anmerkungen. Beantworte Kommentare oder markiere sie als „Erledigt“, wenn du sie umgesetzt hast.'],
                            ['Vergleiche bei Bedarf ', N::b('Versionen'), ', um zu sehen, was seit deiner Abgabe am Text geändert wurde.'],
                        ]),
                    ],
                ],
            ],
        ];
    }
}
