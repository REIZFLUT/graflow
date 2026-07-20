<?php

return [
    'title' => 'Einstellungen',
    'description' => 'Profil- und Kontoeinstellungen verwalten',
    'aria_label' => 'Einstellungen',

    'profile' => [
        'title' => 'Profileinstellungen',
        'sr_title' => 'Profileinstellungen',
        'heading' => 'Profil',
        'description' => 'Name und E-Mail-Adresse aktualisieren',
        'name' => 'Name',
        'name_placeholder' => 'Vollständiger Name',
        'email' => 'E-Mail-Adresse',
        'email_placeholder' => 'E-Mail-Adresse',
        'unverified' => 'Ihre E-Mail-Adresse ist nicht verifiziert.',
        'resend_verification' => 'Klicken Sie hier, um die Bestätigungs-E-Mail erneut zu senden.',
        'verification_sent' => 'Ein neuer Bestätigungslink wurde an Ihre E-Mail-Adresse gesendet.',
        'save' => 'Speichern',
    ],

    'security' => [
        'title' => 'Sicherheitseinstellungen',
        'sr_title' => 'Sicherheitseinstellungen',
        'password_heading' => 'Passwort aktualisieren',
        'password_description' => 'Verwenden Sie ein langes, zufälliges Passwort, um Ihr Konto zu schützen',
        'current_password' => 'Aktuelles Passwort',
        'current_password_placeholder' => 'Aktuelles Passwort',
        'new_password' => 'Neues Passwort',
        'new_password_placeholder' => 'Neues Passwort',
        'confirm_password' => 'Passwort bestätigen',
        'confirm_password_placeholder' => 'Passwort bestätigen',
        'save' => 'Speichern',
    ],

    'passkeys' => [
        'title' => 'Passkeys',
        'description' => 'Passkeys für passwortlose Anmeldung verwalten',
    ],

    'notifications' => [
        'title' => 'Benachrichtigungen',
        'sr_title' => 'Benachrichtigungseinstellungen',
        'heading' => 'E-Mail-Benachrichtigungen',
        'description' => 'Legen Sie fest, worüber Sie per E-Mail benachrichtigt werden möchten',
        'empty' => 'Für Ihre Rolle sind derzeit keine Benachrichtigungen verfügbar.',
        'save' => 'Speichern',
        'types' => [
            'assigned_responsible' => 'Ich möchte benachrichtigt werden, wenn ich (wieder) für einen Artikel zuständig bin.',
            'article_published' => 'Ich möchte benachrichtigt werden, wenn mein Artikel veröffentlicht wurde.',
            'manuscript_submitted' => 'Ich möchte benachrichtigt werden, wenn ein Manuskript eingereicht wurde.',
            'revision_requested' => 'Ich möchte benachrichtigt werden, wenn eine Überarbeitung angefordert wurde.',
            'editorial_completed' => 'Ich möchte benachrichtigt werden, wenn die Lektorat-/Redaktionsarbeit abgeschlossen wurde.',
            'ready_for_publication' => 'Ich möchte benachrichtigt werden, wenn ein Artikel druckreif ist.',
        ],
    ],

    'appearance' => [
        'title' => 'Erscheinungsbild',
        'sr_title' => 'Erscheinungsbild',
        'description' => 'Erscheinungsbild-Einstellungen für Ihr Konto aktualisieren',
        'light' => 'Hell',
        'dark' => 'Dunkel',
        'system' => 'System',
    ],

    'delete_account' => [
        'title' => 'Konto löschen',
        'description' => 'Konto und alle zugehörigen Daten löschen',
        'warning_title' => 'Warnung',
        'warning_body' => 'Bitte seien Sie vorsichtig, dies kann nicht rückgängig gemacht werden.',
        'button' => 'Konto löschen',
        'confirm_title' => 'Möchten Sie Ihr Konto wirklich löschen?',
        'confirm_description' => 'Wenn Ihr Konto gelöscht wird, werden alle zugehörigen Ressourcen und Daten dauerhaft entfernt. Bitte geben Sie Ihr Passwort ein, um die Löschung zu bestätigen.',
        'password_placeholder' => 'Passwort',
    ],
];
