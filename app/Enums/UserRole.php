<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case ProductManager = 'productmanager';
    case Editor = 'editor';
    case Lector = 'lector';
    case Author = 'author';

    public function canManageEditorSettingsSets(): bool
    {
        return $this !== self::Author;
    }

    public function canManageUsers(): bool
    {
        return $this === self::Admin;
    }

    public function canManageArticleWorkflow(): bool
    {
        return in_array($this, [self::Admin, self::ProductManager], true);
    }

    public function canBeAssignedArticleContent(): bool
    {
        return in_array($this, [self::Author, self::Editor, self::Lector], true);
    }
}
