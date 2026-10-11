<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materi extends Model
{
    protected $table = 'materi';

    protected $fillable = [
        'bab_id',
        'judul',
        'tipe',
        'berkas_pdf',
        'url_youtube',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    /**
     * Relasi ke bab pemilik materi.
     */
    public function bab(): BelongsTo
    {
        return $this->belongsTo(Bab::class, 'bab_id');
    }

    /**
     * Relasi ke progres pembukaan oleh siswa.
     */
    public function progres(): HasMany
    {
        return $this->hasMany(ProgresMateri::class, 'materi_id');
    }
}
