<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Soal extends Model
{
    protected $table = 'soal';

    protected $fillable = [
        'kuis_id',
        'tipe_soal',
        'pertanyaan',
        'bobot_poin',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'bobot_poin' => 'integer',
            'urutan' => 'integer',
        ];
    }

    public function kuis(): BelongsTo
    {
        return $this->belongsTo(Kuis::class, 'kuis_id');
    }

    public function pilihanJawaban(): HasMany
    {
        return $this->hasMany(PilihanJawaban::class, 'soal_id')->orderBy('urutan');
    }

    public function jawabanSiswa(): HasMany
    {
        return $this->hasMany(JawabanSiswa::class, 'soal_id');
    }

    /**
     * Cek apakah tipe soal dinilai otomatis (pilihan ganda atau isian).
     */
    public function dinilaiOtomatis(): bool
    {
        return in_array($this->tipe_soal, ['pilihan_ganda', 'isian'], true);
    }

    /**
     * Cek apakah tipe soal esai (dinilai manual admin).
     */
    public function isEsai(): bool
    {
        return $this->tipe_soal === 'esai';
    }
}
