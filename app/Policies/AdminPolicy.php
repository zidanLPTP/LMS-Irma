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
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, mixed $model): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, mixed $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }
}
