<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatNilaiEsai extends Model
{
    protected $table = 'riwayat_nilai_esai';

    public $timestamps = false;

    protected $fillable = [
        'jawaban_siswa_id',
        'admin_id',
        'poin_lama',
        'poin_baru',
        'catatan_lama',
        'catatan_baru',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'poin_lama' => 'decimal:2',
            'poin_baru' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function jawabanSiswa(): BelongsTo
    {
        return $this->belongsTo(JawabanSiswa::class, 'jawaban_siswa_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
