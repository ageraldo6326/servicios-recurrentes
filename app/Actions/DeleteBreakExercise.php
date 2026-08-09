<?php

namespace App\Actions;

use App\Models\BreakExercise;
use App\Models\User;

final class DeleteBreakExercise
{
    public function execute(User $user, BreakExercise $exercise): void
    {
        abort_unless($exercise->user_id === $user->id, 403);

        $exercise->update(['updated_by' => $user->id]);
        $exercise->delete();
    }
}
