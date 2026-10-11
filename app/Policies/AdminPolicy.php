<?php

namespace App\Policies;

use App\Models\User;

class AdminPolicy
{
    /**
     * Pastikan hanya peran admin yang memiliki akses pengelolaan konten LMS.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
