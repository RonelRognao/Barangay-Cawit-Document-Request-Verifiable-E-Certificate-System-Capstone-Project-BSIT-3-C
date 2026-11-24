<?php

namespace App\Policies;

use App\Models\ECertificate;
use App\Models\User;

class ECertificatePolicy
{
    public function view(User $user, ECertificate $certificate): bool
    {
        return $user->isStaff()
            || ($certificate->request && $certificate->request->user_id === $user->id);
    }

    public function revoke(User $user, ECertificate $certificate): bool
    {
        return $user->canIssueCertificates();
    }
}