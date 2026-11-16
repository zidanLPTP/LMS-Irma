<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstrumenPraktikalitas extends Model
{
    protected $table = 'instrumen_praktikalitas';

    protected $fillable = [
        'urutan',
        'aspek',
        'pernyataan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    /**
     * Relasi ke respon yang diberikan siswa untuk butir ini.
     */
    public function respon(): HasMany
    {
        return $this->hasMany(ResponPraktikalitas::class, 'instrumen_id');
    }
}
