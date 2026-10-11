<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $fillable = [
        'kunci',
        'nilai',
    ];

    /**
     * Ambil nilai pengaturan berdasarkan kunci dengan nilai bawaan.
     */
    public static function ambil(string $kunci, mixed $bawaan = null): mixed
    {
        $baris = static::where('kunci', $kunci)->first();

        return $baris ? $baris->nilai : $bawaan;
    }
}
