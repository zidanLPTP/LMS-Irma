<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JawabanSiswa extends Model
{
    protected $table = 'jawaban_siswa';

    protected $fillable = [
        'percobaan_kuis_id',
        'soal_id',
        'pilihan_jawaban_id',
        'jawaban_teks',
        'waktu_mulai_soal',
        'waktu_jawab',
        'poin_diperoleh',
        'catatan_guru',
        'dinilai_pada',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai_soal' => 'datetime',
            'waktu_jawab' => 'datetime',
            'dinilai_pada' => 'datetime',
            'poin_diperoleh' => 'decimal:2',
        ];
    }

    public function percobaanKuis(): BelongsTo
    {
        return $this->belongsTo(PercobaanKuis::class, 'percobaan_kuis_id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id');
    }

    public function pilihanJawaban(): BelongsTo
    {
        return $this->belongsTo(PilihanJawaban::class, 'pilihan_jawaban_id');
    }

    public function riwayatNilaiEsai()
    {
        return $this->hasMany(RiwayatNilaiEsai::class, 'jawaban_siswa_id');
    }
}
