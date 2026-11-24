<?php

namespace App\Policies;

use App\Models\DocumentType;
use App\Models\User;

class DocumentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->canManageGovernance();
    }

    public function update(User $user, DocumentType $documentType): bool
    {
        return $user->canManageGovernance();
    }
}