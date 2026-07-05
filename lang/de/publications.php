<?php

return [
    'title' => 'Publikationen',
    'description' => 'Verwalte Magazine und ihre Ausgaben',
    'new' => 'Neue Publikation',
    'empty' => 'Du hast noch keine Publikationen angelegt.',
    'create_first' => 'Erste Publikation anlegen',

    'table' => [
        'name' => 'Name',
        'issues' => 'Ausgaben',
    ],

    'create' => [
        'description' => 'Lege eine Publikation an, z. B. Energieberater Magazin',
        'name_placeholder' => 'Energieberater Magazin',
        'editor_settings_heading' => 'Editor-Einstellungen',
        'editor_settings_description' => 'Wähle das Set für Artikel dieser Publikation.',
        'no_sets_hint' => 'Lege zuerst ein Editor-Einstellungen-Set an.',
        'submit' => 'Publikation anlegen',
    ],

    'edit' => [
        'description' => 'Bearbeite die Publikation, ihre Ausgaben und Kategorien',
        'editor_settings_description' => 'Wähle ein Set für Artikel dieser Publikation im Editor.',
        'delete_heading' => 'Publikation löschen',
        'delete_description' => 'Alle Ausgaben und Kategorien werden ebenfalls gelöscht.',
    ],

    'issues' => [
        'heading' => 'Ausgaben',
        'description' => 'Verwalte die Ausgaben dieser Publikation, z. B. 07-2026.',
        'empty' => 'Noch keine Ausgaben angelegt.',
        'table' => [
            'label' => 'Bezeichnung',
            'actions' => 'Aktionen',
        ],
        'new_label' => 'Neue Ausgabe',
        'placeholder' => 'z. B. 07-2026',
        'add_button' => 'Ausgabe hinzufügen',
        'save' => 'Speichern',
        'cancel' => 'Abbrechen',
        'delete' => 'Löschen',
        'delete_title' => 'Ausgabe löschen?',
        'delete_description' => 'Artikel, die dieser Ausgabe zugeordnet sind, verlieren die Zuordnung.',
    ],

    'categories' => [
        'heading' => 'Kategorien',
        'description' => 'Lege die Kategorien fest, die Artikeln dieser Publikation zugeordnet werden können.',
        'empty' => 'Noch keine Kategorien angelegt.',
        'table' => [
            'name' => 'Name',
            'actions' => 'Aktionen',
        ],
        'new_label' => 'Neue Kategorie',
        'placeholder' => 'z. B. Markt & Politik',
        'add_button' => 'Kategorie hinzufügen',
        'save' => 'Speichern',
        'cancel' => 'Abbrechen',
        'delete' => 'Löschen',
        'delete_title' => 'Kategorie löschen?',
        'delete_description' => 'Die Kategorie wird von allen zugeordneten Artikeln entfernt.',
    ],
];
