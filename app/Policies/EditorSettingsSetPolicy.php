<?php

namespace App\Policies;

use App\Models\EditorSettingsSet;
use App\Models\User;

class EditorSettingsSetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EditorSettingsSet $editorSettingsSet): bool
    {
        return $user->id === $editorSettingsSet->owner_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EditorSettingsSet $editorSettingsSet): bool
    {
        return $user->id === $editorSettingsSet->owner_id;
    }

    public function delete(User $user, EditorSettingsSet $editorSettingsSet): bool
    {
        return $user->id === $editorSettingsSet->owner_id;
    }
}
