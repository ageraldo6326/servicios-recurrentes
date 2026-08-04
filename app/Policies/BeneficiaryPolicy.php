<?php

namespace App\Policies;

use App\Models\Beneficiary;
use App\Models\User;

class BeneficiaryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Beneficiary $beneficiary): bool
    {
        return true;
    }
}
