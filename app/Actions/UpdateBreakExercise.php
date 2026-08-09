<?php

namespace App\Actions;

use App\Models\BreakExercise;
use App\Models\User;

final class UpdateBreakExercise
{
    public function execute(User $user, BreakExercise $exercise, array $attributes): BreakExercise
    {
        abort_unless($exercise->user_id === $user->id, 403);

        $exercise->fill([...$attributes, 'updated_by' => $user->id]);
        $exercise->save();

        return $exercise->refresh();
    }
}
