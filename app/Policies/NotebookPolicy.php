<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notebook;
use App\Models\User;

final class NotebookPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Notebook $notebook): bool
    {
        return $notebook->user_id === $user->id;
    }

    public function update(User $user, Notebook $notebook): bool
    {
        return $this->view($user, $notebook);
    }

    public function delete(User $user, Notebook $notebook): bool
    {
        return $this->view($user, $notebook);
    }

    public function restore(User $user, Notebook $notebook): bool
    {
        return $this->view($user, $notebook);
    }
}
