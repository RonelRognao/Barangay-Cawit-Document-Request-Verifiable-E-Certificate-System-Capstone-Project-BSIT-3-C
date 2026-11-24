<?php

namespace App\Policies;

use App\Models\BlotterRecord;
use App\Models\User;

class BlotterRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->canProcessRequests();
    }

    public function update(User $user, BlotterRecord $blotterRecord): bool
    {
        return $user->canApproveRequests();
    }
}