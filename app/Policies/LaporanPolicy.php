<?php

namespace App\Policies;

use App\Models\User;

class LaporanPolicy
{
    /**
     * Pastikan hanya admin yang berhak melihat dan mengekspor laporan.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Pastikan hanya admin yang berhak mengekspor laporan.
     */
    public function export(User $user): bool
    {
        return $user->isAdmin();
    }
}
