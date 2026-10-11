<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kuis extends Model
{
    protected $table = 'kuis';

    protected $fillable = [
        'bab_id',
        'tipe_kuis',
        'judul',
        'batas_waktu_per_soal',
        'nilai_lulus',
        'maksimal_kesempatan',
        'acak_soal',
    ];

    protected function casts(): array
    {
        return [
            'batas_waktu_per_soal' => 'integer',
            'nilai_lulus' => 'decimal:2',
            'maksimal_kesempatan' => 'integer',
            'acak_soal' => 'boolean',
        ];
    }

    /**
     * Validasi di level aplikasi untuk aturan satu pretest dan satu posttest.
     */
    protected static function booted(): void
    {
        static::saving(function (self $kuis) {
            if (in_array($kuis->tipe_kuis, ['pretest', 'posttest'], true)) {
                $kuisAda = static::where('tipe_kuis', $kuis->tipe_kuis)
                    ->where('id', '!=', $kuis->id ?? 0)
                    ->exists();

                if ($kuisAda) {
                    throw new \DomainException("Hanya boleh ada satu {$kuis->tipe_kuis} di dalam sistem.");
                }
            }
        });
    }

    /**
     * Relasi ke bab (opsional, null untuk pretest/posttest).
     */
    public function bab(): BelongsTo
    {
        return $this->belongsTo(Bab::class, 'bab_id');
    }

    /**
     * Relasi ke seluruh butir soal di kuis ini.
     */
    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'kuis_id')->orderBy('urutan');
    }

    /**
     * Relasi ke percobaan pengerjaan siswa.
     */
    public function percobaan(): HasMany
    {
        return $this->hasMany(PercobaanKuis::class, 'kuis_id');
    }

    /**
     * Ambil batas waktu per soal efektif (penimpa kuis atau bawaan pengaturan).
     */
    public function batasWaktuEfektif(): int
    {
        if (! is_null($this->batas_waktu_per_soal)) {
            return (int) $this->batas_waktu_per_soal;
        }

        return (int) Pengaturan::ambil('batas_waktu_per_soal', 60);
    }

    /**
     * Ambil nilai lulus efektif (penimpa kuis atau bawaan pengaturan).
     */
    public function nilaiLulusEfektif(): float
    {
        if (! is_null($this->nilai_lulus)) {
            return (float) $this->nilai_lulus;
        }

        return (float) Pengaturan::ambil('nilai_lulus', 65.00);
    }

    /**
     * Ambil batas maksimal kesempatan efektif.
     * Pretest & posttest selalu 1 kali. Kuis bab memakai penimpa atau bawaan pengaturan.
     */
    public function maksimalKesempatanEfektif(): int
    {
        if ($this->tipe_kuis === 'pretest' || $this->tipe_kuis === 'posttest') {
            return 1;
        }

        if (! is_null($this->maksimal_kesempatan)) {
            return (int) $this->maksimal_kesempatan;
        }

        return (int) Pengaturan::ambil('maksimal_kesempatan', 2);
    }
}
