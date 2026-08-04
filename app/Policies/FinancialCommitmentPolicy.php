<?php

namespace App\Policies;

use App\Models\FinancialCommitment;
use App\Models\User;

class FinancialCommitmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FinancialCommitment $financialCommitment): bool
    {
        return true;
    }
}
