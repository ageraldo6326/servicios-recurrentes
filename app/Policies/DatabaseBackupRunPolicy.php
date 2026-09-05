<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class DatabaseBackupRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageDatabaseBackups();
    }

    public function create(User $user): bool
    {
        return $user->canManageDatabaseBackups();
    }
}
