<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NoteAttachment;
use App\Models\User;

final class NoteAttachmentPolicy
{
    public function view(User $user, NoteAttachment $attachment): bool
    {
        return $attachment->user_id === $user->id;
    }

    public function delete(User $user, NoteAttachment $attachment): bool
    {
        return $this->view($user, $attachment);
    }
}
