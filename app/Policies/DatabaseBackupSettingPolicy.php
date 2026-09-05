<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DatabaseBackupSetting;
use App\Models\User;

final class DatabaseBackupSettingPolicy
{
    public function update(User $user, DatabaseBackupSetting $setting): bool
    {
        return $user->canManageDatabaseBackups();
    }
}
