<?php

namespace App\Policies;

use App\Models\BarangayOfficial;
use App\Models\User;

class BarangayOfficialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageGovernance();
    }

    public function create(User $user): bool
    {
        return $user->canManageGovernance();
    }

    public function update(User $user, BarangayOfficial $barangayOfficial): bool
    {
        return $user->canManageGovernance();
    }
}