<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case ProductManager = 'productmanager';
    case Editor = 'editor';
    case Author = 'author';

    public function canManageEditorSettingsSets(): bool
    {
        return $this !== self::Author;
    }
}
