<?php

namespace App\Policies;

use App\Models\User;

class PenggunaPolicy
{
    /**
     * Hanya admin yang dapat mengelola pengguna.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admin tidak bisa menonaktifkan atau mengubah status dirinya sendiri.
     */
    public function toggleAktif(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Admin tidak bisa mereset password dirinya sendiri lewat panel ini.
     */
    public function resetPassword(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Admin hanya dapat menghapus akun yang bukan dirinya dan TIDAK memiliki data.
     */
    public function delete(User $user, User $model): bool
    {
        if (! $user->isAdmin() || $user->id === $model->id) {
            return false;
        }

        // Cek data: percobaan, progres, log akses
        $punyaPercobaan = $model->percobaanKuis()->exists();
        $punyaProgres = $model->progresMateri()->exists();
        $punyaLog = $model->logAkses()->exists();

        return ! ($punyaPercobaan || $punyaProgres || $punyaLog);
    }
}
