<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TautanEdukasi extends Model
{
    protected $table = 'tautan_edukasi';

    protected $fillable = [
        'judul',
        'deskripsi',
        'url',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }
}
