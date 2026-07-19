<?php

return [
    'title' => 'Benutzer',
    'description' => 'Benutzerkonten und Rollen verwalten',
    'new' => 'Neuer Benutzer',
    'empty' => 'Es wurden noch keine Benutzer angelegt.',
    'create_first' => 'Ersten Benutzer anlegen',

    'table' => [
        'name' => 'Name',
        'email' => 'E-Mail',
        'role' => 'Rolle',
        'created_at' => 'Erstellt',
    ],

    'form' => [
        'name' => 'Name',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'password_confirmation' => 'Passwort bestätigen',
        'password_optional' => 'Passwort (leer lassen, um das aktuelle zu behalten)',
        'role' => 'Rolle',
    ],

    'roles' => [
        'admin' => 'Administrator',
        'productmanager' => 'Produktmanager',
        'editor' => 'Redakteur:in',
        'lector' => 'Lektor:in',
        'author' => 'Autor:in',
    ],

    'create' => [
        'head_title' => 'Neuer Benutzer',
        'title' => 'Benutzer anlegen',
        'description' => 'Neues Benutzerkonto anlegen und eine Rolle zuweisen.',
        'submit' => 'Benutzer anlegen',
    ],

    'edit' => [
        'description' => 'Benutzerkonto, Rolle oder Passwort aktualisieren.',
        'breadcrumb' => 'Bearbeiten',
        'delete_heading' => 'Benutzer löschen',
        'delete_description' => 'Das Benutzerkonto wird dauerhaft gelöscht.',
        'delete_blocked' => 'Dieser Benutzer kann nicht gelöscht werden, weil noch verknüpfte Datensätze existieren oder es der letzte Administrator ist.',
    ],
];
