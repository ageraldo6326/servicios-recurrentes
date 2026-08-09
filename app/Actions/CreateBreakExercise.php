<?php

namespace App\Actions;

use App\Models\BreakExercise;
use App\Models\User;

final class CreateBreakExercise
{
    public function execute(User $user, array $attributes): BreakExercise
    {
        return BreakExercise::query()->create([
            ...$attributes,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
