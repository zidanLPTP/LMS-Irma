<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilihanJawaban extends Model
{
    protected $table = 'pilihan_jawaban';

    protected $fillable = [
        'soal_id',
        'teks_pilihan',
        'apakah_benar',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'apakah_benar' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id');
    }
}
