<?php

return [
    'title' => 'Artikel',
    'description' => 'Erstelle und bearbeite deine Artikel',
    'new_article' => 'Neuer Artikel',
    'empty' => 'Du hast noch keine Artikel erstellt.',
    'create_first' => 'Ersten Artikel erstellen',

    'table' => [
        'title' => 'Titel',
        'status' => 'Status',
        'publication' => 'Publikation',
        'updated_at' => 'Zuletzt bearbeitet',
    ],

    'create' => [
        'head_title' => 'Neuer Artikel',
    ],

    'edit' => [
        'breadcrumb' => 'Bearbeiten',
    ],

    'metadata' => [
        'head_title' => 'Metadaten – :title',
        'back_to_editor' => 'Zum Text-Editor',
        'title' => 'Metadaten',
        'breadcrumb' => 'Metadaten',

        'publication' => [
            'heading' => 'Publikation',
            'description' => 'Ordne den Artikel einer Publikation und Ausgabe zu.',
            'label' => 'Publikation',
            'placeholder' => 'Publikation wählen',
        ],

        'issue' => [
            'label' => 'Ausgabe',
            'placeholder' => 'Ausgabe wählen',
            'placeholder_no_publication' => 'Zuerst Publikation wählen',
            'no_issues' => 'Für diese Publikation sind noch keine Ausgaben angelegt.',
            'manage_issues' => 'Ausgaben verwalten',
        ],

        'categories' => [
            'heading' => 'Kategorien',
            'description' => 'Wähle eine oder mehrere Kategorien der Publikation.',
            'select_first' => 'Kategorien können erst nach Auswahl einer Publikation und Ausgabe gewählt werden.',
            'no_categories' => 'Für diese Publikation sind noch keine Kategorien angelegt.',
            'manage' => 'Kategorien verwalten',
            'placeholder' => 'Kategorien auswählen',
            'search_placeholder' => 'Kategorie suchen…',
            'empty' => 'Keine passenden Kategorien',
        ],

        'editor_settings' => [
            'heading' => 'Editor-Einstellungen',
            'description' => 'Überschreibe optional die Standard-Einstellungen für diesen Artikel im Editor.',
            'default' => 'Standard:',
            'default_fallback' => 'App-Standard (Spectral · mit Marginalspalte)',
            'use_default' => 'Standard verwenden',
            'create_set_hint' => 'Lege zuerst ein Editor-Einstellungen-Set an, um einen Override zu wählen.',
        ],
    ],

    'status' => [
        'draft' => 'Entwurf',
        'published' => 'Veröffentlicht',
        'archived' => 'Archiviert',
    ],

    'editor' => [
        'back' => 'Zurück',
        'footnotes' => 'Fußnoten',
        'media' => 'Medien',
        'metadata' => 'Metadaten',
        'versions' => 'Versionen',
        'save' => 'Speichern',
        'title_placeholder' => 'Unbenannter Artikel',
        'versions_sheet' => 'Jeder Speichervorgang erstellt eine neue Version.',
        'footnotes_sheet' => 'Alle Fußnoten des Artikels im Überblick.',
        'media_sheet' => 'Bilder dieses Artikels verwalten und einfügen.',
        'image_in_use_alert' => 'Dieses Bild wird noch im Artikel verwendet und kann nicht gelöscht werden.',
    ],

    'stats' => [
        'words' => ':count Wörter',
        'letters' => ':count Buchstaben',
    ],

    'footnote' => [
        'add_title' => 'Fußnote hinzufügen',
        'edit_title' => 'Fußnote bearbeiten',
        'reference' => 'Bezug:',
        'select_text_first' => 'Markiere zuerst ein Wort oder eine Textstelle im Artikel.',
        'placeholder' => 'Fußnotentext…',
        'empty' => 'Noch keine Fußnoten vorhanden.',
        'item_reference' => 'Bezug: „:excerpt“',
    ],

    'media' => [
        'upload_title' => 'Bild hochladen',
        'edit_title' => 'Bild bearbeiten',
        'description' => 'Alt-Text und Copyright sind Pflichtangaben. Die Bildunterschrift ist optional.',
        'file_label' => 'Bilddatei',
        'alt_label' => 'Alt-Text',
        'alt_placeholder' => 'Beschreibung für Screenreader…',
        'copyright_label' => 'Copyright',
        'copyright_placeholder' => 'z. B. Foto: Max Mustermann',
        'caption_label' => 'Bildunterschrift',
        'caption_placeholder' => 'Optionale Bildunterschrift…',
        'validation_required' => 'Alt-Text und Copyright sind Pflichtfelder.',
        'validation_no_file' => 'Bitte wähle eine Bilddatei aus.',
        'empty' => 'Noch keine Bilder für diesen Artikel hochgeladen.',
        'upload_button' => 'Bild hochladen',
        'used_in_article' => 'Im Artikel verwendet',
        'insert' => 'Einfügen',
        'error' => [
            'load_failed' => 'Medien konnten nicht geladen werden.',
            'upload_unavailable' => 'Upload nicht verfügbar.',
            'upload_failed' => 'Upload fehlgeschlagen.',
            'save_metadata_failed' => 'Metadaten konnten nicht gespeichert werden.',
            'delete_failed' => 'Medien konnten nicht gelöscht werden.',
        ],
    ],

    'versions' => [
        'empty' => 'Noch keine Versionen vorhanden.',
        'label' => 'Version :number',
        'restore' => 'Wiederherstellen',
        'restore_title' => 'Version :number wiederherstellen?',
        'restore_description' => 'Der aktuelle Artikel wird durch diese Version ersetzt. Dabei wird automatisch eine neue Version erstellt.',
    ],

    'assignment' => [
        'with_publication' => ':publication – Ausgabe :issue',
        'issue_only' => 'Ausgabe :issue',
    ],
];
