<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\User;

class BackupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('backups.view');
    }

    public function view(User $user, Backup $backup): bool
    {
        return $user->can('backups.view');
    }

    public function create(User $user): bool
    {
        return $user->can('backups.create');
    }

    public function delete(User $user, Backup $backup): bool
    {
        return $user->can('backups.delete');
    }

    public function restore(User $user, Backup $backup): bool
    {
        return $user->can('backups.restore');
    }
}
