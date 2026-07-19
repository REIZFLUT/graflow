<?php

return [
    'settings_sets' => [
        'title' => 'Editor-Einstellungen',
        'description' => 'Definiere wiederverwendbare Sets für Schriftart und Marginalspalte',
        'new' => 'Neues Set',
        'empty' => 'Du hast noch keine Editor-Einstellungen angelegt.',
        'create_first' => 'Erstes Set anlegen',
        'table' => [
            'name' => 'Name',
            'configuration' => 'Konfiguration',
            'publications' => 'Publikationen',
            'articles' => 'Artikel',
        ],
        'create' => [
            'head_title' => 'Neues Editor-Einstellungen-Set',
            'title' => 'Neues Set',
            'description' => 'Lege ein wiederverwendbares Set für den Artikel-Editor an',
            'submit' => 'Set anlegen',
        ],
        'edit' => [
            'breadcrumb' => 'Bearbeiten',
        ],
        'delete_heading' => 'Set löschen',
        'delete_description' => 'Das Set wird dauerhaft entfernt.',
        'delete_in_use' => 'Dieses Set wird von :count Publikation(en) oder Artikel(n) verwendet und kann nicht gelöscht werden.',
    ],

    'form' => [
        'name_placeholder' => 'Magazin Serif',
        'font_label' => 'Schriftart',
        'font_placeholder' => 'Schriftart wählen',
        'font' => [
            'spectral' => 'Spectral (Serif)',
            'roboto' => 'Roboto (Sans)',
        ],
        'marginal_column' => 'Marginalspalte anzeigen',
    ],

    'summary' => [
        'spectral' => 'Spectral',
        'roboto' => 'Roboto',
        'with_marginal' => 'mit Marginalspalte',
        'without_marginal' => 'ohne Marginalspalte',
        'format' => ':font · :margin',
    ],

    'placeholder' => [
        'document' => 'Schreibe hier den Inhalt deines Artikels…',
        'default' => 'Beginne mit dem Schreiben…',
    ],

    'toolbar' => [
        'heading_2' => 'Überschrift 2',
        'heading_3' => 'Überschrift 3',
        'bold' => 'Fett',
        'italic' => 'Kursiv',
        'superscript' => 'Hochgestellt',
        'subscript' => 'Tiefgestellt',
        'inline_math' => 'Formel',
        'block_math' => 'Blockformel',
        'bullet_list' => 'Aufzählung',
        'ordered_list' => 'Nummerierte Liste',
        'blockquote' => 'Zitat',
        'paragraph_formats' => 'Absatzformate',
        'character_formats' => 'Zeichenformate',
        'block_elements' => 'Blockelemente',
        'marginal_note' => 'Marginalie',
        'footnote' => 'Fußnote',
        'image' => 'Bild',
        'remove_image' => 'Bild aus Artikel entfernen',
        'table' => 'Tabelle',
        'table_insert' => 'Tabelle einfügen',
        'table_add_row_before' => 'Zeile oben',
        'table_add_row_after' => 'Zeile unten',
        'table_add_column_before' => 'Spalte links',
        'table_add_column_after' => 'Spalte rechts',
        'table_delete_row' => 'Zeile löschen',
        'table_delete_column' => 'Spalte löschen',
        'table_toggle_header_row' => 'Kopfzeile umschalten',
        'table_delete' => 'Tabelle löschen',
        'spellcheck' => 'Rechtschreibung prüfen',
    ],

    'spellcheck' => [
        'start' => 'Rechtschreibprüfung starten',
        'checking' => 'Rechtschreibprüfung läuft',
        'not_run' => 'Noch nicht geprüft.',
        'empty' => 'Keine Fehler gefunden.',
        'empty_document' => 'Kein Text zum Prüfen vorhanden.',
        'no_issues' => 'Keine Rechtschreib- oder Grammatikfehler gefunden.',
        'issues_found' => ':count Hinweise gefunden',
        'error' => 'Rechtschreibprüfung fehlgeschlagen.',
        'dismiss' => 'Verwerfen',
        'apply' => 'Übernehmen',
        'no_suggestions' => 'Keine Vorschläge',
    ],

    'format' => [
        'normal_paragraph' => [
            'label' => 'Normaler Absatz',
            'description' => 'Standardabsatz ohne besondere Formatierung.',
        ],
        'normal_character' => [
            'label' => 'Normaler Text',
            'description' => 'Standardtext ohne besondere Zeichenformatierung.',
        ],
        'author_comment' => [
            'label' => 'Autorenkommentar',
            'description' => 'Meta-Kommentar des Autors. Darstellung: kursiv und fett. Semantik: redaktioneller Zusatz außerhalb des Haupttextes.',
        ],
        'red_text' => [
            'label' => 'Roter Text',
            'description' => 'Hervorhebung wichtiger Begriffe. Darstellung: rote Schriftfarbe. Semantik: besondere Aufmerksamkeit.',
        ],
        'info_box' => [
            'label' => 'Infokasten',
            'description' => 'Zusatzinformation für den Leser. Darstellung: hellblau hinterlegt mit 1px blauem Rahmen. Semantik: ergänzender Hinweis.',
        ],
    ],

    'math' => [
        'insert_inline' => 'Formel einfügen',
        'insert_block' => 'Blockformel einfügen',
        'edit_inline' => 'Formel bearbeiten',
        'edit_block' => 'Blockformel bearbeiten',
        'description' => 'LaTeX-Syntax verwenden, z. B. :inline_example oder :block_example.',
        'placeholder' => 'LaTeX-Formel…',
        'preview_empty' => 'Vorschau erscheint hier…',
    ],

    'marginal' => [
        'add_aria' => 'Marginalie hinzufügen',
        'column_aria' => 'Marginalspalte',
    ],
];
