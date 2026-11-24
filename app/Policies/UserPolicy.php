<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAnyStaff(User $user): bool
    {
        return $user->canManageGovernance();
    }

    public function createStaff(User $user): bool
    {
        return $user->canManageGovernance();
    }

    public function updateStaff(User $user, User $target): bool
    {
        if (!$user->canManageGovernance()) {
            return false;
        }

        // Prevent non-system-admins from modifying a System Admin account
        if ($target->role === 'admin' && $user->role !== 'admin') {
            return false;
        }

        return $target->isStaff();
    }

    public function viewAnyResident(User $user): bool
    {
        return $user->isStaff();
    }

    public function verifyResident(User $user, User $resident): bool
    {
        return $user->canApproveRequests() && $resident->isResident();
    }
}