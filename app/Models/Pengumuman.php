<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected $fillable = [
        'admin_id',
        'judul',
        'isi',
        'status',
        'disematkan',
        'tanggal_kedaluwarsa',
    ];

    protected $casts = [
        'disematkan' => 'boolean',
        'tanggal_kedaluwarsa' => 'date',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function pembaca(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pengumuman_dibaca', 'pengumuman_id', 'user_id')
            ->withPivot('dibaca_pada')
            ->withTimestamps();
    }

    /**
     * Scope untuk pengumuman yang aktif terbit bagi siswa (status terbit dan belum kedaluwarsa).
     */
    public function scopeAktifUntukSiswa(Builder $query): Builder
    {
        return $query->where('status', 'terbit')
            ->where(function ($q) {
                $q->whereNull('tanggal_kedaluwarsa')
                    ->orWhere('tanggal_kedaluwarsa', '>=', now()->toDateString());
            })
            ->orderBy('disematkan', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Cek apakah pengumuman sudah dibaca oleh user tertentu.
     */
    public function apakahSudahDibaca(User $user): bool
    {
        return $this->pembaca()->where('user_id', $user->id)->exists();
    }
}
