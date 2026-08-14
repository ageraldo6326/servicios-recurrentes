<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NotebookSection;
use App\Models\User;

final class NotebookSectionPolicy
{
    public function view(User $user, NotebookSection $section): bool
    {
        return $section->notebook()->withTrashed()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, NotebookSection $section): bool
    {
        return $this->view($user, $section);
    }

    public function delete(User $user, NotebookSection $section): bool
    {
        return $this->view($user, $section);
    }

    public function restore(User $user, NotebookSection $section): bool
    {
        return $this->view($user, $section);
    }
}
