<?php

namespace App\Policies;

use App\Models\PercobaanKuis;
use App\Models\User;

class PercobaanKuisPolicy
{
    /**
     * Pastikan siswa hanya dapat mengakses percobaan miliknya sendiri.
     */
    public function view(User $user, PercobaanKuis $percobaan): bool
    {
        return (int) $user->id === (int) $percobaan->user_id;
    }

    /**
     * Pastikan siswa hanya dapat mengerjakan/menjawab percobaan miliknya sendiri.
     */
    public function kerjakan(User $user, PercobaanKuis $percobaan): bool
    {
        return (int) $user->id === (int) $percobaan->user_id
            && $percobaan->status_pengerjaan === 'sedang_mengerjakan'
            && ! $percobaan->dibatalkan;
    }
}
