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

    /**
     * Tetapkan atau perbarui nilai pengaturan berdasarkan kunci.
     */
    public static function setel(string $kunci, mixed $nilai): static
    {
        return static::updateOrCreate(
            ['kunci' => $kunci],
            ['nilai' => (string) $nilai]
        );
    }
}
