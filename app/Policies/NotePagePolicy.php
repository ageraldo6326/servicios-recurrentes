<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NotePage;
use App\Models\User;

final class NotePagePolicy
{
    public function view(User $user, NotePage $page): bool
    {
        return $page->section()->withTrashed()->whereHas('notebook', fn ($query) => $query->withTrashed()->where('user_id', $user->id))->exists();
    }

    public function update(User $user, NotePage $page): bool
    {
        return $this->view($user, $page);
    }

    public function delete(User $user, NotePage $page): bool
    {
        return $this->view($user, $page);
    }

    public function restore(User $user, NotePage $page): bool
    {
        return $this->view($user, $page);
    }
}
