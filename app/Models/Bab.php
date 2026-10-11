<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bab extends Model
{
    protected $table = 'bab';

    protected $fillable = [
        'urutan',
        'judul',
        'deskripsi',
        'status_terbit',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'status_terbit' => 'boolean',
        ];
    }

    /**
     * Relasi ke seluruh materi di bab ini.
     */
    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class, 'bab_id')->orderBy('urutan');
    }

    /**
     * Relasi ke kuis bab ini (jika ada).
     */
    public function kuis(): HasOne
    {
        return $this->hasOne(Kuis::class, 'bab_id');
    }
}
