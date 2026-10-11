<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PercobaanKuis extends Model
{
    protected $table = 'percobaan_kuis';

    protected $fillable = [
        'kuis_id',
        'user_id',
        'nomor_percobaan',
        'waktu_mulai',
        'waktu_selesai',
        'status_pengerjaan',
        'status_kelulusan',
        'esai_menunggu_nilai',
        'dibatalkan',
        'nilai_otomatis',
        'nilai_akhir',
        'urutan_soal_teracak',
    ];

    protected function casts(): array
    {
        return [
            'nomor_percobaan' => 'integer',
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'esai_menunggu_nilai' => 'boolean',
            'dibatalkan' => 'boolean',
            'nilai_otomatis' => 'decimal:2',
            'nilai_akhir' => 'decimal:2',
            'urutan_soal_teracak' => 'array',
        ];
    }

    public function kuis(): BelongsTo
    {
        return $this->belongsTo(Kuis::class, 'kuis_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jawabanSiswa(): HasMany
    {
        return $this->hasMany(JawabanSiswa::class, 'percobaan_kuis_id');
    }

    /**
     * Dapatkan nomor percobaan berikutnya untuk kuis dan siswa tertentu.
     * Nomor percobaan = nomor tertinggi yang pernah ada (termasuk dibatalkan) + 1.
     * Menjamin tidak terjadi bentrok dengan indeks unik kuis_id, user_id, nomor_percobaan.
     */
    public static function nomorBerikutnya(int $kuisId, int $userId): int
    {
        $maksNomor = static::where('kuis_id', $kuisId)
            ->where('user_id', $userId)
            ->max('nomor_percobaan');

        return $maksNomor ? ((int) $maksNomor + 1) : 1;
    }

    /**
     * Hitung jumlah kesempatan yang telah terpakai (hanya percobaan yang tidak dibatalkan).
     */
    public static function hitungKesempatanTerpakai(int $kuisId, int $userId): int
    {
        return static::where('kuis_id', $kuisId)
            ->where('user_id', $userId)
            ->where('dibatalkan', false)
            ->count();
    }

    /**
     * Teks status tampilan untuk siswa dan admin.
     */
    public function labelStatusKelulusan(): string
    {
        if ($this->esai_menunggu_nilai) {
            return 'menunggu penilaian esai';
        }

        return match ($this->status_kelulusan) {
            'lulus' => 'Lulus',
            'belum_lulus' => 'Belum lulus',
            'belum_lulus_lanjut' => 'Belum lulus, lanjut',
            default => '-',
        };
    }
}
